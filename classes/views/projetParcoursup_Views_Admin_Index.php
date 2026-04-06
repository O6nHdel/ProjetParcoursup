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
                <?php 
                // On récupère la date actuelle (sans l'heure pour comparer les jours)
                $today = date('Y-m-d'); 
                ?>
                <?php foreach ($campaigns as $campaign) : ?>
                    <?php 
                    // LOGIQUE DE STATUT DYNAMIQUE
                    // Une campagne est réellement active si elle est cochée ET que les dates sont valides
                    $is_in_dates = ($today >= $campaign->start_date && $today <= $campaign->end_date);
                    $is_really_active = ($campaign->is_activated && $is_in_dates);
                    ?>
                    <tr>
                        <td><?php echo $campaign->id_campaign; ?></td>
                        <td><strong><?php echo esc_html($campaign->name_campaign); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($campaign->start_date)); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($campaign->end_date)); ?></td>
                        <td>
                            <?php if ($is_really_active) : ?>
                                <span style="color:green; font-weight:bold;">Active</span>
                            <?php elseif (!$campaign->is_activated) : ?>
                                <span style="color:gray;">Désactivée (Admin)</span>
                            <?php elseif ($today < $campaign->start_date) : ?>
                                <span style="color:blue;">À venir</span>
                            <?php else : ?>
                                <span style="color:red; font-weight:bold;">Terminée</span>
                            <?php endif; ?>
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
                    <td colspan="7">Aucune campagne trouvée.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>