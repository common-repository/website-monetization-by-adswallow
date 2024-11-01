(function($){
    $(document).ready(function(){
       $('.ad-tooltip').tooltip({
           items: "span",
           content: function() {
               return $(this).attr('data-tooltip');
           }
       });
    });
})(jQuery);
