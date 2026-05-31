#!/bin/bash
# Initialisation Ollama — attend que le service soit pret, puis telecharge le modele.
# Ce script est destine a etre lance manuellement ou via un entrypoint supplementaire.

MODEL="llama3.2:1b"

echo "Attente du service Ollama..."
until curl -sf http://ollama:11434/api/tags > /dev/null 2>&1; do
    sleep 3
done
echo "Ollama est pret."

# Telecharger le modele uniquement s'il n'est pas deja present.
if ! curl -sf http://ollama:11434/api/tags | grep -q "$MODEL"; then
    echo "Telechargement du modele $MODEL..."
    curl -X POST http://ollama:11434/api/pull \
         -H "Content-Type: application/json" \
         -d "{\"name\": \"$MODEL\"}"
    echo "Modele $MODEL telecharge."
else
    echo "Modele $MODEL deja present."
fi
