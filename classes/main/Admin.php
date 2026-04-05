<?php

class PP_Main_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
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

        // --- 0. AFFICHAGE DES MESSAGES (Alertes WordPress) ---
        if (isset($_GET['message']) && $_GET['message'] === 'updated') {
            echo '<div class="updated"><p>Campagne mise à jour avec succès !</p></div>';
        }
        if (isset($_GET['message']) && $_GET['message'] === 'deleted') {
            echo '<div class="updated"><p>Campagne supprimée avec succès.</p></div>';
        }

        // --- 1. TRAITEMENT DES FORMULAIRES (POST) ---

        // AJOUT d'une campagne
        if (isset($_POST['submit_campaign'])) {
            if (isset($_POST['pp_add_campaign_nonce']) && wp_verify_nonce($_POST['pp_add_campaign_nonce'], 'pp_add_campaign_action')) {
                $result = $campaign_crud->create($_POST);
                if ($result) {
                    echo '<div class="updated"><p>Campagne ajoutée avec succès !</p></div>';
                } else {
                    echo '<div class="error"><p>Erreur lors de l\'ajout.</p></div>';
                }
            }
        }

        // MODIFICATION d'une campagne (Redirection JS pour éviter l'erreur Header)
        if (isset($_POST['update_campaign']) && isset($_GET['id'])) {
            if (wp_verify_nonce($_POST['pp_edit_campaign_nonce'], 'pp_edit_campaign_action')) {
                $campaign_crud->update($_GET['id'], $_POST);
                
                $url = admin_url('admin.php?page=pp-admin&message=updated');
                echo "<script>window.location.href='$url';</script>";
                exit; 
            }
        }

        // AJOUT d'une formation (Choice)
        if (isset($_POST['submit_choice']) && isset($_GET['id'])) {
            check_admin_referer('pp_add_choice_action', 'pp_add_choice_nonce');
            $campaign_crud->add_choice($_GET['id'], $_POST['name_choice']);
            echo '<div class="updated"><p>Formation ajoutée à la campagne !</p></div>';
        }

        // SUPPRESSION d'une campagne (Redirection JS pour éviter l'erreur Header)
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $deleted = $campaign_crud->delete($_GET['id']);
            if ($deleted) {
                $url = admin_url('admin.php?page=pp-admin&message=deleted');
                echo "<script>window.location.href='$url';</script>";
                exit;
            } else {
                echo '<div class="error"><p>Suppression impossible : des étudiants sont déjà inscrits à cette campagne.</p></div>';
            }
        }

        // --- 2. AIGUILLAGE DES VUES (GET) ---

        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        if ($action === 'add') {
            include_once PP_PATH . 'classes/views/admin-add-campaign.php';
        } 
        elseif ($action === 'edit' && isset($_GET['id'])) {
            $campaign = $campaign_crud->get_one($_GET['id']);
            if ($campaign) {
                include_once PP_PATH . 'classes/views/admin-edit-campaign.php';
            } else {
                echo '<div class="error"><p>Campagne introuvable.</p></div>';
            }
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
    }
}