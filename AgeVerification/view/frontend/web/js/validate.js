define([
    "jquery",
    "jquery/ui"
], function($){
    "use strict";
    function validate(config, element) {
        var $element = $(element);
        var AjaxUrl = config.AjaxUrl;
        $(document).ready(function(){                      
            $.ajax({
                context: '#age_verification_popup',
                url: AjaxUrl,
                type: "POST",
                data: {},
            }).done(function (data) {
                $('#age_verification_popup').html(data.output).trigger('contentUpdated');
                return true;
            });            
        });
    }
    return validate;
});