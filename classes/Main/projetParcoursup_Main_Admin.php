<?php

class projetParcoursup_Main_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_admin_assets($hook) {
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
        $campaign_crud = new projetParcoursup_Crud_Campaign();

        // 1. ON ISOLE LE TRAITEMENT DES DONNÉES
        $this->handle_admin_actions($campaign_crud);

        // 2. AIGUILLAGE DES VUES
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        echo '<div class="wrap">'; 

        if ($action === 'add') {
            pp_render('Admin_AddCampaign');
        } 
        elseif ($action === 'edit' && isset($_GET['id'])) {
            $campaign = $campaign_crud->get_one($_GET['id']);
            pp_render('Admin_EditCampaign', ['campaign' => $campaign]);
        }
        elseif ($action === 'manage_choices' && isset($_GET['id'])) {
            $campaign = $campaign_crud->get_one($_GET['id']);
            // La vue recevra la liste des choix à jour après le traitement
            $choices  = $campaign_crud->get_choices($_GET['id']);
            pp_render('Admin_ManageChoices', ['campaign' => $campaign, 'choices' => $choices]);
        } 
        else {
            $campaigns = $campaign_crud->get_all();
            pp_render('Admin_Index', ['campaigns' => $campaigns]);
        }

        echo '</div>';
    }

    /**
     * Gère tous les traitements de formulaires (Responsabilité : Logique)
     */
    private function handle_admin_actions($campaign_crud) {
        
        // Messages de succès simples
        if (isset($_GET['message']) && !isset($_GET['action'])) {
            $msg = ($_GET['message'] === 'updated') ? 'Campagne mise à jour !' : 'Action effectuée.';
            echo '<div class="updated"><p>'.$msg.'</p></div>';
        }

        // Traitement AJOUT CAMPAGNE
        if (isset($_POST['submit_campaign']) && wp_verify_nonce($_POST['pp_add_campaign_nonce'], 'pp_add_campaign_action')) {
            if ($campaign_crud->create($_POST)) {
                // CORRECTION : On redirige vers la liste avec le message de succès !
                $this->redirect_to_index('added'); 
            }
        }

        // Traitement UPDATE CAMPAGNE
        if (isset($_POST['update_campaign']) && isset($_GET['id']) && wp_verify_nonce($_POST['pp_edit_campaign_nonce'], 'pp_edit_campaign_action')) {
            $campaign_crud->update($_GET['id'], $_POST);
            $this->redirect_to_index('updated');
        }

        // Traitement DELETE CAMPAGNE
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            if ($campaign_crud->delete($_GET['id'])) {
                $this->redirect_to_index('deleted');
            } else {
                echo '<div class="error"><p>Suppression impossible : des vœux sont liés à cette campagne.</p></div>';
            }
        }

        // ==========================================
        // Traitement : AJOUT FORMATION (Avec Anti-Doublon)
        // ==========================================
        if (isset($_POST['submit_choice']) && isset($_GET['id'])) {
            check_admin_referer('pp_add_choice_action', 'pp_add_choice_nonce');
            
            $campaign_id = intval($_GET['id']);
            $new_name = sanitize_text_field($_POST['name_choice']);
            
            // On vérifie les doublons
            $existing_choices = $campaign_crud->get_choices($campaign_id);
            $already_exists = false;
            foreach ($existing_choices as $choice) {
                if (strtolower(trim($choice->name_choice)) === strtolower(trim($new_name))) {
                    $already_exists = true; break;
                }
            }

            if ($already_exists) {
                $url = admin_url("admin.php?page=pp-admin&action=manage_choices&id={$campaign_id}&message=error_duplicate");
                echo "<script>window.location.replace('$url');</script>"; exit;
            } else {
                $campaign_crud->add_choice($campaign_id, $new_name);
                $url = admin_url("admin.php?page=pp-admin&action=manage_choices&id={$campaign_id}&message=added");
                echo "<script>window.location.replace('$url');</script>"; exit;
            }
        }

        // ==========================================
        // Traitement : SUPPRESSION FORMATION
        // ==========================================
        if (isset($_GET['delete_id']) && isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] === 'manage_choices') {
            $choice_id = intval($_GET['delete_id']);
            $campaign_id = intval($_GET['id']);

            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_choice_' . $choice_id)) {
                $crud_votes = new projetParcoursup_Crud_Votes();
                $crud_choices = new projetParcoursup_Crud_Choices();

                if ($crud_votes->count_votes_by_choice($choice_id) > 0) {
                    $url = admin_url("admin.php?page=pp-admin&action=manage_choices&id={$campaign_id}&message=error_used");
                    echo "<script>window.location.replace('$url');</script>"; exit;
                } else {
                    $crud_choices->delete_choice($choice_id);
                    $url = admin_url("admin.php?page=pp-admin&action=manage_choices&id={$campaign_id}&message=deleted");
                    echo "<script>window.location.replace('$url');</script>"; exit;
                }
            }
        }
    }

    private function redirect_to_index($msg_type) {
        $url = admin_url('admin.php?page=pp-admin&message=' . $msg_type);
        echo "<script>window.location.replace('$url');</script>";
        exit;
    }
}