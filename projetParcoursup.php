<?php
/**
 * Plugin Name: Projet Parcoursup - LP 2025-2026
 * Description: Application de gestion de vœux inspirée de ParcoursSup.
 * Version: 1.0
 * Author: Hugo Delaporte
 */

if (!defined('ABSPATH')) exit;

define('PP_PATH', plugin_dir_path(__FILE__));
define('PP_URL', plugin_dir_url(__FILE__));

// --- 1. AUTOLOADER CORRIGÉ ---
spl_autoload_register(function ($class_name) {
    // Si la classe ne commence pas par notre préfixe, on ignore
    if (strpos($class_name, 'projetParcoursup_') !== 0) return;

    // Exemple de classe : projetParcoursup_Action_FrontIndex
    // On découpe le nom grâce aux tirets du bas (_)
    $parts = explode('_', $class_name);
    
    // Le nom du dossier se trouve toujours en 2ème position (index 1) : Action, Crud, Shortcodes...
    if (isset($parts[1])) {
        $folder = $parts[1];
        
        // On construit le chemin : classes/Action/projetParcoursup_Action_FrontIndex.php
        $path = PP_PATH . 'classes' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $class_name . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }
});

// --- 2. FONCTION DE RENDU DES VUES ---
function pp_render($view_name, $data = []) {
    $full_name = 'projetParcoursup_Views_' . $view_name . '.php';
    $path = PP_PATH . 'classes/views/' . $full_name;
    if (file_exists($path)) {
        extract($data);
        include $path;
    } else {
        echo "<div class='notice notice-error'><p>Vue introuvable : $full_name</p></div>";
    }
}

// --- 3. ACTIVATION DU PLUGIN ---
register_activation_hook(__FILE__, function() {
    $installer = new projetParcoursup_Install_DbInstaller();
    $installer->install();
});

// --- 4. INITIALISATION ---
add_action('plugins_loaded', function() {
    // Front-Office
    new projetParcoursup_Action_Front_Index(); // <-- La correction est ici (ajout du _ )
    new projetParcoursup_Shortcodes_VoeuxForm();
    new projetParcoursup_Shortcodes_AuthForm();

    // Back-Office
    if (is_admin()) {
        new projetParcoursup_Main_Admin();
        
        // Si tu as aussi mis un underscore pour l'admin, utilise cette ligne :
        new projetParcoursup_Action_Admin_Index(); 
    }
});