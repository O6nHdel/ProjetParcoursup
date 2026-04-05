<?php
/**
 * Vue Admin : Liste des Vœux et Export CSV
 */

// 1. INITIALISATION DES DONNÉES
$crud = new PP_Crud_Votes();
$campaign_crud = new PP_Crud_Campaign();

// Récupération de la campagne sélectionnée pour le filtre
$selected_campaign = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$campaigns = $campaign_crud->get_all();

// On récupère les votes depuis le CRUD (filtrés ou non)
$all_votes = $crud->get_all_votes($selected_campaign);

// 2. GESTION DE L'EXPORT CSV
// Ce bloc doit impérativement s'exécuter avant tout affichage HTML
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    
    // Nettoyage des tampons de sortie pour éviter les caractères parasites
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Configuration des headers pour forcer le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export_voeux_parcoursup_' . date('d-m-Y') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    
    // Ajout du BOM UTF-8 pour la compatibilité des accents dans Excel France
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // En-tête du fichier CSV (Séparateur point-virgule pour Excel FR)
    fputcsv($output, ['Etudiant', 'Voeu 1', 'Voeu 2', 'Voeu 3'], ';');

    // On organise les données pour l'export (Regroupement par étudiant)
    $export_data = [];
    foreach ($all_votes as $v) {
        $export_data[$v->student_name][$v->choice_order] = $v->formation_name;
    }

    // Écriture des lignes
    foreach ($export_data as $student => $choices) {
        fputcsv($output, [
            $student, 
            isset($choices[1]) ? $choices[1] : '-', 
            isset($choices[2]) ? $choices[2] : '-', 
            isset($choices[3]) ? $choices[3] : '-'
        ], ';');
    }
    
    fclose($output);
    exit; // Arrêt brutal pour ne pas charger le HTML de WordPress dans le CSV
}

// 3. PRÉPARATION DES DONNÉES POUR L'AFFICHAGE TABLEAU
$display_data = [];
foreach ($all_votes as $v) {
    $display_data[$v->student_name][$v->choice_order] = $v->formation_name;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">📊 Récapitulatif des Vœux Étudiants</h1>
    
    <a href="admin.php?page=pp-votes-list&action=export_csv&campaign_id=<?php echo $selected_campaign; ?>" class="page-title-action">
    📥 Exporter en CSV
    </a>
    
    <hr class="wp-header-end">

    <div class="tablenav top" style="margin-top: 20px; background: #fff; padding: 10px; border: 1px solid #ccd0d4; border-radius: 4px;">
        <form method="get" action="<?php echo admin_url('admin.php'); ?>">
            <input type="hidden" name="page" value="pp-votes-list">
            
            <label for="campaign_id" style="font-weight: bold; margin-right: 10px;">Filtrer par campagne :</label>
            <select name="campaign_id" id="campaign_id" style="min-width: 200px;">
                <option value="0">--- Toutes les campagnes ---</option>
                <?php foreach ($campaigns as $camp): ?>
                    <option value="<?php echo $camp->id_campaign; ?>" <?php selected($selected_campaign, $camp->id_campaign); ?>>
                        <?php echo esc_html($camp->name_campaign); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="submit" class="button action" value="Filtrer les vœux">
            
            <?php if ($selected_campaign > 0): ?>
                <a href="<?php echo admin_url('admin.php?page=pp-votes-list'); ?>" class="button">Réinitialiser</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped" style="margin-top:20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <thead>
            <tr>
                <th style="font-weight: bold; width: 25%; padding: 12px;">Nom de l'Étudiant</th>
                <th style="font-weight: bold;">Vœu n°1</th>
                <th style="font-weight: bold;">Vœu n°2</th>
                <th style="font-weight: bold;">Vœu n°3</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($display_data)): ?>
                <?php foreach ($display_data as $student => $choices): ?>
                    <tr>
                        <td style="padding: 12px;"><strong><?php echo esc_html($student); ?></strong></td>
                        <td><?php echo isset($choices[1]) ? esc_html($choices[1]) : '<small style="color:#ccc;">non renseigné</small>'; ?></td>
                        <td><?php echo isset($choices[2]) ? esc_html($choices[2]) : '<small style="color:#ccc;">non renseigné</small>'; ?></td>
                        <td><?php echo isset($choices[3]) ? esc_html($choices[3]) : '<small style="color:#ccc;">non renseigné</small>'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; background: #fdfdfd; color: #666;">
                        <span style="font-size: 30px;">🔍</span><br>
                        Aucun vœu trouvé pour cette sélection.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>