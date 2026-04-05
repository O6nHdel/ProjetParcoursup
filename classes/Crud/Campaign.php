<?php

class PP_Crud_Campaign {
    private $table_campaigns;
    private $table_choices;

    public function __construct() {
        global $wpdb;
        // On définit les noms des tables avec le préfixe WordPress [cite: 26, 28]
        $this->table_campaigns = $wpdb->prefix . 'ps_campaigns';
        $this->table_choices   = $wpdb->prefix . 'ps_choices';
    }

    /**
     * Récupère la liste de toutes les campagnes 
     */
    public function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_campaigns} ORDER BY id_campaign DESC");
    }

    /**
     * Crée une nouvelle campagne en BDD 
     */
    public function create($data) {
        global $wpdb;
        return $wpdb->insert($this->table_campaigns, [
            'name_campaign' => sanitize_text_field($data['name']),
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'],
            'is_activated'  => 1
        ]);
    }

    /**
     * Récupère les formations liées à une campagne 
     */
    public function get_choices($id_campaign) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_choices} WHERE id_campaign = %d", 
            $id_campaign
        ));
    }

    /**
     * Ajoute une formation pour une campagne donnée 
     */
    public function add_choice($id_campaign, $name_choice) {
        global $wpdb;
        return $wpdb->insert($this->table_choices, [
            'id_campaign' => (int)$id_campaign,
            'name_choice' => sanitize_text_field($name_choice)
        ]);
    }
    
    /**
     * Règle de suppression : Interdit si un étudiant a déjà formulé des vœux [cite: 51]
     */
    public function delete($id_campaign) {
        global $wpdb;
        $table_pivot = $wpdb->prefix . 'ps_student_to_campaign';

        // 1. Vérifie si des étudiants sont déjà liés à cette campagne [cite: 51]
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_pivot WHERE id_campaign = %d",
            $id_campaign
        ));

        // 2. Si vœux existants, suppression refusée [cite: 51]
        if ($count > 0) {
            return false;
        }

        // 3. Sinon, on supprime la campagne [cite: 47]
        return $wpdb->delete($this->table_campaigns, ['id_campaign' => $id_campaign]);
    }

    /**
     * Modifie une campagne existante 
     */
    public function update($id, $data) {
        global $wpdb;
        return $wpdb->update(
            $this->table_campaigns,
            [
                'name_campaign' => sanitize_text_field($data['name']),
                'start_date'    => $data['start_date'],
                'end_date'      => $data['end_date']
            ],
            ['id_campaign' => (int)$id]
        );
    }

    /**
     * Récupère une seule campagne pour la modification 
     */
    public function get_one($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_campaigns} WHERE id_campaign = %d", 
            $id
        ));
    }
}