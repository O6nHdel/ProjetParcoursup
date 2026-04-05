<?php

class PP_Crud_Votes {

    public function get_all_votes() {
        global $wpdb;
        
        // On définit les noms de tes tables
        $table_votes = $wpdb->prefix . 'ps_student_choices';
        $table_users = $wpdb->prefix . 'users';
        $table_choices = $wpdb->prefix . 'ps_choices';

        // Requête SQL avec JOIN pour récupérer les noms au lieu des IDs
        $sql = "SELECT 
                    u.display_name as student_name, 
                    c.name_choice as formation_name, 
                    v.choice_order 
                FROM $table_votes v
                JOIN $table_users u ON v.id_stc = u.ID
                JOIN $table_choices c ON v.id_choice = c.id_choice
                ORDER BY u.display_name ASC, v.choice_order ASC";

        return $wpdb->get_results($sql);
    }
}