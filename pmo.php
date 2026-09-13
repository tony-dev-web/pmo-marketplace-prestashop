<?php
/**
 * PieceMotoOccasion marketplace : vendez vos produits sur https://piecemotooccasion.eu
 *
 * @author    PieceMotoOccasion <tony@piecemotooccasion.eu>
 * @copyright 2026 PieceMotoOccasion
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pmo extends Module
{
    public const API = 'https://piecemotooccasion.eu/api/v1';
    public const LOT = 50;

    public function __construct()
    {
        $this->name = 'pmo';
        $this->tab = 'market_place';
        $this->version = '1.0.0';
        $this->author = 'PieceMotoOccasion';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        parent::__construct();
        $this->displayName = 'PieceMotoOccasion marketplace';
        $this->description = 'Vendez vos produits sur PieceMotoOccasion : catalogue et stock synchronises, commandes recues automatiquement.';
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionUpdateQuantity')
            && Configuration::updateValue('PMO_JETON', '')
            && Configuration::updateValue('PMO_CATEGORIE', 'Pièce moto')
            && Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pmo_commande` (
                `id_pmo_commande` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_commande_pmo` INT UNSIGNED NOT NULL,
                `donnees` TEXT NOT NULL,
                `date_add` DATETIME NOT NULL,
                PRIMARY KEY (`id_pmo_commande`), UNIQUE KEY `pmo` (`id_commande_pmo`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PMO_JETON');
        Configuration::deleteByName('PMO_CATEGORIE');

        return parent::uninstall();
    }

    // ------------------------------------------------------------ configuration

    public function getContent()
    {
        $sortie = '';
        if (Tools::isSubmit('pmo_enregistrer')) {
            Configuration::updateValue('PMO_JETON', trim((string) Tools::getValue('PMO_JETON')));
            Configuration::updateValue('PMO_CATEGORIE', (string) Tools::getValue('PMO_CATEGORIE'));
            $sortie .= $this->displayConfirmation($this->l('Reglages enregistres.'));
        }
        if (Tools::isSubmit('pmo_synchroniser')) {
            $sortie .= $this->displayConfirmation($this->synchroniserTout());
        }
        $commandes = [];
        $lignes = Db::getInstance()->executeS('SELECT id_commande_pmo, donnees, date_add FROM `' . _DB_PREFIX_ . 'pmo_commande` ORDER BY id_pmo_commande DESC LIMIT 20');
        foreach ($lignes ?: [] as $c) {
            $d = json_decode($c['donnees'], true);
            $liv = isset($d['livraison']) ? $d['livraison'] : [];
            $commandes[] = [
                'id' => (int) $c['id_commande_pmo'],
                'client' => trim(($liv['prenom'] ?? '') . ' ' . ($liv['nom'] ?? '') . ', ' . ($liv['ville'] ?? ''), ' ,'),
                'total' => isset($d['total_vendeur_ttc']) ? $d['total_vendeur_ttc'] : '',
                'date' => $c['date_add'],
            ];
        }
        $this->context->smarty->assign([
            'pmo_jeton' => (string) Configuration::get('PMO_JETON'),
            'pmo_categorie' => (string) Configuration::get('PMO_CATEGORIE'),
            'pmo_webhook' => $this->context->link->getModuleLink('pmo', 'commande', [], true),
            'pmo_commandes' => $commandes,
        ]);

        return $sortie . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    // ------------------------------------------------------------ API

    public function appel($methode, $chemin, $corps = null)
    {
        $jeton = Configuration::get('PMO_JETON');
        if (!$jeton) {
            return ['erreur' => 'jeton API manquant'];
        }
        $ch = curl_init(self::API . $chemin);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $methode,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $jeton, 'Content-Type: application/json', 'User-Agent: pmo-prestashop/1.0'],
        ]);
        if ($corps !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corps, JSON_UNESCAPED_UNICODE));
        }
        $reponse = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $json = json_decode((string) $reponse, true);
        if ($reponse === false || $code >= 400) {
            return ['erreur' => isset($json['erreur']) ? $json['erreur'] : 'HTTP ' . $code];
        }

        return is_array($json) ? $json : [];
    }

    public function fiche($id_product)
    {
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $produit = new Product((int) $id_product, false, $id_lang);
        if (!Validate::isLoadedObject($produit) || !$produit->active || !$produit->reference) {
            return null;
        }
        $images = [];
        $link = $this->context->link;
        foreach (Image::getImages($id_lang, (int) $id_product) as $image) {
            $images[] = 'https://' . $link->getImageLink($produit->link_rewrite, (int) $image['id_image'], ImageType::getFormattedName('large'));
            if (count($images) >= 5) {
                break;
            }
        }

        return [
            'reference' => $produit->reference,
            'titre' => $produit->name,
            'description' => strip_tags((string) $produit->description_short),
            'information' => strip_tags((string) $produit->description),
            'prix_ttc' => round(Product::getPriceStatic((int) $id_product, true), 2),
            'stock' => (int) StockAvailable::getQuantityAvailableByProduct((int) $id_product),
            'categorie' => $this->categorie($produit),
            'etat_achat' => 'Occasion',
            'images' => $images,
            'url_boutique' => $link->getProductLink($produit),
        ];
    }

    public function categorie($produit)
    {
        // La categorie par defaut du produit dans PrestaShop, sinon celle du reglage.
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        if ($produit->id_category_default) {
            $cat = new Category((int) $produit->id_category_default, $id_lang);
            if (Validate::isLoadedObject($cat) && $cat->name && $cat->name !== 'Accueil' && $cat->name !== 'Home') {
                return $cat->name;
            }
        }

        return Configuration::get('PMO_CATEGORIE') ?: 'Pièce moto';
    }

    public function synchroniserTout()
    {
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $fiches = [];
        foreach (Product::getProducts($id_lang, 0, 0, 'id_product', 'ASC', false, true) as $p) {
            $fiche = $this->fiche((int) $p['id_product']);
            if ($fiche) {
                $fiches[] = $fiche;
            }
        }
        $envoyes = 0;
        $erreurs = [];
        foreach (array_chunk($fiches, self::LOT) as $lot) {
            $r = $this->appel('PUT', '/produits', ['produits' => $lot]);
            if (isset($r['erreur'])) {
                $erreurs[] = $r['erreur'];
                continue;
            }
            $envoyes += count(isset($r['produits']) ? $r['produits'] : []);
            foreach (isset($r['erreurs']) ? $r['erreurs'] : [] as $e) {
                $erreurs[] = ($e['reference'] ?? '?') . ' : ' . ($e['erreur'] ?? '');
            }
        }

        return sprintf('%d produit(s) envoye(s) a PieceMotoOccasion, %d erreur(s). %s', $envoyes, count($erreurs), implode(' | ', array_slice($erreurs, 0, 5)));
    }

    // ------------------------------------------------------------ hooks

    public function hookActionProductAdd($params)
    {
        $this->synchroniserProduit($params);
    }

    public function hookActionProductUpdate($params)
    {
        $this->synchroniserProduit($params);
    }

    private function synchroniserProduit($params)
    {
        $id = isset($params['id_product']) ? (int) $params['id_product'] : (isset($params['product']) ? (int) $params['product']->id : 0);
        $fiche = $id ? $this->fiche($id) : null;
        if ($fiche) {
            $this->appel('PUT', '/produits', ['produits' => [$fiche]]);
        }
    }

    public function hookActionUpdateQuantity($params)
    {
        $id = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        if (!$id) {
            return;
        }
        $produit = new Product($id);
        if (Validate::isLoadedObject($produit) && $produit->reference) {
            $this->appel('PATCH', '/produits/' . rawurlencode($produit->reference) . '/stock', ['stock' => (int) StockAvailable::getQuantityAvailableByProduct($id)]);
        }
    }
}
