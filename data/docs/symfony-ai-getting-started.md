# Installer Symfony AI
Dans ce projet, Symfony AI est fourni par les paquets déjà installés. Pour l'ajouter à un projet compatible, les commandes Composer suivent la forme :
```sh
composer require symfony/ai-bundle symfony/ai-agent
```
Un agent est configuré en YAML, par exemple :
```yaml
ai:
  agent:
    default:
      platform: 'ai.platform.ollama'
      model: 'llama3.2:3b'
```
