<?php

class PP_Shortcodes_VoeuxForm {

    public function __construct() {
        add_shortcode('parcoursup_form', [$this, 'render_form']);
    }

    public function render_form() {
        global $wpdb;

        // 1. SÉCURITÉ : Vérifier que l'utilisateur est connecté [cite: 11]
        if (!is_user_logged_in()) {
            return "<p>Vous devez être connecté pour accéder à Parcoursup.</p>";
        }

        $user_id = get_current_user_id();
        $campaign_crud = new PP_Crud_Campaign(); 
        $campaigns = $campaign_crud->get_all();
        
        if (empty($campaigns)) {
            return "<p>Aucune campagne n'est ouverte pour le moment.</p>";
        }

        $current = $campaigns[0];
        $id_campaign = $current->id_campaign;

        // 2. GESTION DE L'INSCRIPTION À LA CAMPAGNE
        $table_stc = $wpdb->prefix . 'ps_student_to_campaign';

        // Si l'étudiant clique sur "M'inscrire"
        if (isset($_POST['register_campaign'])) {
            
            // On insère uniquement les colonnes présentes dans la BDD actuelle
            $wpdb->insert($table_stc, [
                'id_student'       => $user_id,
                'id_campaign'      => $id_campaign,
                'status_candidate' => 'en_attente'
            ]);
            echo '<div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:5px;">✅ Inscription validée ! Vous pouvez faire vos vœux.</div>';
        }

        // Vérifie si l'étudiant est inscrit [cite: 32, 34]
        $registration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_stc WHERE id_student = %d AND id_campaign = %d",
            $user_id,
            $id_campaign
        ));

        ob_start();

        // 3. AFFICHAGE CONDITIONNEL
        if (!$registration) {
            // A. L'ÉTUDIANT N'EST PAS INSCRIT
            ?>
            <div style="background:#f9f9f9; padding:20px; border:1px solid #ddd; border-radius: 8px; text-align:center;">
                <h3>Campagne : <?php echo esc_html($current->name_campaign); ?></h3>
                <p>Vous n'êtes pas encore inscrit à cette campagne. Cliquez ci-dessous pour accéder aux choix.</p>
                <form method="post">
                    <button type="submit" name="register_campaign" class="button button-primary" style="background: #2271b1; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        M'inscrire à la campagne
                    </button>
                </form>
            </div>
            <?php
        } else {
            // B. L'ÉTUDIANT EST INSCRIT
            $choices = $campaign_crud->get_choices($id_campaign);

            // --- TRAITEMENT DE LA SAUVEGARDE DES VŒUX ---
            if (isset($_POST['submit_voeux'])) {
                $table_votes = $wpdb->prefix . 'student_choice'; // MAJ du nom selon ton MLD [cite: 2]

                // On supprime les anciens choix basés sur id_student_to_campaign (l'ID de la table de liaison)
                // Assure-toi que la colonne s'appelle bien id_student_to_campaign dans ta table student_choice
                $wpdb->delete($table_votes, ['id_student_to_campaign' => $registration->id_stc]);

                // Insertion des 3 vœux [cite: 53]
                for ($i = 1; $i <= 3; $i++) {
                    $choice_id = isset($_POST['voeu' . $i]) ? intval($_POST['voeu' . $i]) : 0;
                    if ($choice_id > 0) {
                        $wpdb->insert($table_votes, [
                            'id_student_to_campaign' => $registration->id_stc, // On utilise l'ID de l'inscription
                            'id_choice'    => $choice_id,
                            'choice_order' => $i
                        ]);
                    }
                }
                echo '<div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:5px;">✅ Vos vœux ont été enregistrés avec succès ! [cite: 25]</div>';
            }

            // --- AFFICHAGE DU FORMULAIRE DE VŒUX ---
            ?>
            <div class="pp-form-wrapper" style="background:#f9f9f9; padding:20px; border:1px solid #ddd; border-radius: 8px;">
                <h3>Candidature : <?php echo esc_html($current->name_campaign); ?></h3>
                <p style="color: #46b450; font-weight: bold;">Statut : Inscrit</p>
                
                <form id="parcoursup-voeu-form" method="post">
                    <p>
                        <label><strong>Vœu n°1 :</strong></label><br>
                        <select name="voeu1" id="voeu1" style="width:100%; padding: 8px;" required>
                            <option value="">-- Choisir une formation --</option>
                            <?php foreach($choices as $c): ?>
                                <option value="<?php echo $c->id_choice; ?>"><?php echo esc_html($c->name_choice); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    
                    <p>
                        <label><strong>Vœu n°2 :</strong></label><br>
                        <select name="voeu2" id="voeu2" style="width:100%; padding: 8px;" disabled>
                            <option value="">-- Sélectionnez d'abord le vœu 1 --</option>
                        </select>
                    </p>

                    <p>
                        <label><strong>Vœu n°3 :</strong></label><br>
                        <select name="voeu3" id="voeu3" style="width:100%; padding: 8px;" disabled>
                            <option value="">-- Sélectionnez d'abord le vœu 2 --</option>
                        </select>
                    </p>
                    
                    <p style="margin-top: 20px;">
                        <input type="submit" name="submit_voeux" value="Valider mes vœux" class="button button-primary" style="background: #2271b1; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px;">
                    </p>
                </form>
            </div>

            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script>
            jQuery(document).ready(function($) {
                $('#voeu1').on('change', function() {
                    var val1 = $(this).val();
                    if (val1 !== "") {
                        $('#voeu2').prop('disabled', false).empty().append('<option value="">-- Choisir le vœu 2 --</option>');
                        $("#voeu1 option").each(function() {
                            if ($(this).val() !== "" && $(this).val() !== val1) {
                                $(this).clone().appendTo("#voeu2");
                            }
                        });
                    } else {
                        $('#voeu2, #voeu3').prop('disabled', true).val("");
                    }
                });

                $('#voeu2').on('change', function() {
                    var val1 = $('#voeu1').val();
                    var val2 = $(this).val();
                    if (val2 !== "") {
                        $('#voeu3').prop('disabled', false).empty().append('<option value="">-- Choisir le vœu 3 --</option>');
                        $("#voeu1 option").each(function() {
                            if ($(this).val() !== "" && $(this).val() !== val1 && $(this).val() !== val2) {
                                $(this).clone().appendTo("#voeu3");
                            }
                        });
                    } else {
                        $('#voeu3').prop('disabled', true).val("");
                    }
                });
            });
            </script>
            <?php
        }

        return ob_get_clean(); 
    }
}