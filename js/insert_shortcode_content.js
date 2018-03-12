jQuery(function($) {

    $(document).ready(function(){
        /*$("#one-time-url-list-open").on("click", function() {
            $("#one-time-url-list-wrap").fadeIn("fast");
        });

        $("#one-time-url-list-close").on("click", function() {
            $("#one-time-url-list-wrap").fadeOut("fast");
        });

        $("#one-time-url-list-wrap").on("click", function() {
            $("#one-time-url-list-wrap").fadeOut("fast");
        });*/

        $("#one-time-url-list ul li").on("click", function() {
            wp.media.editor.insert( $(this).text() );
            //$("#one-time-url-list-wrap").fadeOut("fast");
        });
    });

});