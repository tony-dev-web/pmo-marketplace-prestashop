# PieceMotoOccasion marketplace pour PrestaShop 1.7 / 8

Vendez vos pièces moto sur la marketplace française [PieceMotoOccasion](https://piecemotooccasion.eu) depuis votre boutique PrestaShop : catalogue et stock synchronisés, commandes reçues dans le module et par courriel. Écrit pour les casses moto.

**Page et guide d'installation** : https://piecemotooccasion.eu/extensions/prestashop
**Téléchargement** : https://cdn.piecemotooccasion.eu/static/plugins/pmo-prestashop.zip
**Jeton API** : espace vendeur, page « Ma boutique en ligne »

## Installation

1. Modules › Gestionnaire de modules › Installer un module (zip contenant le dossier `pmo/`).
2. Configurer : jeton API (espace vendeur) et catégorie par défaut des pièces (Carénage, Selle…) ; la catégorie PrestaShop du produit est envoyée quand elle existe.
3. Copier l'URL de notification (`index.php?fc=module&module=pmo&controller=commande`) dans votre espace vendeur.
4. « Envoyer tout le catalogue » : chaque nouvelle pièce est vérifiée avant sa mise en ligne.

## API

L'extension utilise l'API vendeur PieceMotoOccasion (`https://piecemotooccasion.eu/api/v1`, jeton Bearer généré dans l'espace vendeur) : `PUT /produits`, `PATCH /produits/<ref>/stock`, `DELETE /produits/<ref>`, `GET /commandes`, `POST /commandes/<id>/expedier`, et reçoit un webhook JSON signé HMAC-SHA256 (`X-Pmo-Signature`) à chaque commande payée.

Licence MIT.

## Les extensions PieceMotoOccasion

- [WooCommerce](https://github.com/tony-dev-web/pmo-marketplace-woocommerce)
- [WordPress](https://github.com/tony-dev-web/pmo-marketplace-wordpress)
- [Shopify](https://github.com/tony-dev-web/pmo-marketplace-shopify)
- [Drupal](https://github.com/tony-dev-web/pmo-marketplace-drupal)
- [Magento](https://github.com/tony-dev-web/pmo-marketplace-magento)
- [API](https://github.com/tony-dev-web/pmo-marketplace-api)
- Toutes les extensions : https://piecemotooccasion.eu/extensions/
