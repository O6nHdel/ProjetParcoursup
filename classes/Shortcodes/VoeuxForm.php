<?php

class PP_Shortcodes_VoeuxForm {

    public function __construct() {
        add_shortcode('parcoursup_form', [$this, 'render_form']);
    }

    public function render_form() {
        global $wpdb;
        $campaign_crud = new PP_Crud_Campaign();
        $campaigns = $campaign_crud->get_all();
        
        if (empty($campaigns)) {
            return "<p>Aucune campagne n'est ouverte pour le moment.</p>";
        }

        $current = $campaigns[0];
        $choices = $campaign_crud->get_choices($current->id_campaign);
        $user_id = get_current_user_id();

        // --- 1. TRAITEMENT DE LA SAUVEGARDE ---
        if (isset($_POST['submit_voeux']) && $user_id > 0) {
            $table_votes = $wpdb->prefix . 'ps_student_choices';

            // Suppression des anciens vœux pour cet étudiant (id_stc)
            $wpdb->delete($table_votes, [
                'id_stc' => $user_id
            ]);

            // Insertion des 3 vœux
            for ($i = 1; $i <= 3; $i++) {
                $choice_id = isset($_POST['voeu' . $i]) ? intval($_POST['voeu' . $i]) : 0;
                
                if ($choice_id > 0) {
                    $wpdb->insert($table_votes, [
                        'id_stc'       => $user_id,      // Ta colonne 2
                        'id_choice'    => $choice_id,   // Ta colonne 3
                        'choice_order' => $i            // Ta colonne 4
                    ]);
                }
            }
            echo '<div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px; border:1px solid #c3e6cb;">
                    ✅ Vos vœux ont été enregistrés avec succès !
                  </div>';
        }

        // --- 2. AFFICHAGE DU FORMULAIRE ---
        ob_start(); 
        ?>
        <div class="pp-form-wrapper" style="background:#f9f9f9; padding:20px; border:1px solid #ddd; border-radius: 8px;">
            <h3>Candidature : <?php echo esc_html($current->name_campaign); ?></h3>
            
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
        return ob_get_clean(); 
    }
}