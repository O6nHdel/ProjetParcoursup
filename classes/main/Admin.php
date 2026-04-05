<?php

class PP_Main_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        // On charge le JS spécifique à l'admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_admin_assets($hook) {
        // On ne charge le script que sur les pages de notre plugin
        if (strpos($hook, 'pp-admin') === false) return;

        wp_enqueue_script(
            'pp-admin-js', 
            plugin_dir_url(dirname(__DIR__)) . 'assets/js/admin-scripts.js', 
            array('jquery'), 
            '1.0', 
            true
        );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Parcoursup', 
            'Parcoursup', 
            'manage_options', 
            'pp-admin', 
            [$this, 'render_admin_index'], 
            'dashicons-welcome-learn-more', 
            25
        );
    }

    public function render_admin_index() {
        $campaign_crud = new PP_Crud_Campaign();

        // 1. ON ISOLE LE TRAITEMENT DES DONNÉES
        $this->handle_admin_actions($campaign_crud);

        // 2. AIGUILLAGE DES VUES (LOGIQUE D'AFFICHAGE UNIQUEMENT)
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        echo '<div class="wrap">'; // Conteneur standard WordPress

        if ($action === 'add') {
            include_once PP_PATH . 'classes/views/admin-add-campaign.php';
        } 
        elseif ($action === 'edit' && isset($_GET['id'])) {
            $campaign = $campaign_crud->get_one($_GET['id']);
            include_once PP_PATH . 'classes/views/admin-edit-campaign.php';
        }
        elseif ($action === 'manage_choices' && isset($_GET['id'])) {
            $campaign = $campaign_crud->get_one($_GET['id']);
            $choices  = $campaign_crud->get_choices($_GET['id']);
            include_once PP_PATH . 'classes/views/admin-manage-choices.php';
        } 
        else {
            $campaigns = $campaign_crud->get_all();
            include_once PP_PATH . 'classes/views/admin-index.php';
        }

        echo '</div>';
    }

    /**
     * Gère tous les traitements de formulaires (Responsabilité : Logique)
     */
    private function handle_admin_actions($campaign_crud) {
        // Messages de succès simples
        if (isset($_GET['message'])) {
            $msg = ($_GET['message'] === 'updated') ? 'Campagne mise à jour !' : 'Action effectuée.';
            echo '<div class="updated"><p>'.$msg.'</p></div>';
        }

        // Traitement AJOUT
        if (isset($_POST['submit_campaign']) && wp_verify_nonce($_POST['pp_add_campaign_nonce'], 'pp_add_campaign_action')) {
            if ($campaign_crud->create($_POST)) {
                echo '<div class="updated"><p>Campagne ajoutée !</p></div>';
            }
        }

        // Traitement UPDATE
        if (isset($_POST['update_campaign']) && isset($_GET['id']) && wp_verify_nonce($_POST['pp_edit_campaign_nonce'], 'pp_edit_campaign_action')) {
            $campaign_crud->update($_GET['id'], $_POST);
            $this->redirect_to_index('updated');
        }

        // Traitement DELETE
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            if ($campaign_crud->delete($_GET['id'])) {
                $this->redirect_to_index('deleted');
            } else {
                echo '<div class="error"><p>Suppression impossible : des vœux sont liés à cette campagne.</p></div>';
            }
        }

        // Traitement AJOUT FORMATION (Choice)
        if (isset($_POST['submit_choice']) && isset($_GET['id'])) {
            check_admin_referer('pp_add_choice_action', 'pp_add_choice_nonce');
            $campaign_crud->add_choice($_GET['id'], $_POST['name_choice']);
        }
    }

    private function redirect_to_index($msg_type) {
        $url = admin_url('admin.php?page=pp-admin&message=' . $msg_type);
        echo "<script>window.location.href='$url';</script>";
        exit;
    }
}