(function( $ ) {
    'use strict';
    
    var e = document.querySelectorAll(".ssl-alp-katex-equation");
    
    Array.prototype.forEach.call(e, function(e) {
        var t = {
            displayMode: "true" === e.getAttribute("data-display"),
            throwOnError: !1
        };
        var r = document.createElement("span");

        try {
            katex.render(e.textContent, r, t)
        } catch (a) {
            r.style.color = "red";
            r.textContent = a.message;
        }
        
        e.parentNode.replaceChild(r, e)
    });
})( jQuery );
