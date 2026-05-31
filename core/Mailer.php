<?php

declare(strict_types=1);

/**
 * Mailer — client SMTP natif PHP pour Gmail (STARTTLS sur port 587).
 *
 * Pas de dépendance externe : connexion TCP brute + upgrade STARTTLS + AUTH LOGIN.
 * Corps de l'email encodé en base64 (Content-Transfer-Encoding: base64).
 *
 * Configuration via config/config.php → clé 'mail' (lue depuis les variables d'env) :
 *   MAIL_HOST       : smtp.gmail.com
 *   MAIL_PORT       : 587
 *   MAIL_USERNAME   : votre.adresse@gmail.com
 *   MAIL_PASSWORD   : mot de passe d'application Gmail (16 caractères, sans espaces)
 *                     Générer : myaccount.google.com → Sécurité → Mots de passe des applications
 *   MAIL_FROM_EMAIL : adresse expéditeur (égale à MAIL_USERNAME pour Gmail)
 *   MAIL_FROM_NAME  : nom affiché dans le champ "De :"
 *
 * Usage minimal :
 *   Mailer::sendMail('dest@example.com', 'Sujet', '<p>Corps HTML</p>');
 */
class Mailer
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;

    /** @var resource|null */
    private $socket = null;

    public function __construct()
    {
        $config          = require __DIR__ . '/../config/config.php';
        $mail            = $config['mail'] ?? [];
        $this->host      = (string) ($mail['host']       ?? 'smtp.gmail.com');
        $this->port      = (int)    ($mail['port']       ?? 587);
        $this->username  = (string) ($mail['username']   ?? '');
        $this->password  = (string) ($mail['password']   ?? '');
        $this->fromEmail = (string) ($mail['from_email'] ?? $this->username);
        $this->fromName  = (string) ($mail['from_name']  ?? 'BudgetFlow');
    }

    /**
     * Envoie un email HTML.
     *
     * @param string $to      Adresse destinataire
     * @param string $subject Sujet (UTF-8)
     * @param string $body    Corps HTML
     * @return bool True si envoi réussi
     */
    public function send(string $to, string $subject, string $body): bool
    {
        if ($this->username === '' || $this->password === '') {
            error_log('[Mailer] Identifiants Gmail non configurés — email non envoyé à ' . $to);
            return false;
        }

        try {
            $this->connect();
            $this->authenticate();
            $this->sendMessage($to, $subject, $body);
            $this->quit();
            return true;
        } catch (RuntimeException $e) {
            error_log('[Mailer] Erreur SMTP : ' . $e->getMessage());
            $this->closeSocket();
            return false;
        }
    }

    /**
     * Méthode statique pour envoyer sans instanciation explicite (corps HTML direct).
     */
    public static function sendMail(string $to, string $subject, string $body): bool
    {
        return (new self())->send($to, $subject, $body);
    }

    // -------------------------------------------------------------------------
    // SMTP internals
    // -------------------------------------------------------------------------

    private function connect(): void
    {
        $errno  = 0;
        $errstr = '';
        // Connexion TCP brute (non chiffrée) sur port 587, puis STARTTLS.
        $socket = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            15
        );

        if ($socket === false) {
            throw new RuntimeException("Connexion SMTP échouée : {$errstr} ({$errno})");
        }

        $this->socket = $socket;
        stream_set_timeout($this->socket, 15);

        $this->expectCode(220); // Bannière du serveur

        $this->cmd("EHLO " . $this->localHostname());
        $this->readAll(); // Lire les lignes multi-lignes EHLO

        // Upgrade vers TLS.
        $this->cmd('STARTTLS');
        $this->expectCode(220);

        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('STARTTLS : activation du chiffrement TLS échouée.');
        }

        // Ré-envoyer EHLO après upgrade TLS (obligatoire).
        $this->cmd("EHLO " . $this->localHostname());
        $this->readAll();
    }

    private function authenticate(): void
    {
        $this->cmd('AUTH LOGIN');
        $this->expectCode(334);

        $this->cmd(base64_encode($this->username));
        $this->expectCode(334);

        $this->cmd(base64_encode($this->password));
        $this->expectCode(235); // Authentification acceptée
    }

    private function sendMessage(string $to, string $subject, string $body): void
    {
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $fromHeader     = $this->fromName !== ''
            ? '=?UTF-8?B?' . base64_encode($this->fromName) . '?= <' . $this->fromEmail . '>'
            : $this->fromEmail;

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        $headers .= "From: {$fromHeader}\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$encodedSubject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "X-Mailer: BudgetFlow/1.0\r\n";

        $encodedBody = chunk_split(base64_encode($body));

        $this->cmd("MAIL FROM:<{$this->fromEmail}>");
        $this->expectCode(250);

        $this->cmd("RCPT TO:<{$to}>");
        $this->expectCode(250);

        $this->cmd('DATA');
        $this->expectCode(354);

        $this->write($headers . "\r\n" . $encodedBody . "\r\n.");
        $this->expectCode(250);
    }

    private function quit(): void
    {
        $this->cmd('QUIT');
        $this->closeSocket();
    }

    private function cmd(string $command): void
    {
        $this->write($command);
    }

    private function write(string $data): void
    {
        if ($this->socket === null) {
            throw new RuntimeException('Socket SMTP non initialisé.');
        }

        fwrite($this->socket, $data . "\r\n");
    }

    private function read(): string
    {
        if ($this->socket === null) {
            throw new RuntimeException('Socket SMTP non initialisé.');
        }

        $response = fgets($this->socket, 512);

        return $response !== false ? $response : '';
    }

    /** Lit toutes les lignes d'une réponse multi-lignes (e.g. EHLO). */
    private function readAll(): string
    {
        $full = '';
        do {
            $line  = $this->read();
            $full .= $line;
        } while (strlen($line) >= 4 && $line[3] === '-'); // Multi-ligne SMTP

        return $full;
    }

    private function expectCode(int $expected): string
    {
        $response = $this->readAll();
        $code     = (int) substr($response, 0, 3);

        if ($code !== $expected) {
            throw new RuntimeException(
                "SMTP inattendu — attendu {$expected}, reçu {$code}: " . trim($response)
            );
        }

        return $response;
    }

    private function closeSocket(): void
    {
        if ($this->socket !== null) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function localHostname(): string
    {
        return gethostname() ?: 'localhost';
    }

    // ── Static helpers — email notification methods ───────────────────────────

    /**
     * Load an email template and return the rendered HTML.
     *
     * @param string               $template Nom du fichier sans .php (dans app/views/emails/)
     * @param array<string, mixed> $data     Variables à injecter dans le template
     */
    private static function renderTemplate(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/../app/views/emails/' . $template . '.php';
        return (string) ob_get_clean();
    }

    /** Notifie un utilisateur que son compte a été activé. */
    public static function sendAccountValidated(array $user): bool
    {
        try {
            $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                      . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/login';
            $body = self::renderTemplate('account_validated', [
                'userName' => (string) ($user['name']  ?? ''),
                'loginUrl' => $loginUrl,
            ]);
            return self::sendMail((string) ($user['email'] ?? ''), 'Votre compte BudgetFlow a été activé', $body);
        } catch (\Throwable $e) {
            error_log('[Mailer] sendAccountValidated : ' . $e->getMessage());
            return false;
        }
    }

    /** Envoie une invitation à rejoindre un budget partagé. */
    public static function sendBudgetInvitation(array $invitee, array $inviter, array $budget): bool
    {
        try {
            $budgetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                       . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/budgets/show?id=' . (int) ($budget['id'] ?? 0);
            $body = self::renderTemplate('budget_invitation', [
                'invitee'   => $invitee,
                'inviter'   => $inviter,
                'budget'    => $budget,
                'budgetUrl' => $budgetUrl,
            ]);
            $subject = '[BudgetFlow] Invitation — ' . ($budget['name'] ?? 'Budget partagé');
            return self::sendMail((string) ($invitee['email'] ?? ''), $subject, $body);
        } catch (\Throwable $e) {
            error_log('[Mailer] sendBudgetInvitation : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie une alerte de dépassement de plafond budget.
     *
     * @param float $percent Pourcentage atteint (>= 80 pour déclencher)
     * @param float $spent   Montant dépensé
     * @param float $limit   Plafond du budget
     */
    public static function sendBudgetAlert(array $user, array $budget, float $percent, float $spent, float $limit): bool
    {
        try {
            $budgetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                       . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/budgets/show?id=' . (int) ($budget['id'] ?? 0);
            $body = self::renderTemplate('budget_alert', [
                'user'      => $user,
                'budget'    => $budget,
                'percent'   => $percent,
                'spent'     => $spent,
                'limit'     => $limit,
                'budgetUrl' => $budgetUrl,
            ]);
            $subject = $percent >= 100
                ? '[BudgetFlow] Budget dépassé — ' . ($budget['name'] ?? '')
                : '[BudgetFlow] Alerte budget — ' . ($budget['name'] ?? '') . ' à ' . round($percent) . '%';
            return self::sendMail((string) ($user['email'] ?? ''), $subject, $body);
        } catch (\Throwable $e) {
            error_log('[Mailer] sendBudgetAlert : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie une demande de suppression de compte à tous les admins actifs.
     *
     * @param array<string, mixed> $requestingUser Utilisateur demandant la suppression
     */
    public static function sendDeletionRequestToAdmins(array $requestingUser): bool
    {
        try {
            $admins  = (new User())->findAllAdmins();
            $adminUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                      . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/admin/users';
            $ok = true;
            foreach ($admins as $admin) {
                $body = self::renderTemplate('deletion_request_admin', [
                    'requestingUser' => $requestingUser,
                    'adminUrl'       => $adminUrl,
                ]);
                $subject = '[Admin BudgetFlow] Demande de suppression — ' . ($requestingUser['name'] ?? '');
                if (!self::sendMail((string) ($admin['email'] ?? ''), $subject, $body)) {
                    $ok = false;
                }
            }
            return $ok;
        } catch (\Throwable $e) {
            error_log('[Mailer] sendDeletionRequestToAdmins : ' . $e->getMessage());
            return false;
        }
    }

    /** Confirme à l'utilisateur que son compte a été supprimé. */
    public static function sendDeletionConfirmed(array $user): bool
    {
        try {
            $body = self::renderTemplate('deletion_confirmed', [
                'user' => $user,
            ]);
            return self::sendMail((string) ($user['email'] ?? ''), 'Votre compte BudgetFlow a été supprimé', $body);
        } catch (\Throwable $e) {
            error_log('[Mailer] sendDeletionConfirmed : ' . $e->getMessage());
            return false;
        }
    }
}
