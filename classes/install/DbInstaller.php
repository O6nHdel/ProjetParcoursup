<?php

class PP_Install_DbInstaller {

    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 1. Table des Étudiants (Si on ne veut pas utiliser wp_users pour séparer le métier)
        $table_student = $wpdb->prefix . 'ps_students';
        
        // 2. Table des Campagnes
        $table_campaign = $wpdb->prefix . 'ps_campaigns';

        // 3. Table des Formations (Choix possibles)
        $table_choice = $wpdb->prefix . 'ps_choices';

        // 4. Table Pivot : Liaison Étudiant <-> Campagne
        $table_pivot = $wpdb->prefix . 'ps_student_to_campaign';

        // 5. Table des Voeux (Entité / Valeur)
        $table_student_choices = $wpdb->prefix . 'ps_student_choices';

        $sql = "CREATE TABLE $table_student (
            id_student mediumint(9) NOT NULL AUTO_INCREMENT,
            lname_student varchar(100) NOT NULL,
            fname_student varchar(100) NOT NULL,
            email_student varchar(100) NOT NULL,
            password varchar(255) NOT NULL, -- 
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id_student)
        ) $charset_collate;

        CREATE TABLE $table_campaign (
            id_campaign mediumint(9) NOT NULL AUTO_INCREMENT,
            name_campaign varchar(255) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            is_activated tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id_campaign)
        ) $charset_collate;

        CREATE TABLE $table_choice (
            id_choice mediumint(9) NOT NULL AUTO_INCREMENT,
            id_campaign mediumint(9) NOT NULL,
            name_choice varchar(255) NOT NULL,
            PRIMARY KEY  (id_choice)
        ) $charset_collate;

        CREATE TABLE $table_pivot (
            id_stc mediumint(9) NOT NULL AUTO_INCREMENT,
            id_student mediumint(9) NOT NULL,
            id_campaign mediumint(9) NOT NULL,
            status_candidate varchar(50) DEFAULT 'en_attente',
            PRIMARY KEY  (id_stc)
        ) $charset_collate;

        CREATE TABLE $table_student_choices (
            id_student_choice mediumint(9) NOT NULL AUTO_INCREMENT,
            id_stc mediumint(9) NOT NULL,
            id_choice mediumint(9) NOT NULL,
            choice_order tinyint(1) NOT NULL, -- [cite: 18]
            PRIMARY KEY  (id_student_choice)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}