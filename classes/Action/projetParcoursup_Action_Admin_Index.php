<?php

class projetParcoursup_Action_Admin_Index {

    public function __construct() {
        // Création du sous-menu
        add_action('admin_menu', [$this, 'add_votes_menu']);
        
        // Intercepteur pour l'Export CSV (s'exécute avant le rendu HTML)
        add_action('admin_init', [$this, 'intercept_csv_export']);
    }

    /**
     * Ajoute la page "Liste des Vœux" dans le menu WordPress
     */
    public function add_votes_menu() {
        add_submenu_page(
            'pp-parcoursup',      // Slug du parent (Défini dans Main_Admin)
            'Liste des Vœux',     // Titre de la page
            'Voir les Vœux',      // Titre dans le menu
            'manage_options',     // Capacité requise
            'pp-votes-list',      // Slug de cette page
            [$this, 'render_votes_page'] // Fonction d'affichage
        );
    }

    /**
     * Affiche la vue de la liste des vœux (HTML)
     */
    public function render_votes_page() {
        // On utilise notre nouvelle fonction de rendu !
        pp_render('Admin_VotesList');
    }

    /**
     * Intercepte la demande d'export CSV
     */
    public function intercept_csv_export() {
        if (isset($_GET['page']) && $_GET['page'] === 'pp-votes-list' && isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            
            // On peut appeler la vue contenant le script d'export directement
            pp_render('Admin_VotesList', ['is_exporting' => true]);
            exit; // Très important : on coupe l'exécution de WP pour envoyer le fichier pur
        }
    }
}