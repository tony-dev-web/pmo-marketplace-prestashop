{*
 * PieceMotoOccasion marketplace : configuration du module
 *
 * @author    PieceMotoOccasion <tony@piecemotooccasion.eu>
 * @copyright 2026 PieceMotoOccasion
 * @license   https://opensource.org/licenses/MIT MIT License
 *}
<div class="panel">
  <h3>PieceMotoOccasion marketplace</h3>
  <form method="post" class="form-horizontal">
    <div class="form-group">
      <label class="control-label col-lg-3">Jeton API PieceMotoOccasion</label>
      <div class="col-lg-6">
        <input type="text" name="PMO_JETON" value="{$pmo_jeton|escape:'html':'UTF-8'}">
        <p class="help-block">Genere dans votre espace vendeur : https://piecemotooccasion.eu/a2/vendeurs/boutique</p>
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-lg-3">Categorie par defaut</label>
      <div class="col-lg-6">
        <input type="text" name="PMO_CATEGORIE" value="{$pmo_categorie|escape:'html':'UTF-8'}">
        <p class="help-block">Utilisee quand un produit n'a pas de categorie PrestaShop : Carénage, Selle, Jante…</p>
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-lg-3">Notification des commandes</label>
      <div class="col-lg-6"><p class="help-block">Copiez cette URL dans votre espace vendeur PieceMotoOccasion : <code>{$pmo_webhook|escape:'html':'UTF-8'}</code></p></div>
    </div>
    <div class="panel-footer">
      <button type="submit" name="pmo_enregistrer" class="btn btn-default"><i class="process-icon-save"></i> Enregistrer</button>
      <button type="submit" name="pmo_synchroniser" class="btn btn-primary">Envoyer tout le catalogue a PieceMotoOccasion</button>
    </div>
  </form>
  <p>Les produits actifs avec une reference sont envoyes ; les nouveaux sont valides par PieceMotoOccasion avant mise en ligne. Ensuite chaque modification et chaque changement de stock sont envoyes automatiquement.</p>
</div>
<div class="panel">
  <h3>Dernieres commandes PieceMotoOccasion</h3>
  <table class="table">
    <thead><tr><th>Commande</th><th>Client</th><th>Total</th><th>Date</th></tr></thead>
    <tbody>
      {foreach from=$pmo_commandes item=c}
        <tr><td>#{$c.id|intval}</td><td>{$c.client|escape:'html':'UTF-8'}</td><td>{$c.total|escape:'html':'UTF-8'} EUR</td><td>{$c.date|escape:'html':'UTF-8'}</td></tr>
      {foreachelse}
        <tr><td colspan="4">Aucune commande pour le moment.</td></tr>
      {/foreach}
    </tbody>
  </table>
  <p>Le detail (lignes, adresse, personnalisation) vous est envoye par email et reste consultable dans votre espace vendeur PieceMotoOccasion.</p>
</div>
