define([
    "jquery",
    "Magento_Ui/js/modal/modal"
], function($, modal){
    "use strict";    
    function popUp(config) {
        var modalElement = $('#popup-modal-age-verification');
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            closeOnEscape: false,
            clickableOverlay: false,
            title: config.title,
            buttons: [],
            modalClass: 'age-verification-modal'
        };
        var popup = modal(options, modalElement);
        modalElement.modal('openModal');
        $('.age-verification-modal .modal-header').remove();
        $('.age-verification-modal .modal-footer').remove();        
        $('.agree-button').on('click', function() {
            setSessionCode(config,true).done(function (response) {
                if(response.success === 200) {
                    modalElement.modal('closeModal');
                } else {
                    alert("Something went to be wrong !!");
                }
            });
        });
        $('.disagree-button').on('click', function() {
            setSessionCode(config,false).done(function (response) {
                if(response.error === 202) {
                    modalElement.modal('closeModal');
                    window.location.href = config.redirectUrl;
                } else {
                    alert("Something went to be wrong !!");
                }
            });
        });
    }

    function setSessionCode(config,type) {
        return $.ajax({
            context: '#popup-modal-age-verification',
            url: config.ajaxUrl,
            type: "POST",
            data: {type:type},
        })
    }
    return popUp;
});