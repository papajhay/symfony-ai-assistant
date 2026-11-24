# Ollama avec Symfony AI
Démarrer le serveur avec `ollama serve`, puis télécharger un modèle avec `ollama pull llama3.2:3b`. Le modèle d'embeddings se récupère avec `ollama pull nomic-embed-text`.
Configuration d'environnement : `OLLAMA_HOST_URL=http://127.0.0.1:11434`.
```yaml
ai:
  platform:
    ollama:
      endpoint: '%env(OLLAMA_HOST_URL)%'
```
Vérifier les modèles avec `ollama list`.
