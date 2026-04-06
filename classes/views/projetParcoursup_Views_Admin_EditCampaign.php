<div class="wrap">
    <h1>Modifier la campagne : <?php echo esc_html($campaign->name_campaign); ?></h1>
    <form method="post" action="">
        <?php wp_nonce_field('pp_edit_campaign_action', 'pp_edit_campaign_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="name">Nom de la campagne</label></th>
                <td><input name="name" type="text" id="name" value="<?php echo esc_attr($campaign->name_campaign); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="start_date">Date de début</label></th>
                <td><input name="start_date" type="date" id="start_date" value="<?php echo $campaign->start_date; ?>" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="end_date">Date de fin</label></th>
                <td><input name="end_date" type="date" id="end_date" value="<?php echo $campaign->end_date; ?>" required></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="update_campaign" id="submit" class="button button-primary" value="Mettre à jour">
            <a href="?page=pp-admin" class="button">Annuler</a>
        </p>
    </form>
</div>