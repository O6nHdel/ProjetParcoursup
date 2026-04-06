<?php

class projetParcoursup_Shortcodes_VoeuxForm {

    public function __construct() {
        add_shortcode('parcoursup_form', [$this, 'render_form']);
        // On déclare le chargement du script JS et CSS
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_assets() {
        // Chargement du JS (Cascade des vœux)
        wp_enqueue_script(
            'ps-voeux-js', 
            plugin_dir_url(dirname(__DIR__)) . 'assets/js/parcoursup-form.js', 
            array('jquery'), 
            '1.0', 
            true
        );

        // Chargement du CSS (Utilisation de time() pour bypass le cache navigateur en dev)
        wp_enqueue_style(
            'pp-style', 
            plugin_dir_url(dirname(__DIR__)) . 'assets/css/style.css', 
            array(), 
            time() 
        );
    }

    public function render_form() {
        global $wpdb;

        // 1. SÉCURITÉ : Vérifier la connexion
        if (!is_user_logged_in()) {
            $login_url = home_url('/index.php/connexion/');
            return '<div class="pp-form-wrapper" style="text-align:center;">
                        <div class="header-section">
                            <h3>Accès restreint</h3>
                            <p>Vous devez posséder un compte étudiant pour formuler vos vœux.</p>
                            <a href="' . esc_url($login_url) . '" class="button-primary" style="display:inline-block; width:auto; padding:15px 30px; text-decoration:none;">Se connecter / S\'inscrire</a>
                        </div>
                    </div>';
        }

        $user_id = get_current_user_id();
        $current_user = wp_get_current_user(); // Récupération des infos de l'étudiant
        
        // CORRECTION ICI : Utilisation du nouveau nom de la classe CRUD
        $campaign_crud = new projetParcoursup_Crud_Campaign();
        
        $table_stc = $wpdb->prefix . 'ps_student_to_campaign';

        // --- TRAITEMENT DE L'INSCRIPTION (POST) ---
        if (isset($_POST['register_now'])) {
            $id_c = intval($_POST['id_campaign_to_reg']);
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_stc WHERE id_student = %d AND id_campaign = %d", $user_id, $id_c));

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

        // 2. RÉCUPÉRATION DES DONNÉES GÉNÉRALES
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

        // --- DÉBUT DU RENDU HTML ---
        echo '<div class="pp-form-wrapper">'; 

        // --- BLOC HEADER ÉTUDIANT (UX PERSONNALISÉE) ---
        ?>
        <div class="student-header">
            <div class="student-info">
                <span class="avatar-circle"><?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?></span>
                <div class="student-text">
                    <span class="welcome-msg">Espace Candidat</span>
                    <span class="student-name"><?php echo esc_html($current_user->display_name); ?></span>
                </div>
            </div>
            <a href="<?php echo wp_logout_url(home_url('/index.php/connexion/')); ?>" class="logout-link">
                Déconnexion 👋
            </a>
        </div>
        <?php

        if (empty($all_active_campaigns)) {
            echo "<div class='header-section'><h3>Aucune campagne ouverte</h3><p>Revenez plus tard pour consulter les sessions de vœux.</p></div>";
        } 
        elseif ($selected_stc_id === 0) {
            // --- VUE A : DASHBOARD (LISTE DES CAMPAGNES) ---
            ?>
            <div class="header-section">
                <h3>📋 Vos Campagnes de Vœux</h3>
                <p>Inscrivez-vous à une session ou accédez à vos choix en cours.</p>
            </div>

            <div class="pp-dashboard">
                <?php foreach ($all_active_campaigns as $camp) : 
                    $is_registered = isset($my_registrations[$camp->id_campaign]);
                ?>
                    <div class="campaign-card">
                        <strong><?php echo esc_html($camp->name_campaign); ?></strong>
                        <?php if ($is_registered) : ?>
                            <a href="<?php echo add_query_arg('stc_id', $my_registrations[$camp->id_campaign]->id_stc); ?>" class="button-primary" style="display:flex; align-items:center; justify-content:center; text-decoration:none;">
                               Accéder aux choix
                            </a>
                        <?php else : ?>
                            <form method="post">
                                <input type="hidden" name="id_campaign_to_reg" value="<?php echo $camp->id_campaign; ?>">
                                <button type="submit" name="register_now" class="button-primary">
                                    M'inscrire
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        } else {
            // --- VUE B : FORMULAIRE DE VŒUX SPÉCIFIQUE ---
            $id_stc = $selected_stc_id;
            $registration_data = $wpdb->get_row($wpdb->prepare("
                SELECT c.* FROM {$wpdb->prefix}ps_campaigns c 
                JOIN $table_stc stc ON c.id_campaign = stc.id_campaign 
                WHERE stc.id_stc = %d AND stc.id_student = %d", 
                $id_stc, $user_id
            ));

            if (!$registration_data) {
                echo "<div class='header-section'><h3>Erreur d'accès</h3><p>Cette campagne n'existe pas ou vous n'y êtes pas inscrit.</p></div>";
            } else {
                $id_campaign = $registration_data->id_campaign;

                // --- SAUVEGARDE DES VŒUX (TRAITEMENT POST) ---
                if (isset($_POST['submit_voeux'])) {
                    $choix_soumis = array(intval($_POST['voeu1']), intval($_POST['voeu2']), intval($_POST['voeu3']));
                    if (count(array_unique(array_filter($choix_soumis))) === 3) {
                        $wpdb->delete($wpdb->prefix . 'ps_student_choices', array('id_stc' => $id_stc));
                        foreach ($choix_soumis as $index => $id_choice) {
                            $wpdb->insert($wpdb->prefix . 'ps_student_choices', [
                                'id_stc' => $id_stc, 
                                'id_choice' => $id_choice, 
                                'choice_order' => $index + 1
                            ]);
                        }
                        echo "<script>window.location.href='".remove_query_arg('stc_id')."';</script>";
                        exit;
                    } else {
                        echo '<div style="color:#ef4444; margin-bottom:20px; font-weight:bold; text-align:center;">⚠️ Erreur : Veuillez sélectionner 3 formations différentes.</div>';
                    }
                }

                $mes_voeux = $wpdb->get_results($wpdb->prepare("
                    SELECT v.choice_order, c.name_choice 
                    FROM {$wpdb->prefix}ps_student_choices v 
                    INNER JOIN {$wpdb->prefix}ps_choices c ON v.id_choice = c.id_choice 
                    WHERE v.id_stc = %d ORDER BY v.choice_order ASC", 
                    $id_stc
                ));
                ?>

                <div class="header-section">
                    <h3>Campagne : <?php echo esc_html($registration_data->name_campaign); ?></h3>
                    <a href="<?php echo remove_query_arg('stc_id'); ?>" style="text-decoration:none; color:#64748b; font-size:0.9rem; font-weight:600;">⬅️ Retour au tableau de bord</a>
                </div>

                <div id="parcoursup-voeu-form">
                    <?php if (!empty($mes_voeux)) : ?>
                        <div style="text-align:center; margin-bottom:20px;">
                            <span class="status-badge">Choix Validés ✅</span>
                        </div>
                        <div class="voeux-valides-list" style="background:#f8fafc; padding:20px; border-radius:12px;">
                            <ul style="list-style: none; padding: 0; margin:0;">
                                <?php foreach($mes_voeux as $voeu): ?>
                                    <li style="padding:15px 0; border-bottom: 1px solid rgba(0,0,0,0.05); color:#1e293b;">
                                        <strong>Vœu n°<?php echo $voeu->choice_order; ?> :</strong> <?php echo esc_html($voeu->name_choice); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else : ?>
                        <?php $choices = $campaign_crud->get_choices($id_campaign); ?>
                        <form method="post">
                            <p>
                                <label>Vœu n°1 (Choix prioritaire)</label>
                                <select name="voeu1" id="voeu1" required>
                                    <option value="">-- Sélectionner une formation --</option>
                                    <?php foreach($choices as $c): ?>
                                        <option value="<?php echo $c->id_choice; ?>"><?php echo esc_html($c->name_choice); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                            <p>
                                <label>Vœu n°2</label>
                                <select name="voeu2" id="voeu2" disabled required>
                                    <option value="">-- En attente du vœu 1 --</option>
                                </select>
                            </p>
                            <p>
                                <label>Vœu n°3</label>
                                <select name="voeu3" id="voeu3" disabled required>
                                    <option value="">-- En attente du vœu 2 --</option>
                                </select>
                            </p>
                            <button type="submit" name="submit_voeux" class="button-primary">
                                Envoyer mes vœux
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php
            }
        }

        echo '</div>'; // FERMETURE DU WRAPPER PRINCIPAL
        
        return ob_get_clean();
    }
}