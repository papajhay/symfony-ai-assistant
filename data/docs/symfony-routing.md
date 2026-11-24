# Le routage Symfony
Une route associe une URL et une méthode HTTP à une action. Avec les attributs PHP :
```php
#[Route('/salut', name: 'salut', methods: ['GET'])]
public function salut(): Response { return new Response('Bonjour'); }
```
L'attribut `Route` se trouve dans `Symfony\\Component\\Routing\\Attribute\\Route`.
