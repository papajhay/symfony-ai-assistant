# Les contrôleurs Symfony
Un contrôleur traite une requête et renvoie une réponse HTTP. Exemple :
```php
use Symfony\\Component\\HttpFoundation\\JsonResponse;
public function status(): JsonResponse { return new JsonResponse(['ok' => true]); }
```
Symfony peut injecter des services dans les arguments d'une action.
