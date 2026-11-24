# Assistant Symfony avec RAG local

Le chatbot `/chat` utilise RAG : il recherche des extraits dans `data/docs/` avec des embeddings Ollama (`nomic-embed-text`) puis l'agent `llama3.2:3b` formule une réponse en français. Le store est le cache Symfony `cache.app`, local et sans base de données.

## Démarrer Ollama

Dans un terminal, lancez `ollama serve` (si Ollama ne tourne pas déjà comme service). Dans un autre :

```sh
ollama list
ollama pull llama3.2:3b
ollama pull nomic-embed-text
ollama list
```

L'URL est définie par `OLLAMA_HOST_URL=http://127.0.0.1:11434` dans `.env.local`.

## Indexer les documents

```sh
php bin/console ai:store:index symfony_docs
```

Après toute modification ou ajout dans `data/docs/`, relancez cette commande. Le cache partagé permet aux requêtes HTTP de retrouver les vecteurs indexés. Pour repartir d'un cache vide : `php bin/console cache:pool:clear cache.app`, puis relancez l'indexation.

## Démarrer et appeler Symfony

```sh
symfony server:start
```

Dans un terminal séparé :

```sh
curl -X POST http://127.0.0.1:8000/chat -H 'Content-Type: application/json' -d '{"question":"Comment déclarer une route avec un attribut ?"}'
curl -X POST http://127.0.0.1:8000/chat -H 'Content-Type: application/json' -d '{"question":"Quelle est la capitale du Pérou ?"}'
```

La première question est documentée dans `symfony-routing.md`. La seconde sert à vérifier la réponse de repli quand les documents ne couvrent pas la question.

Si Ollama est arrêté, les appels de génération ou d'embedding échouent avec une erreur de connexion et `/chat` renvoie une erreur JSON HTTP 500. Si un modèle manque, utilisez `ollama pull <nom-du-modèle>` puis vérifiez avec `ollama list`. L'indexation requiert `nomic-embed-text`; les réponses requièrent `llama3.2:3b`.
