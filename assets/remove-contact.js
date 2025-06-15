document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.remove-contact').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if(!confirm('Retirer ce contact ?')) {
                e.preventDefault();
            }
        });
    });
});