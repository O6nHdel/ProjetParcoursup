<?php

class projetParcoursup_Crud_Votes {

    public function get_all_votes($campaign_id = 0) {
        global $wpdb;
        
        $table_votes   = $wpdb->prefix . 'ps_student_choices';
        $table_stc     = $wpdb->prefix . 'ps_student_to_campaign';
        $table_users   = $wpdb->users; // Table native WP
        $table_choices = $wpdb->prefix . 'ps_choices';

        // Requête SQL avec le double JOIN pour remonter jusqu'à l'utilisateur
        // On passe par la table STC pour faire le lien entre le vote et l'étudiant
        $sql = "SELECT 
                    u.display_name as student_name, 
                    c.name_choice as formation_name, 
                    v.choice_order 
                FROM $table_votes v
                JOIN $table_stc stc ON v.id_stc = stc.id_stc
                JOIN $table_users u ON stc.id_student = u.ID
                JOIN $table_choices c ON v.id_choice = c.id_choice";

        // Si une campagne est sélectionnée dans le filtre Admin
        if ($campaign_id > 0) {
            $sql .= $wpdb->prepare(" WHERE stc.id_campaign = %d", $campaign_id);
        }

        $sql .= " ORDER BY u.display_name ASC, v.choice_order ASC";

        return $wpdb->get_results($sql);
    }

    /**
     * NOUVELLE FONCTION :
     * Compte combien de votes sont associés à une formation précise.
     * Utile pour empêcher la suppression d'une formation utilisée.
     */
    public function count_votes_by_choice($choice_id) {
        global $wpdb;
        
        // On utilise ici le vrai nom de ta table que j'ai vu dans ta fonction du dessus
        $table_votes = $wpdb->prefix . 'ps_student_choices'; 
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_votes} WHERE id_choice = %d",
            intval($choice_id)
        );
        
        return intval($wpdb->get_var($query));
    }
}