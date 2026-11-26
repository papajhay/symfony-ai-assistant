# Assistant Symfony avec RAG local et historique persistant

Le chatbot `/chat` utilise RAG : il recherche des extraits dans `data/docs/` avec des embeddings Ollama (`nomic-embed-text`) puis l'agent `llama3.2:3b` formule une réponse en français. Les conversations et messages sont conservés dans PostgreSQL avec Doctrine : `Conversation` possède plusieurs `Message`, supprimés en cascade.

## Historique des conversations

Après configuration de `DATABASE_URL` dans `.env.local`, appliquez la migration :

```sh
php bin/console doctrine:migrations:migrate
```

La barre latérale charge les conversations récentes. « Nouvelle conversation » réinitialise l'interface sans insérer de ligne vide. Le premier message crée la conversation et définit son titre; cliquer sur un titre recharge ses messages pour continuer. La suppression demande confirmation et supprime aussi les messages.

### API

- `POST /chat` avec `{"question":"Comment déclarer une route ?","conversationId":null}` crée une conversation. Réponse : `{"conversationId":1,"question":"…","answer":"…","response":"…"}`. Réutilisez cet identifiant pour continuer.
- `GET /chat/conversations` renvoie les identifiants, titres et dates, sans charger les messages.
- `GET /chat/conversations/{id}` renvoie le titre et les messages dans l'ordre chronologique; identifiant absent : HTTP 404.
- `DELETE /chat/conversations/{id}` supprime la conversation et renvoie `{"deleted":true}`; identifiant absent : HTTP 404.

Un échec de génération renvoie HTTP 500 et conserve le message utilisateur; aucun message assistant artificiel n'est ajouté.

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

## Tests

Exécutez la suite avec `php bin/phpunit`. Les tests d'intégration Doctrine nécessitent une base de test PostgreSQL configurée dans `DATABASE_URL` (le suffixe `_test` est appliqué en environnement `test`).

Si Ollama est arrêté, les appels de génération ou d'embedding échouent avec une erreur de connexion et `/chat` renvoie une erreur JSON HTTP 500. Si un modèle manque, utilisez `ollama pull <nom-du-modèle>` puis vérifiez avec `ollama list`. L'indexation requiert `nomic-embed-text`; les réponses requièrent `llama3.2:3b`.
