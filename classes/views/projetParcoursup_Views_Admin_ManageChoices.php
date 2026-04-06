<?php
/**
 * Vue Admin : Gérer les formations (Choices) d'une campagne
 */

if (!defined('ABSPATH')) {
    exit;
}

// On s'assure d'avoir la campagne
if (!isset($campaign) || empty($campaign)) {
    echo '<div class="wrap"><h1>Erreur</h1><p>Campagne introuvable.</p></div>';
    return;
}

// Juste besoin de ça pour l'affichage visuel des boutons grisés
$crud_votes = new projetParcoursup_Crud_Votes();
?>

<div class="wrap">
    <h1>Gérer les formations : <?php echo esc_html($campaign->name_campaign); ?></h1>
    <hr>

    <?php if (isset($_GET['message'])) : ?>
        <?php if ($_GET['message'] === 'added') : ?>
            <div class="updated notice is-dismissible"><p>Formation ajoutée avec succès !</p></div>
        <?php elseif ($_GET['message'] === 'deleted') : ?>
            <div class="updated notice is-dismissible"><p>Formation supprimée avec succès !</p></div>
        <?php elseif ($_GET['message'] === 'error_used') : ?>
            <div class="error notice is-dismissible">
                <p><strong>Erreur :</strong> Impossible de supprimer car des étudiants l'ont choisie.</p>
            </div>
        <?php elseif ($_GET['message'] === 'error_duplicate') : ?>
            <div class="error notice is-dismissible">
                <p><strong>Erreur :</strong> Cette formation existe déjà dans cette campagne !</p>
            </div>
        <?php endif; ?>
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
                <a href="<?php echo esc_url(admin_url('admin.php?page=pp-admin')); ?>" class="button">Retour à la liste</a>
            </p>
        </form>
    </div>

    <h2 style="margin-top: 30px;">Formations proposées dans cette campagne</h2>
    <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom de la formation</th>
                <th style="width: 150px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($choices)) : ?>
                <?php foreach ($choices as $choice) : ?>
                    <?php $is_used = $crud_votes->count_votes_by_choice($choice->id_choice) > 0; ?>
                    <tr>
                        <td><?php echo esc_html($choice->id_choice); ?></td>
                        <td><strong><?php echo esc_html($choice->name_choice); ?></strong></td>
                        <td>
                            <?php if ($is_used) : ?>
                                <span style="color: #a0a5aa; cursor: not-allowed;" title="Des étudiants ont déjà choisi cette formation.">
                                    En cours d'utilisation
                                </span>
                            <?php else : ?>
                                <?php
                                $delete_url = wp_nonce_url(
                                    add_query_arg(['delete_id' => $choice->id_choice]),
                                    'delete_choice_' . $choice->id_choice
                                );
                                ?>
                                <a href="<?php echo esc_url($delete_url); ?>" style="color: #d63638;" onclick="return confirm('Sûr de vouloir supprimer ?');">
                                    Supprimer
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 20px;">Aucune formation enregistrée.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>