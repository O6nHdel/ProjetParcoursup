<?php

class PP_Shortcodes_VoeuxForm {

    public function __construct() {
        add_shortcode('parcoursup_form', [$this, 'render_form']);
    }

    public function render_form() {
        global $wpdb;

        // 1. SÉCURITÉ : Vérifier la connexion [cite: 11]
        if (!is_user_logged_in()) {
            $login_url = home_url('/index.php/connexion/');
            return '<div class="pp-form-wrapper" style="text-align:center; padding:30px; background:#f9f9f9; border:1px solid #ddd; border-radius:8px;">
                        <h3 style="color:#2271b1; margin-top:0;">Accès restreint</h3>
                        <p>Vous devez posséder un compte étudiant pour formuler vos vœux.</p>
                        <a href="' . esc_url($login_url) . '" style="background: #2271b1; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px; display: inline-block; margin-top: 15px; font-weight: bold;">Se connecter / S\'inscrire</a>
                    </div>';
        }

        $user_id = get_current_user_id();
        $campaign_crud = new PP_Crud_Campaign();
        $table_stc = $wpdb->prefix . 'ps_student_to_campaign';

        // --- TRAITEMENT DE L'INSCRIPTION (POST) [cite: 28] ---
        if (isset($_POST['register_now'])) {
            $id_c = intval($_POST['id_campaign_to_reg']);
            
            // Vérifier si déjà inscrit pour éviter les doublons
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_stc WHERE id_student = %d AND id_campaign = %d",
                $user_id, $id_c
            ));

            if ($exists == 0) {
                $wpdb->insert($table_stc, [
                    'id_student' => $user_id,
                    'id_campaign' => $id_c,
                    'status_candidate' => 'en_attente'
                ]);
                $new_id = $wpdb->insert_id;
                echo "<script>window.location.href='".add_query_arg('stc_id', $new_id)."';</script>";
                exit;
            }
        }

        // 2. RÉCUPÉRATION DES DONNÉES [cite: 32, 33]
        $today = date('Y-m-d');
        $all_active_campaigns = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}ps_campaigns 
            WHERE start_date <= %s AND end_date >= %s AND is_activated = 1
        ", $today, $today));

        $my_registrations = $wpdb->get_results($wpdb->prepare("
            SELECT id_campaign, id_stc FROM $table_stc WHERE id_student = %d
        ", $user_id), OBJECT_K);

        $selected_stc_id = isset($_GET['stc_id']) ? intval($_GET['stc_id']) : 0;

        ob_start();

        if (empty($all_active_campaigns)) {
            echo "<div class='notice notice-info'><p>Aucune campagne n'est ouverte pour le moment.</p></div>";
            return ob_get_clean();
        }

        // 3. DASHBOARD : SÉLECTION OU INSCRIPTION [cite: 30, 31]
        if ($selected_stc_id === 0) {
            ?>
            <div class="pp-dashboard" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px;">
                <h2 style="margin-top:0;">📋 Vos Campagnes de Vœux</h2>
                <p>Sélectionnez une campagne pour formuler ou consulter vos vœux :</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top:20px;">
                    <?php foreach ($all_active_campaigns as $camp) : 
                        $is_registered = isset($my_registrations[$camp->id_campaign]);
                        ?>
                        <div style="padding:20px; border:2px solid #2271b1; border-radius:8px; background:<?php echo $is_registered ? '#f0f6fb' : '#fff'; ?>; text-align:center;">
                            <strong style="font-size:1.1em; display:block; margin-bottom:15px;"><?php echo esc_html($camp->name_campaign); ?></strong>
                            
                            <?php if ($is_registered) : ?>
                                <a href="<?php echo add_query_arg('stc_id', $my_registrations[$camp->id_campaign]->id_stc); ?>" 
                                   style="display:block; background:#2271b1; color:#fff; padding:10px; border-radius:4px; text-decoration:none; font-weight:bold;">
                                   Accéder aux choix
                                </a>
                            <?php else : ?>
                                <form method="post">
                                    <input type="hidden" name="id_campaign_to_reg" value="<?php echo $camp->id_campaign; ?>">
                                    <button type="submit" name="register_now" 
                                            style="width:100%; background:#46b450; color:#fff; border:none; padding:10px; border-radius:4px; cursor:pointer; font-weight:bold;">
                                        M'inscrire et participer
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        // 4. FORMULAIRE POUR UNE CAMPAGNE SPÉCIFIQUE [cite: 15, 18]
        $id_stc = $selected_stc_id;
        $registration_data = $wpdb->get_row($wpdb->prepare(
            "SELECT c.* FROM {$wpdb->prefix}ps_campaigns c 
             JOIN $table_stc stc ON c.id_campaign = stc.id_campaign 
             WHERE stc.id_stc = %d AND stc.id_student = %d", 
            $id_stc, $user_id
        ));

        if (!$registration_data) return "<p>Erreur : Accès non autorisé ou campagne introuvable.</p>";

        $id_campaign = $registration_data->id_campaign;
        $table_votes = $wpdb->prefix . 'ps_student_choices';
        $table_choices_name = $wpdb->prefix . 'ps_choices';

        // --- SAUVEGARDE DES VŒUX (SÉCURISÉE PHP) ---
        if (isset($_POST['submit_voeux'])) {
            $v1 = intval($_POST['voeu1']);
            $v2 = intval($_POST['voeu2']);
            $v3 = intval($_POST['voeu3']);

            $choix_soumis = array($v1, $v2, $v3);
            $unique_check = array_unique(array_filter($choix_soumis));

            if (count($unique_check) === 3) { // [cite: 18, 53]
                $wpdb->delete($table_votes, array('id_stc' => $id_stc));
                foreach ($choix_soumis as $index => $id_choice) {
                    $wpdb->insert($table_votes, array(
                        'id_stc'       => $id_stc,
                        'id_choice'    => $id_choice,
                        'choice_order' => $index + 1 // Ordre strict [cite: 18, 54]
                    ));
                }
                echo "<script>window.location.href='".remove_query_arg('stc_id')."';</script>";
                exit;
            } else {
                echo '<div style="color:red; margin-bottom:10px;">⚠️ Erreur : Sélectionnez 3 formations uniques.</div>';
            }
        }

        // --- RÉCUPÉRATION DES VŒUX [cite: 35, 39] ---
        $mes_voeux = $wpdb->get_results($wpdb->prepare(
            "SELECT v.choice_order, c.name_choice FROM $table_votes v
             INNER JOIN $table_choices_name c ON v.id_choice = c.id_choice
             WHERE v.id_stc = %d ORDER BY v.choice_order ASC", $id_stc
        ));

        ?>
        <div class="pp-form-wrapper" style="background:#f9f9f9; padding:20px; border:1px solid #ddd; border-radius: 8px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0;">Campagne : <?php echo esc_html($registration_data->name_campaign); ?></h3>
                <a href="<?php echo remove_query_arg('stc_id'); ?>" style="font-size:0.9em; text-decoration:none;">⬅️ Retour</a>
            </div>

            <?php if (!empty($mes_voeux)) : //[cite: 25]?>
                <p style="color: #46b450; font-weight: bold;">Statut : Choix Validés ✅</p>
                <div style="background: white; padding: 15px; border: 1px solid #eee; border-radius: 5px;">
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach($mes_voeux as $voeu): ?>
                            <li style="padding: 10px; border-bottom: 1px solid #f1f1f1;">
                                <strong>Vœu n°<?php echo $voeu->choice_order; ?> :</strong> <?php echo esc_html($voeu->name_choice); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else : ?>
                <?php $choices = $campaign_crud->get_choices($id_campaign); //[cite: 37]?>
                <form id="parcoursup-voeu-form" method="post">
                    <p>
                        <label><strong>Vœu n°1 :</strong></label>
                        <select name="voeu1" id="voeu1" style="width:100%; padding: 8px;" required>
                            <option value="">-- Choisir une formation --</option>
                            <?php foreach($choices as $c): ?>
                                <option value="<?php echo $c->id_choice; ?>"><?php echo esc_html($c->name_choice); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label><strong>Vœu n°2 :</strong></label>
                        <select name="voeu2" id="voeu2" style="width:100%; padding: 8px;" disabled required></select>
                    </p>
                    <p>
                        <label><strong>Vœu n°3 :</strong></label>
                        <select name="voeu3" id="voeu3" style="width:100%; padding: 8px;" disabled required></select>
                    </p>
                    <input type="submit" name="submit_voeux" value="Valider mes vœux" class="button button-primary" style="background:#2271b1; color:#fff; border:none; padding:10px 20px; border-radius:4px; cursor:pointer;">
                </form>

                <script>
                jQuery(document).ready(function($) { 
                    function updateCascade(current, next, exclude1, exclude2) {
                        var val = $(current).val();
                        if (val !== "") {
                            $(next).prop('disabled', false).empty().append('<option value="">-- Choisir le vœu suivant --</option>');
                            $("#voeu1 option").each(function() {
                                var optionVal = $(this).val();
                                // Filtrer : pas vide, pas la valeur actuelle, pas les valeurs précédentes
                                if (optionVal !== "" && optionVal !== val && optionVal !== exclude1 && optionVal !== exclude2) {
                                    $(this).clone().appendTo(next);
                                }
                            });
                        } else {
                            $(next).prop('disabled', true).val("").empty();
                            if(current === '#voeu1') $('#voeu3').prop('disabled', true).val("").empty();
                        }
                    }

                    $('#voeu1').on('change', function() { 
                        // On réinitialise aussi le vœu 3 si le 1 change
                        updateCascade('#voeu1', '#voeu2', null, null); 
                        $('#voeu3').prop('disabled', true).val("").empty();
                    });
                    
                    $('#voeu2').on('change', function() { 
                        updateCascade('#voeu2', '#voeu3', $('#voeu1').val(), null); 
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}