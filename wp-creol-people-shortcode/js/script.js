// Minimal JS for creol people shortcode
(function(){
    // Placeholder: could add interactions like modal on click
    document.addEventListener('click', function(e){
        var card = e.target.closest && e.target.closest('.creol-person-card');
        if(!card) return;
        // Future: handle card click
    });
})();
