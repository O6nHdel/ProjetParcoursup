<?php

class PP_Shortcodes_VoeuxForm {

    public function __construct() {
        add_shortcode('parcoursup_form', [$this, 'render_form']);
    }

    public function render_form() {
        global $wpdb;

        // 1. SÉCURITÉ : Vérifier que l'utilisateur est connecté
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
            $wpdb->insert($table_stc, [
                'id_student'       => $user_id,
                'id_campaign'      => $id_campaign,
                'status_candidate' => 'en_attente'
            ]);
            echo '<div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:5px;">✅ Inscription validée ! Vous pouvez faire vos vœux.</div>';
        }

        // Vérifie si l'étudiant est inscrit
        $registration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_stc WHERE id_student = %d AND id_campaign = %d",
            $user_id,
            $id_campaign
        ));

        ob_start();

        // 3. AFFICHAGE CONDITIONNEL GLOBAL
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
            $table_votes = $wpdb->prefix . 'ps_student_choices'; // NOM DE TABLE CORRIGÉ
            $table_choices_name = $wpdb->prefix . 'ps_choices';

            // --- TRAITEMENT DE LA SAUVEGARDE DES VŒUX ---
            if (isset($_POST['submit_voeux'])) {
                // Suppression des anciens choix
                $wpdb->delete($table_votes, ['id_stc' => $registration->id_stc]); // COLONNE CORRIGÉE

                // Insertion des 3 vœux
                for ($i = 1; $i <= 3; $i++) {
                    $choice_id = isset($_POST['voeu' . $i]) ? intval($_POST['voeu' . $i]) : 0;
                    if ($choice_id > 0) {
                        $wpdb->insert($table_votes, [
                            'id_stc'       => $registration->id_stc, // COLONNE CORRIGÉE
                            'id_choice'    => $choice_id,
                            'choice_order' => $i
                        ]);
                    }
                }
                // Astuce pour forcer la disparition du formulaire immédiatement après le vote
                echo "<script>window.location.reload();</script>";
                exit;
            }

            // --- VÉRIFICATION DES VŒUX EXISTANTS ---
            $mes_voeux = $wpdb->get_results($wpdb->prepare(
                "SELECT v.choice_order, c.name_choice 
                 FROM $table_votes v
                 INNER JOIN $table_choices_name c ON v.id_choice = c.id_choice
                 WHERE v.id_stc = %d 
                 ORDER BY v.choice_order ASC",
                $registration->id_stc // COLONNE CORRIGÉE
            ));

            if (!empty($mes_voeux)) {
                // C1. L'ÉTUDIANT A DÉJÀ VOTÉ : On affiche le récapitulatif
                ?>
                <div class="pp-form-wrapper" style="background:#f9f9f9; padding:20px; border:1px solid #ddd; border-radius: 8px;">
                    <h3>Candidature : <?php echo esc_html($current->name_campaign); ?></h3>
                    <p style="color: #46b450; font-weight: bold;">Statut : Choix Validés ✅</p>
                    
                    <div style="background: white; padding: 15px; border: 1px solid #eee; border-radius: 5px; margin-top: 15px;">
                        <h4 style="margin-top:0;">Récapitulatif de vos vœux définitifs :</h4>
                        <ul style="list-style-type: none; padding-left: 0; font-size: 16px;">
                            <?php foreach($mes_voeux as $voeu): ?>
                                <li style="padding: 10px; border-bottom: 1px solid #f1f1f1;">
                                    <strong>Vœu n°<?php echo $voeu->choice_order; ?> :</strong> <?php echo esc_html($voeu->name_choice); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <p style="font-size: 12px; color: #777; margin-top: 15px;">Vos choix ont été transmis à l'administration et ne sont plus modifiables.</p>
                </div>
                <?php
            } else {
                // C2. L'ÉTUDIANT N'A PAS ENCORE VOTÉ : On affiche le formulaire
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
            } // Fin du else if(!empty($mes_voeux))
        } // Fin du else (!$registration)

        return ob_get_clean(); 
    }
}