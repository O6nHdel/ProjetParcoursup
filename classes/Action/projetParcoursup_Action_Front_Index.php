<?php

class projetParcoursup_Action_Front_Index {

    public function __construct() {
        if (!is_admin()) {
            add_action('template_redirect', [$this, 'restrict_access']);
        }
    }

    /**
     * Redirige vers la page de connexion si on tente d'accéder au formulaire de vœux
     */
    public function restrict_access() {
        if (!is_user_logged_in()) {
            global $post;
            
            // Si on est sur une page qui contient le shortcode [parcoursup_form]
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'parcoursup_form')) {
                // Redirection vers ta page de connexion personnalisée
                wp_redirect(home_url('/index.php/connexion/'), 302);
                exit;
            }
        }
    }
}