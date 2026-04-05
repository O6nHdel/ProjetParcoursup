<div class="wrap">
    <h1>Gérer les formations : <?php echo esc_html($campaign->name_campaign); ?></h1>
    <hr>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'added') : ?>
        <div class="updated"><p>Formation ajoutée avec succès !</p></div>
    <?php endif; ?>

    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px;">
        <h2>Ajouter une formation / spécialité</h2>
        <form method="post" action="">
            <?php wp_nonce_field('pp_add_choice_action', 'pp_add_choice_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="name_choice">Nom de la formation</label></th>
                    <td>
                        <input name="name_choice" type="text" id="name_choice" value="" class="regular-text" placeholder="Ex: BTS SIO - Option SLAM" required>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_choice" id="submit" class="button button-primary" value="Ajouter à cette campagne">
                <a href="?page=pp-admin" class="button">Retour à la liste</a>
            </p>
        </form>
    </div>

    <h2 style="margin-top: 30px;">Formations proposées dans cette campagne</h2>
    <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom de la formation</th>
                <th style="width: 100px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($choices)) : ?>
                <?php foreach ($choices as $choice) : ?>
                    <tr>
                        <td><?php echo $choice->id_choice; ?></td>
                        <td><strong><?php echo esc_html($choice->name_choice); ?></strong></td>
                        <td>
                            <a href="#" style="color:red;">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="3">Aucune formation enregistrée pour cette campagne.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>