<div class="wrap">
    <h1>Gestion des Campagnes</h1>
    <hr>

    <div class="action-bar" style="margin-bottom: 20px;">
        <a href="?page=pp-admin&action=add" class="button button-primary">Ajouter une campagne</a>
        
        <a href="?page=pp-votes-list" class="button button-secondary" style="margin-left: 10px;">📊 Voir les vœux</a>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom de la campagne</th>
                <th>Date de début</th>
                <th>Date de fin</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($campaigns)) : ?>
                <?php foreach ($campaigns as $campaign) : ?>
                    <tr>
                        <td><?php echo $campaign->id_campaign; ?></td>
                        <td><strong><?php echo esc_html($campaign->name_campaign); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($campaign->start_date)); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($campaign->end_date)); ?></td>
                        <td>
                            <?php echo ($campaign->is_activated) ? '<span style="color:green;">Active</span>' : 'Inactive'; ?>
                        </td>
                        <td>
                            <a href="?page=pp-admin&action=manage_choices&id=<?php echo $campaign->id_campaign; ?>">Gérer les formations</a> | 
                            <a href="?page=pp-admin&action=edit&id=<?php echo $campaign->id_campaign; ?>">Modifier</a> | 
                            <a href="?page=pp-admin&action=delete&id=<?php echo $campaign->id_campaign; ?>" 
                                style="color:red;" 
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette campagne ?');">
                                Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6">Aucune campagne trouvée.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>