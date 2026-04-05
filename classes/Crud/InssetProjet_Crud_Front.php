<?php
class InssetProjet_Crud_Front {

    /**
     * Vérifie si l'étudiant est déjà inscrit à la campagne
     */
    public function get_student_registration($id_student, $id_campaign) {
        global $wpdb;
        $table = $wpdb->prefix . 'student_to_campaign';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id_student = %d AND id_campaign = %d",
            $id_student,
            $id_campaign
        ));
    }

    /**
     * Inscrit l'étudiant à la campagne avec génération du num_candidate
     */
    public function register_student_to_campaign($id_student, $id_campaign) {
        global $wpdb;
        $table = $wpdb->prefix . 'student_to_campaign';

        // Génération d'un numéro de candidat unique (ex: CAND-2026-4852)
        $num_candidate = 'CAND-' . date('Y') . '-' . mt_rand(1000, 9999);

        $inserted = $wpdb->insert($table, [
            'id_student'       => $id_student,
            'id_campaign'      => $id_campaign,
            'num_candidate'    => $num_candidate,
            'status_candidate' => 'en_attente',
            'date_add'         => current_time('mysql')
        ]);

        return $inserted ? $num_candidate : false;
    }
}