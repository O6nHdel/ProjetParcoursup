<?php
/**
 * Classe CRUD pour la gestion des formations (Choices)
 */

// Sécurité WordPress : empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit; 
}

class projetParcoursup_Crud_Choices {
    
    private $table_name;

    public function __construct() {
        global $wpdb;
        // Remplace 'pp_choices' par le vrai nom de ta table (sans le préfixe wp_)
        $this->table_name = $wpdb->prefix . 'ps_choices'; 
    }

    /**
     * 1. Ajouter une nouvelle formation à une campagne (CREATE)
     */
    public function add_choice($campaign_id, $name_choice) {
        global $wpdb;
        
        // $wpdb->insert sécurise automatiquement les données
        return $wpdb->insert(
            $this->table_name,
            [
                'id_campaign' => intval($campaign_id),
                'name_choice' => sanitize_text_field($name_choice) // Nettoyage du texte
            ],
            ['%d', '%s'] // %d = entier (digit), %s = chaîne de caractères (string)
        );
    }

    /**
     * 2. Récupérer toutes les formations d'une campagne spécifique (READ)
     */
    public function get_choices_by_campaign($campaign_id) {
        global $wpdb;
        
        // $wpdb->prepare empêche les injections SQL
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id_campaign = %d ORDER BY id_choice ASC",
            intval($campaign_id)
        );
        
        return $wpdb->get_results($query);
    }

    /**
     * 3. Supprimer une formation (DELETE)
     */
    public function delete_choice($choice_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id_choice' => intval($choice_id)],
            ['%d']
        );
    }

    /**
     * 4. (Bonus) Récupérer une seule formation par son ID
     * Très utile si tu veux faire une page pour modifier le nom d'une formation plus tard !
     */
    public function get_choice($choice_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id_choice = %d",
            intval($choice_id)
        );
        
        return $wpdb->get_row($query);
    }
    
    /**
     * 5. (Bonus) Mettre à jour le nom d'une formation (UPDATE)
     */
    public function update_choice($choice_id, $name_choice) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            ['name_choice' => sanitize_text_field($name_choice)],
            ['id_choice' => intval($choice_id)],
            ['%s'],
            ['%d']
        );
    }
}