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

class PmoCommandeModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        header('Content-Type: application/json');
        $corps = (string) file_get_contents('php://input');
        $signature = isset($_SERVER['HTTP_X_PMO_SIGNATURE']) ? $_SERVER['HTTP_X_PMO_SIGNATURE'] : '';
        $attendue = 'sha256=' . hash_hmac('sha256', $corps, (string) Configuration::get('PMO_JETON'));
        if (!$signature || !hash_equals($attendue, $signature)) {
            http_response_code(401);
            exit(json_encode(['erreur' => 'signature invalide']));
        }
        $donnees = json_decode($corps, true);
        if (!is_array($donnees) || ($donnees['evenement'] ?? '') !== 'commande.payee' || empty($donnees['commande']['id'])) {
            exit(json_encode(['ok' => true, 'ignore' => true]));
        }
        $c = $donnees['commande'];
        Db::getInstance()->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'pmo_commande` (id_commande_pmo, donnees, date_add) VALUES ('
            . (int) $c['id'] . ', \'' . pSQL(json_encode($c, JSON_UNESCAPED_UNICODE), true) . '\', NOW())');

        $lignes = '';
        foreach (isset($c['lignes']) ? $c['lignes'] : [] as $l) {
            $lignes .= '- ' . $l['titre'] . ' x ' . $l['quantite'] . ' : ' . $l['total_ttc'] . " EUR\n";
        }
        $liv = isset($c['livraison']) ? $c['livraison'] : [];
        $texte = 'Commande PieceMotoOccasion #' . (int) $c['id'] . "\n\n" . $lignes . "\nLivraison : " . ($liv['prenom'] ?? '') . ' ' . ($liv['nom'] ?? '') . ', '
            . ($liv['adresse'] ?? '') . ', ' . ($liv['code_postal'] ?? '') . ' ' . ($liv['ville'] ?? '') . ' - ' . ($liv['telephone'] ?? '')
            . "\n\nDetail et expedition : https://piecemotooccasion.eu/a2/vendeurs/boutique";
        @mail(Configuration::get('PS_SHOP_EMAIL'), 'Commande PieceMotoOccasion #' . (int) $c['id'], $texte);
        exit(json_encode(['ok' => true]));
    }
}
