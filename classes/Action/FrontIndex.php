<?php

class PP_Actions_FrontIndex {

    public function __construct() {
        // On ne lance la sécurité que si on n'est pas dans l'admin
        if (!is_admin()) {
            add_action('template_redirect', [$this, 'restrict_access']);
        }
    }

    /**
     * Redirige vers la page de login si l'utilisateur n'est pas connecté
     */
    public function restrict_access() {
        // global $pagenow permet de savoir sur quelle page système on est (ex: wp-login.php)
        global $pagenow;

        // Si l'utilisateur n'est PAS connecté ET qu'il n'est pas déjà sur la page de login
        if (!is_user_logged_in() && $pagenow !== 'wp-login.php') {
            
            // auth_redirect() est une fonction WP qui renvoie vers le login 
            // et redirige l'utilisateur vers sa page d'origine après connexion.
            auth_redirect();
            exit;
        }
    }
}