jQuery(document).ready(function($) {
    // Fonction pour gérer l'affichage en cascade
    function updateCascade(current, next, exclude1, exclude2) {
        var val = $(current).val();
        if (val !== "") {
            $(next).prop('disabled', false).empty().append('<option value="">-- Choisir la formation --</option>');
            
            // On récupère les options du vœu 1 (la source)
            $("#voeu1 option").each(function() {
                var optionVal = $(this).val();
                // On filtre : pas vide, pas déjà choisi
                if (optionVal !== "" && optionVal !== val && optionVal !== exclude1 && optionVal !== exclude2) {
                    $(this).clone().appendTo(next);
                }
            });
        } else {
            $(next).prop('disabled', true).val("").empty();
            if(current === '#voeu1') $('#voeu3').prop('disabled', true).val("").empty();
        }
    }

    // Événements
    $('#voeu1').on('change', function() { 
        updateCascade('#voeu1', '#voeu2', null, null); 
    });
    
    $('#voeu2').on('change', function() { 
        updateCascade('#voeu2', '#voeu3', $('#voeu1').val(), null); 
    });
});