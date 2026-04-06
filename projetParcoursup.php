<?php
/**
 * Plugin Name: Projet Parcoursup - LP 2025-2026
 * Description: Application de gestion de vœux inspirée de ParcoursSup.
 * Version: 1.0
 * Author: Ton Nom
 */

// Sécurité : empêche l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes de chemin
define('PP_PATH', plugin_dir_path(__FILE__));
define('PP_URL', plugin_dir_url(__FILE__));

/**
 * Autoloader
 */
spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'PP_') !== 0) {
        return;
    }

    $file = str_replace(['PP_', '_'], ['', DIRECTORY_SEPARATOR], $class_name) . '.php';
    $path = PP_PATH . 'classes' . DIRECTORY_SEPARATOR . $file;

    if (file_exists($path)) {
        require_once $path;
    }
});

// --- ⚡ INTERCEPTEUR D'EXPORT (AVANT TOUT AFFICHAGE) ---
// On le place ici pour qu'il s'exécute très tôt dans le cycle de vie de WordPress
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'pp-votes-list' && isset($_GET['action']) && $_GET['action'] === 'export_csv') {
        
        // On charge manuellement les dépendances nécessaires
        require_once PP_PATH . 'classes/Crud/Votes.php';
        require_once PP_PATH . 'classes/Crud/Campaign.php';
        
        $view_file = PP_PATH . 'classes/views/VotesList.php';
        if (file_exists($view_file)) {
            // L'inclusion va déclencher le bloc "if export_csv" au début de VotesList.php
            include $view_file;
            exit; // On coupe court pour envoyer le fichier CSV pur
        }
    }
});

// --- CHARGEMENT DU DESIGN ---
add_action('wp_enqueue_scripts', function() {
    // On utilise time() pour casser le cache pendant le développement
    wp_enqueue_style('pp-style', PP_URL . 'assets/css/style.css', [], time());
});

// --- SÉCURITÉ : REDIRECTION DES NON-CONNECTÉS (302) ---
add_action('template_redirect', function() {
    if (!is_admin() && !is_user_logged_in()) {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'parcoursup_form')) {
            wp_redirect(home_url('/index.php/connexion/'), 302);
            exit;
        }
    }
});

add_action('plugins_loaded', function() {
    
    // --- 1. CHARGEMENT DES CLASSES ET ACTIONS ---
    if (is_admin()) {
        if (class_exists('PP_Main_Admin')) {
            new PP_Main_Admin();
        }
    } else {
        $front_file = PP_PATH . 'classes/actions/FrontIndex.php';
        if (file_exists($front_file)) {
            require_once $front_file;
            new PP_Actions_FrontIndex();
        }
    }
    
    // --- 2. ENREGISTREMENT DES SHORTCODES ---
    $shortcode_file = PP_PATH . 'classes/Shortcodes/VoeuxForm.php';
    if (file_exists($shortcode_file)) {
        require_once $shortcode_file;
        new PP_Shortcodes_VoeuxForm();
    }

    $auth_file = PP_PATH . 'classes/Shortcodes/AuthForm.php';
    if (file_exists($auth_file)) {
        require_once $auth_file;
        new PP_Shortcodes_AuthForm();
    }

    // --- 3. MENU ADMIN : LISTE DES VŒUX ---
    if (is_admin()) {
        add_action('admin_menu', function() {
            add_submenu_page(
                'pp-parcoursup',      
                'Liste des Vœux',     
                'Voir les Vœux',      
                'manage_options', 
                'pp-votes-list', 
                'pp_display_votes_view' 
            );
        });
    }
});

/**
 * Fonction d'affichage de la vue Admin (Affichage normal)
 */
function pp_display_votes_view() {
    $crud_file = PP_PATH . 'classes/Crud/Votes.php';
    $campaign_file = PP_PATH . 'classes/Crud/Campaign.php';
    
    if (file_exists($crud_file)) require_once $crud_file;
    if (file_exists($campaign_file)) require_once $campaign_file;

    $view_file = PP_PATH . 'classes/views/VotesList.php';
    
    if (file_exists($view_file)) {
        include $view_file;
    } else {
        echo "<div class='wrap' style='background: white; padding: 20px; border-left: 4px solid red; margin-top:20px;'>";
        echo "<h2>🚨 Fichier introuvable</h2>";
        echo "</div>";
    }
}

/**
 * Hook d'activation
 */
register_activation_hook(__FILE__, function() {
    $installer = new PP_Install_DbInstaller();
    $installer->install();
});