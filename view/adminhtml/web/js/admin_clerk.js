require(
    [
        'jquery',
        'Magento_Ui/js/modal/confirm',
        'Magento_Ui/js/modal/alert'
    ],
    function ($, confirmation, alert) {


        $('#clerk_log_level').focus(function () {

            before_logging_level = $('#clerk_log_level').val();

        }).change(function () {

            log_level = $(this).val();

            if (log_level == 'all') {

                confirmation({
                    title: $.mage.__('Changing Logging Level'),
                    content: $.mage.__('Debug Mode should not be used in production! Are you sure you want to change logging level to Debug Mode ? '),
                    actions: {
                        confirm: function () {
                        },
                        cancel: function () {
                        },
                        always: function () {
                        }
                    },
                    buttons: [{
                        text: $.mage.__('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function (event) {
                            document.querySelector('#clerk_log_level [value="' + before_logging_level + '"]').selected = true;
                            this.closeModal(event);
                        }
                    }, {
                        text: $.mage.__('I\'m Sure'),
                        class: 'action-primary action-accept',
                        click: function (event) {
                            this.closeModal(event, true);
                        }
                    }]
                });

            }
            else {

                before_logging_level = $('#clerk_log_level').val();

            }

        });
    }
);