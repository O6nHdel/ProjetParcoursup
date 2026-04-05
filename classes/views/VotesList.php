<?php
// On récupère les données via le Crud
$crud = new PP_Crud_Votes();
$all_votes = $crud->get_all_votes();

// On organise les données par étudiant
$data = [];
foreach ($all_votes as $v) {
    $data[$v->student_name][$v->choice_order] = $v->formation_name;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">📊 Récapitulatif des Vœux Étudiants</h1>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped" style="margin-top:20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <thead>
            <tr>
                <th style="font-weight: bold; width: 25%;">Nom de l'Étudiant</th>
                <th>Vœu n°1</th>
                <th>Vœu n°2</th>
                <th>Vœu n°3</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $student => $choices): ?>
                    <tr>
                        <td><strong><?php echo esc_html($student); ?></strong></td>
                        <td><?php echo isset($choices[1]) ? esc_html($choices[1]) : '<small style="color:#ccc;">non renseigné</small>'; ?></td>
                        <td><?php echo isset($choices[2]) ? esc_html($choices[2]) : '<small style="color:#ccc;">non renseigné</small>'; ?></td>
                        <td><?php echo isset($choices[3]) ? esc_html($choices[3]) : '<small style="color:#ccc;">non renseigné</small>'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center; padding: 20px;">Aucun vœu n'a encore été enregistré en base de données.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>