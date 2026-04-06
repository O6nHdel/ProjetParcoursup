<?php

class projetParcoursup_Shortcodes_AuthForm {

    public function __construct() {
        add_shortcode('parcoursup_auth', [$this, 'render_form']);
    }

    public function render_form() {
        ob_start();

        // 🎯 L'URL DE TA PAGE DE VŒUX (Modifie le slug entre les guillemets si besoin)
        $url_redirection = home_url('/index.php/mes-voeux/'); 

        // Si l'utilisateur est DÉJÀ connecté
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            echo '<div class="pp-form-wrapper" style="background:#f9f9f9; padding:20px; border:1px solid #ddd; border-radius: 8px; text-align:center;">';
            echo '<h3 style="color:#2271b1; margin-top:0;">Bienvenue, ' . esc_html($current_user->display_name) . '</h3>';
            echo '<p>Vous êtes déjà connecté à votre espace.</p>';
            echo '<a href="' . esc_url($url_redirection) . '" class="button button-primary" style="background: #46b450; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px; display: inline-block; margin-right: 10px;">Accéder à mes vœux</a>';
            echo '<a href="' . wp_logout_url(get_permalink()) . '" style="color: #d63638; text-decoration: underline;">Se déconnecter</a>';
            echo '</div>';
            return ob_get_clean();
        }

        // --- TRAITEMENT DE L'INSCRIPTION ---
        if (isset($_POST['pp_register'])) {
            $username = sanitize_user($_POST['reg_username']);
            $email = sanitize_email($_POST['reg_email']);
            $password = $_POST['reg_password'];

            if (username_exists($username) || email_exists($email)) {
                echo '<div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:4px; margin-bottom:15px;">❌ Ce nom d\'utilisateur ou cet email est déjà pris.</div>';
            } else {
                $user_id = wp_create_user($username, $password, $email);
                if (!is_wp_error($user_id)) {
                    // On connecte le nouvel utilisateur automatiquement
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id);
                    // 🚀 REDIRECTION AUTOMATIQUE VERS LES VŒUX
                    echo '<script>window.location.href="' . $url_redirection . '";</script>';
                    exit;
                }
            }
        }

        // --- TRAITEMENT DE LA CONNEXION ---
        if (isset($_POST['pp_login'])) {
            $creds = [
                'user_login'    => sanitize_user($_POST['log_username']),
                'user_password' => $_POST['log_password'],
                'remember'      => true
            ];

            $user = wp_signon($creds, false);
            if (is_wp_error($user)) {
                echo '<div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:4px; margin-bottom:15px;">❌ Identifiants incorrects.</div>';
            } else {
                // 🚀 REDIRECTION AUTOMATIQUE VERS LES VŒUX
                echo '<script>window.location.href="' . $url_redirection . '";</script>';
                exit;
            }
        }

        // --- AFFICHAGE DES FORMULAIRES ---
        ?>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <div class="pp-form-wrapper" style="flex: 1; min-width: 300px; background:#f9f9f9; padding:20px; border:1px solid #ddd; border-radius: 8px;">
                <h3 style="color:#2271b1; margin-top:0;">Connexion</h3>
                <form method="post">
                    <p>
                        <label>Identifiant</label><br>
                        <input type="text" name="log_username" required style="width: 100%; padding: 8px;">
                    </p>
                    <p>
                        <label>Mot de passe</label><br>
                        <input type="password" name="log_password" required style="width: 100%; padding: 8px;">
                    </p>
                    <p style="margin-bottom:0;">
                        <input type="submit" name="pp_login" value="Se connecter" class="button button-primary" style="background: #2271b1; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px;">
                    </p>
                </form>
            </div>

            <div class="pp-form-wrapper" style="flex: 1; min-width: 300px; background:#f9f9f9; padding:20px; border:1px solid #ddd; border-radius: 8px;">
                <h3 style="color:#2271b1; margin-top:0;">Créer un compte étudiant</h3>
                <form method="post">
                    <p>
                        <label>Identifiant</label><br>
                        <input type="text" name="reg_username" required style="width: 100%; padding: 8px;">
                    </p>
                    <p>
                        <label>Email</label><br>
                        <input type="email" name="reg_email" required style="width: 100%; padding: 8px;">
                    </p>
                    <p>
                        <label>Mot de passe</label><br>
                        <input type="password" name="reg_password" required style="width: 100%; padding: 8px;">
                    </p>
                    <p style="margin-bottom:0;">
                        <input type="submit" name="pp_register" value="S'inscrire" class="button button-primary" style="background: #46b450; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px;">
                    </p>
                </form>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
}