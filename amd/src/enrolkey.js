define(['jquery', 'core/modal_factory', 'core/templates'], function($, ModalFactory, Templates) {
    return {
        init_dialog: function(context, enrolkey) {
            var trigger = $('#enrolkeybtn');
            var params = {enrolkey: enrolkey, enrolurl: M.cfg.wwwroot + '/enrol/self/enrolwithkey.php?enrolkey='
                    + enrolkey, wwwroot:  M.cfg.wwwroot};
            ModalFactory.create({
                title: M.util.get_string('getcoursekeytitle', 'theme_petel'),
                type: ModalFactory.types.CANCEL,
                body: Templates.render('format_flexsections/getenrolkey', params)
                /*
                ,footer: '<button type="button" class="btn btn-primary" data-action="cancel">'
                        + M.util.get_string('close', 'theme_petel')
                        // + '</button> <button type="button" class="btn btn-secondary" data-action="cancel">' +
                        //M.util.get_string('cancel', 'theme_petel') + '</button>'
                        */
            }, trigger)
                .done(function(modal) {
                    // Do what you want with your new modal.
                    modal.getRoot().addClass('enrolkeydialog');
                });
        }
    };
});
