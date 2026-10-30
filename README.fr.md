# Plugin Copilote d'Achat B2B pour Sylius

[English](README.md) | Français

Assistant d'achat conversationnel avec IA pour Sylius 2 B2B : les acheteurs formulent leurs demandes d'achat en langage naturel depuis leur compte, et le copilote prépare le panier à partir des commandes passées et des devis.

**Démo** : [https://sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)  
**Auteur** : [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

## Fonctionnalités

- **Widget conversationnel** dans l'espace client B2B
- Demandes d'achat en **langage naturel** (recommander, ajout rapide par SKU, demande de devis)
- **Mode rejoué** pour la démo publique (réponses enregistrées, aucun appel LLM payant)
- **Traçabilité** de toutes les actions du copilote
- **Permissions par scope** (`b2b:read`, `b2b:cart`)

Les actions sur la boutique via des outils MCP sont prévues pour une version ultérieure.

## Prérequis

- PHP `^8.2`
- Sylius `^2.2.9 || ^2.3`
- Symfony `^6.4 || ^7.4 || ^8.0`

## Installation

```bash
composer require tmonier/sylius-b2b-sales-copilot-plugin
```

Activez le bundle dans `config/bundles.php` :

```php
return [
    // ...
    Tmonier\SyliusB2bSalesCopilotPlugin\TmonierSyliusB2bSalesCopilotPlugin::class => ['all' => true],
];
```

Exécutez les migrations :

```bash
php bin/console doctrine:migrations:migrate
```

## Configuration

Le plugin fonctionne en **mode rejoué** par défaut (réponses enregistrées pour la démonstration).

Fichier de cassettes : `config/cassettes.jsonl` (format JSONL, une réponse par ligne).

### Exemple de cassette

```jsonl
{"version":"1.0","intent":"état de mon panier","normalized":{"action":"get_cart"},"timestamp":"2026-10-30T10:00:00Z","tools":[{"name":"get_checkout_link","args":{},"result":"success"}],"response":"Votre panier contient 3 articles pour un total de 150,00 €. Voulez-vous passer au checkout ?"}
```

## Utilisation

Une fois installé, les clients B2B voient un widget de copilote dans leur tableau de bord de compte. Ils peuvent :

- Demander l'état de leur panier
- Recommander depuis des commandes passées
- Ajouter des produits par SKU
- Demander des devis

## Développement

### Lancer les tests

```bash
composer install
vendor/bin/phpunit --testsuite=all
```

### Qualité du code

```bash
vendor/bin/phpstan analyse
vendor/bin/ecs check
vendor/bin/rector process --dry-run
```

## Licence

MIT. Voir le fichier [LICENSE](LICENSE).

## Auteur

**Thibaut Monier**  
LinkedIn : [https://www.linkedin.com/in/thibautmonier/](https://www.linkedin.com/in/thibautmonier/)

## Contribuer

Les contributions sont les bienvenues. Veuillez ouvrir une issue ou une pull request sur GitHub.
