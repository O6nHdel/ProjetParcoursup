<div class="wrap">
    <h1>Ajouter une nouvelle campagne</h1>
    <form method="post" action="">
        <?php wp_nonce_field('pp_add_campaign_action', 'pp_add_campaign_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="name">Nom de la campagne</label></th>
                <td><input name="name" type="text" id="name" value="" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="start_date">Date de début</label></th>
                <td><input name="start_date" type="date" id="start_date" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="end_date">Date de fin</label></th>
                <td><input name="end_date" type="date" id="end_date" required></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="submit_campaign" id="submit" class="button button-primary" value="Enregistrer la campagne">
            <a href="?page=pp-admin" class="button">Annuler</a>
        </p>
    </form>
</div>