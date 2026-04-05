jQuery(document).ready(function($) {
    // On ajoute une sécurité : quand on clique sur un lien de suppression
    // Il faut que l'élément HTML ait la classe "delete-campaign-btn"
    $('.delete-campaign-btn').on('click', function(e) {
        if (!confirm('Attention : Êtes-vous sûr de vouloir supprimer cette campagne ? Cette action est irréversible.')) {
            e.preventDefault(); // Annule le clic si l'utilisateur clique sur "Annuler"
        }
    });

    // Optionnel : On fait disparaître les messages de succès après 5 secondes
    setTimeout(function() {
        $('.updated, .notice-success').fadeOut('slow');
    }, 5000);
});