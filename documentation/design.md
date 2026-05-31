# BudgetFlow — Design System

> Référence complète des couleurs, composants, typographie et conventions visuelles de BudgetFlow.

---

## 1. Thème général

BudgetFlow utilise un thème **dark** exclusivement, inspiré des outils financiers professionnels.  
Fond très sombre (`#0F1117`), cartes légèrement plus claires, accent violet pour les CTA et vert pour les revenus/succès.

**Palette en un mot :** sombre, violet, vert.

---

## 2. Palette de couleurs

### Fonds

| Variable | Valeur | Usage |
|----------|--------|-------|
| `--bg-page` | `#0F1117` | Fond de page principal |
| `--bg-card` | `#1A1D27` | Cartes, sidebar, modals |
| `--bg-elevated` | `#222636` | Inputs, dropdowns, éléments surélevés |
| `--bg-hover` | `#2A2F45` | Hover des éléments interactifs |

### Accent et actions

| Variable | Valeur | Usage |
|----------|--------|-------|
| `--accent` | `#6C63FF` | Boutons primaires, liens actifs, focus |
| `--accent-hover` | `#7B74FF` | Hover sur accent |

### Couleurs fonctionnelles

| Variable | Valeur | Usage |
|----------|--------|-------|
| `--color-income` | `#22D3A5` | Revenus, succès, éléments positifs |
| `--color-expense` | `#FF6B6B` | Dépenses, erreurs |
| `--color-warning` | `#FFB547` | Alertes, budgets proches du plafond |
| `--color-danger` | `#FF4D4D` | Dépassements, danger |

### Texte

| Variable | Valeur | Usage |
|----------|--------|-------|
| `--text-primary` | `#F0F2F8` | Texte principal |
| `--text-secondary` | `#8B90A7` | Texte secondaire, labels, placeholders |
| `--text-muted` | `#555B75` | Texte discret, métadonnées |

### Bordures

| Variable | Valeur | Usage |
|----------|--------|-------|
| `--border` | `#2A2F45` | Bordures des cartes et inputs |

---

## 3. Typographie

| Usage | Police | Poids | Notes |
|-------|--------|-------|-------|
| Titres de page, H1 | DM Sans | 600–700 | `font-family:'DM Sans'` |
| Corps, labels, boutons | Plus Jakarta Sans | 400–600 | Police principale |
| Montants, codes | JetBrains Mono | 500–600 | Chiffres tabulaires |

**Import Google Fonts (déjà dans `app/views/layouts/app.php`) :**
```html
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700
  &family=Plus+Jakarta+Sans:wght@300;400;500;600;700
  &family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
```

---

## 4. Composants CSS — classes `bf-*`

### Carte

```css
.bf-card {
    background: #1A1D27;
    border: 1px solid #2A2F45;
    border-radius: 16px;
    padding: 24px;
}
```

### Input

```css
.bf-input {
    background: #222636 !important;
    border: 1px solid #2A2F45 !important;
    border-radius: 10px !important;
    color: #F0F2F8 !important;
    padding: 12px 16px;
}
.bf-input:focus {
    border-color: #6C63FF !important;
    box-shadow: 0 0 0 3px rgba(108,99,255,.15) !important;
}
```

### Bouton primaire

```css
.bf-btn-primary {
    background: #6C63FF;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px 28px;
    font-weight: 600;
    transition: all .2s;
}
.bf-btn-primary:hover {
    background: #7B74FF;
    transform: translateY(-1px);
}
```

### Lien sidebar

```css
.bf-sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    border-radius: 10px;
    color: #8B90A7;
    text-decoration: none;
    font-size: 14px;
}
.bf-sidebar-link:hover  { background: #2A2F45; color: #F0F2F8; }
.bf-sidebar-link.active { background: rgba(108,99,255,.15); color: #6C63FF; }
```

### Badges

```css
.bf-badge          { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
.bf-badge-user     { background: rgba(108,99,255,.15); color: #6C63FF; }
.bf-badge-admin    { background: rgba(255,181,71,.15);  color: #FFB547; }
.bf-badge-income   { background: rgba(34,211,165,.15);  color: #22D3A5; }
.bf-badge-expense  { background: rgba(255,107,107,.15); color: #FF6B6B; }
```

### Alertes flash

```css
.bf-alert          { border-radius: 10px; padding: 12px 16px; font-size: 14px; border: 1px solid; }
.bf-alert-success  { background: rgba(34,211,165,.1);  border-color: rgba(34,211,165,.3);  color: #22D3A5; }
.bf-alert-danger   { background: rgba(255,107,107,.1); border-color: rgba(255,107,107,.3); color: #FF6B6B; }
.bf-alert-warning  { background: rgba(255,181,71,.1);  border-color: rgba(255,181,71,.3);  color: #FFB547; }
.bf-alert-info     { background: rgba(96,165,250,.1);  border-color: rgba(96,165,250,.3);  color: #60A5FA; }
```

---

## 5. Design spécifique — Wavy Green

L'assistant IA et le bouton Rapport PDF utilisent un design **"wavy"** animé avec bordure verte.

### Principe

- Fond sombre `#0F1117`
- Bordure `#22D3A5` (vert)
- `border-radius` qui morphe en continu → effet vague
- `box-shadow` vert pulsant synchronisé

### Animation bouton circulaire (AI FAB)

```css
#ai-fab {
    border: 3px solid #22D3A5;
    border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
    animation: ai-wavy 3s ease-in-out infinite;
    box-shadow: 0 0 16px rgba(34,211,165,.45), 0 0 32px rgba(34,211,165,.2);
}
@keyframes ai-wavy {
    0%   { border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
    25%  { border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%; }
    50%  { border-radius: 50% 60% 30% 40% / 40% 50% 70% 60%; }
    75%  { border-radius: 40% 50% 60% 30% / 60% 40% 30% 70%; }
    100% { border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
}
```

### Animation bulle de chat / bouton rectangulaire

```css
.element-wavy {
    border: 2px solid #22D3A5;
    animation: bubble-wavy 4s ease-in-out infinite;
}
@keyframes bubble-wavy {
    0%   { border-radius: 18px 6px 18px 6px / 6px 18px 6px 18px; }
    25%  { border-radius: 6px 18px 6px 18px / 18px 6px 18px 6px; }
    50%  { border-radius: 18px 6px 18px 6px / 18px 6px 18px 6px; }
    75%  { border-radius: 6px 18px 6px 18px / 6px 18px 6px 18px; }
    100% { border-radius: 18px 6px 18px 6px / 6px 18px 6px 18px; }
}
```

---

## 6. Assistant IA — composants UI

### Bouton flottant (FAB)

```
Position   : fixed, bottom 28px, right 28px, z-index 1050
Taille     : 86×86px
Design     : wavy vert + GIF ai-chat.gif (56px)
Animation  : ai-wavy 3s + glow vert pulsant
```

### Modal chat

```
Fond          : #1A1D27 (bf-card)
Bordure       : 2px solid #22D3A5 + animation wavy modale
En-tête       : gradient rgba(34,211,165,.10) → #1A1D27
Avatar        : GIF ai-chat.gif dans cercle vert borderné
Titre         : "BudgetFlow AI" couleur #22D3A5
Zone messages : fond #0A0D13, scrollable
Bulles bot    : fond #0F1117, bordure verte wavy animée
Bulles user   : fond #0d1f1b, bordure verte wavy (offset -2s)
```

### Suggestions rapides (chips)

```
Fond       : #0F1117
Bordure    : rgba(34,211,165,.3)
Hover      : fond rgba(34,211,165,.1), couleur #22D3A5, glow vert
```

---

## 7. Rapport PDF — design page impression

### Toolbar (masquée à l'impression)

```
Fond     : #0d1117
Bouton   : "Imprimer / Enregistrer PDF" — vert #22D3A5
Retour   : lien gris sobre
```

### En-tête rapport

```
Fond          : #1a1a2e avec gradient sombre
Strip décoratif : gradient vert horizontal (4px)
Logo          : carré vert #22D3A5 avec icône bi-bar-chart-fill
Période       : texte vert, 20px bold
```

### Stat cards

```
Revenus   : fond #f0fdf8, bordure #22D3A5, valeur verte
Dépenses  : fond #fff5f5, bordure #FF6B6B, valeur rouge
Solde     : fond #f0f9ff, bordure #38bdf8, valeur bleue
```

### Montants

```
Format    : number_format(2, ',', ' ') . ' DT'
Exemple   : 1 250,00 DT
```

---

## 8. Iconographie

BudgetFlow utilise **Bootstrap Icons 1.11** chargé depuis CDN.

| Contexte | Icône | Classe |
|----------|-------|--------|
| Tableau de bord | `bi-grid` | `.bi.bi-grid` |
| Transactions | `bi-arrow-left-right` | |
| Budgets | `bi-wallet2` | |
| Catégories | `bi-tag` | |
| Profil | `bi-person` | |
| Rapport PDF | `bi-file-earmark-pdf` | |
| Statistiques | `bi-bar-chart-fill` | |
| Revenus | `bi-arrow-up-circle-fill` | |
| Dépenses | `bi-arrow-down-circle-fill` | |
| Impression | `bi-printer-fill` | |

> Aucun emoji dans le code PHP ou HTML — toujours utiliser les Bootstrap Icons.

---

## 9. Conventions de formatage

```php
// Montants (Dinar Tunisien)
number_format($amount, 2, ',', ' ') . ' DT'
// → "1 250,00 DT"

// Dates
date('d/m/Y', strtotime($date))
// → "31/05/2026"

// Mois en français
$moisFr = ['Janvier','Février','Mars','Avril','Mai','Juin',
           'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$label = $moisFr[$m - 1] . ' ' . $year;
// → "Mai 2026"
```

---

## 10. Responsive & Dark mode

- Bootstrap 5.3 gère le responsive (grid, breakpoints)
- Le dark mode est géré via `data-theme` sur `<html>` (stocké en `localStorage`)
- Le script de détection est inline dans `<head>` pour éviter le FOUC
- Variables CSS `var(--bg-page)` etc. changent selon `data-theme`
