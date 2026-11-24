# Comprendre le RAG
Le RAG (génération augmentée par récupération) recherche d'abord des passages proches de la question dans les documents locaux, puis transmet ces passages à l'agent pour rédiger une réponse. Un vectorizer transforme les textes en vecteurs; un store les conserve et un retriever retrouve les plus pertinents.
```php
$messages = new MessageBag(Message::ofUser($question));
$response = $agent->call($messages);
```
Lorsqu'un document change, relancer l'indexation pour mettre à jour les vecteurs.
