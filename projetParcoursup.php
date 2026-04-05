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

/**
 * Initialisation du plugin
 */
add_action('plugins_loaded', function() {
    
    // --- 1. CHARGEMENT DES CLASSES ET ACTIONS ---
    if (is_admin()) {
        // Charge l'administration principale
        if (class_exists('PP_Main_Admin')) {
            new PP_Main_Admin();
        }
    } else {
        // Charge la sécurité Front (Login obligatoire)
        $front_file = PP_PATH . 'classes/actions/FrontIndex.php';
        if (file_exists($front_file)) {
            require_once $front_file;
            new PP_Actions_FrontIndex();
        }
    }
    
    // --- 2. ENREGISTREMENT DU SHORTCODE ---
    $shortcode_file = PP_PATH . 'classes/Shortcodes/VoeuxForm.php';
    if (file_exists($shortcode_file)) {
        require_once $shortcode_file;
        new PP_Shortcodes_VoeuxForm();
    }

    // --- 3. MENU ADMIN : LISTE DES VŒUX ---
    if (is_admin()) {
        add_action('admin_menu', function() {
            add_submenu_page(
                'pp-parcoursup',      // Slug parent
                'Liste des Vœux',     // Titre fenêtre
                'Voir les Vœux',      // Texte menu
                'manage_options', 
                'pp-votes-list', 
                'pp_display_votes_view' // Fonction de rappel définie juste après
            );
        });
    }
});

/**
 * Fonction d'affichage de la vue (Hors du bloc plugins_loaded pour être accessible)
 */
function pp_display_votes_view() {
    // On charge le CRUD de données
    $crud_file = PP_PATH . 'classes/Crud/Votes.php';
    if (file_exists($crud_file)) {
        require_once $crud_file;
    }

    // On affiche le HTML de la vue
    $view_file = PP_PATH . 'classes/views/VotesList.php';
    
    if (file_exists($view_file)) {
        include $view_file;
    } else {
        // --- LE DÉTECTEUR D'ERREUR EST ICI ---
        echo "<div class='wrap' style='background: white; padding: 20px; border-left: 4px solid red; margin-top:20px;'>";
        echo "<h2>🚨 Fichier introuvable</h2>";
        echo "<p>WordPress cherche exactement ce chemin sur ton ordinateur :</p>";
        echo "<p><strong style='color:red; font-size:16px;'>" . $view_file . "</strong></p>";
        echo "<ul>";
        echo "<li>Vérifie que le dossier s'appelle bien <b>views</b> (avec un 's' et tout en minuscules).</li>";
        echo "<li>Vérifie que le fichier s'appelle bien <b>VotesList.php</b> (avec un V et un L majuscules).</li>";
        echo "</ul>";
        echo "</div>";
    }
}

/**
 * Hook d'activation : Création des tables SQL
 */
register_activation_hook(__FILE__, function() {
    $installer = new PP_Install_DbInstaller();
    $installer->install();
});