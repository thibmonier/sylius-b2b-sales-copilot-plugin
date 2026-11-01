# Plugin Copilote d'Achat B2B pour Sylius

[English](README.md) | Français

Squelette d'assistant d'achat conversationnel pour Sylius 2 B2B : un widget dans le compte client envoie les demandes à un modèle de copilote derrière un port (`CopilotModelInterface`). Cette version ne livre que l'**adaptateur rejoué** : les réponses viennent de cassettes enregistrées, sans appel à une API de LLM.

**Démo** : [https://sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)  
**Auteur** : [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

## Fonctionnalités

- **Widget conversationnel** dans le tableau de bord du compte client (Stimulus, utilisable au clavier, réponses annoncées par une région live)
- **Mode rejoué** : une demande enregistrée renvoie sa réponse enregistrée ; toute autre demande renvoie « scénario non enregistré en démo »
- **Garde d'accès** : l'API du chat refuse les visiteurs anonymes (401) et les canaux absents de `enabled_channels` (403) ; le widget ne s'affiche que sur les canaux activés
- **Garde-fous fonctionnels** :
  - **Liste blanche d'outils** : seuls les outils approuvés peuvent être appelés
  - **Autorisation par portée** : les outils de lecture requièrent `b2b:read`, les modifications `b2b:cart`
  - **Limitation de débit** : nombre maximal de requêtes par membre par jour (configurable, défaut 50)
  - **Journal d'audit** : toutes les exécutions d'outils sont tracées dans un canal dédié
  - **Refus automatique** : les opérations de commande et de paiement sont refusées par conception

Les actions en langage naturel sur la boutique (recommande, ajout rapide, devis) via des outils MCP sont prévues pour une version ultérieure.

## Prérequis

- PHP `^8.2`
- Sylius `^2.2.9 || ^2.3`
- Symfony `^7.4 || ^8.0`

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

Importez les routes dans `config/routes/tmonier_sylius_b2b_sales_copilot.yaml` :

```yaml
tmonier_sylius_b2b_sales_copilot:
    resource: "@TmonierSyliusB2bSalesCopilotPlugin/config/routes.yaml"
```

Enregistrez le contrôleur du widget dans le point d'entrée des assets de la boutique :

```js
import '@vendor/tmonier/sylius-b2b-sales-copilot-plugin/assets/shop/entrypoint';
```

## Configuration

Listez les canaux où le copilote est disponible (canaux B2B) :

```yaml
# config/packages/tmonier_sylius_b2b_sales_copilot.yaml
tmonier_sylius_b2b_sales_copilot:
    enabled_channels: ['B2B_WEB']
```

Les réponses enregistrées sont dans `config/cassettes.jsonl` (JSONL, une demande enregistrée par ligne).

Les journaux d'audit sont écrits dans `var/log/copilot_audit.log`.

### Intégration LLM (optionnelle)

Le plugin peut appeler un vrai LLM via [Symfony AI Platform](https://github.com/symfony/ai). En cas d'indisponibilité du LLM, le plugin bascule automatiquement en mode rejoué.

```bash
composer require symfony/ai-platform symfony/ai-anthropic
```

```yaml
tmonier_sylius_b2b_sales_copilot:
    ai:
        enabled: true
        model: 'claude-sonnet-4-5'
        daily_cost_limit_cents: 500  # 0 = illimité
```

Configurez votre clé API via variable d'environnement comme documenté par Symfony AI.

Quand `ai.enabled` est `false` (défaut), seul l'adaptateur rejoué est utilisé — aucune dépendance LLM n'est nécessaire.

### Exemple de cassette

```jsonl
{"version":"1.0","intent":"état de mon panier","normalized":{"action":"get_cart"},"timestamp":"2026-10-31T10:00:00Z","tools":[{"name":"get_checkout_link","args":{},"result":"success"}],"response":"Votre panier contient 47 articles pour un total de 963,50 € HT. Voulez-vous passer au checkout ?"}
```

## Utilisation

Un client connecté sur un canal activé voit le widget du copilote dans le tableau de bord de son compte et peut envoyer une demande. Une demande enregistrée (par exemple « état de mon panier ») renvoie sa réponse enregistrée.

Le widget est accroché à `sylius_shop.account.dashboard.index.content.main.section`. Un thème qui remplace le tableau de bord peut l'inclure directement : `{% include '@TmonierSyliusB2bSalesCopilotPlugin/account/copilot_widget.html.twig' %}` (il n'affiche rien en dehors des canaux activés).

## Captures d'écran

![Le widget dans le tableau de bord du compte, après une demande enregistrée](docs/images/shop-copilot-widget-fr.jpg)

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
