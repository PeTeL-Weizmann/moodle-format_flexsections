define(['jquery',
        'core/ajax',
        'core/str',
        'core/modal_factory',
        'core/modal_events',
        'core/templates',
        'core/notification',
        'core/fragment'],
    function($, Ajax, Str, ModalFactory, ModalEvents, Templates, Notification, Fragment) {
        return {
            course: function() {
                $(document).on('click', '#uploadcourseimage', (e) => {
                    let courseid = $(e.target).data('courseid');
                    let coursecontextid = $(e.target).data('contextid');

                    const getBody = function() {
                        var params = {courseid: courseid, type: 'course'};
                        return Fragment.loadFragment('format_flexsections', 'upload_image', coursecontextid, params);
                    };

                    Str.get_strings([
                        {key: 'uploadimage', component: 'format_flexsections'},
                    ]).done(function(strings) {
                        var modalPromise = ModalFactory.create({
                            type: ModalFactory.types.DEFAULT,
                            title: strings[0],
                            body: getBody()
                        });

                        $.when(modalPromise).then(function(fmodal) {
                            fmodal.setLarge();
                            return fmodal;
                        }).done(function(modal) {
                            modal.show();
                        }).fail(Notification.exception);
                    });
                });
            },

            singleSection: function() {
                $(document).on('click', '#uploadsectionimage', (e) => {
                    let sectionid = $(e.target).data('sectionid');
                    let coursecontextid = $(e.target).data('contextid');

                    const getBody = function() {
                        var params = {sectionid: sectionid, type: 'singlesection'};
                        return Fragment.loadFragment('format_flexsections', 'upload_image', coursecontextid, params);
                    };

                    Str.get_strings([
                        {key: 'uploadimage', component: 'format_flexsections'},
                    ]).done(function(strings) {
                        var modalPromise = ModalFactory.create({
                            type: ModalFactory.types.DEFAULT,
                            title: strings[0],
                            body: getBody()
                        });

                        $.when(modalPromise).then(function(fmodal) {
                            fmodal.setLarge();
                            return fmodal;
                        }).done(function(modal) {
                            modal.show();
                        }).fail(Notification.exception);
                    });
                });
            },

            multiSections: function() {
                $('.uploadsectionimage').find('input').each(function() {

                    $(this).on('click', (e) => {
                        let sectionid = $(e.target).data('sectionid');
                        let coursecontextid = $(e.target).data('contextid');

                        const getBody = function() {
                            var params = {sectionid: sectionid, type: 'multisection'};
                            return Fragment.loadFragment('format_flexsections', 'upload_image', coursecontextid, params);
                        };

                        Str.get_strings([
                            {key: 'uploadimage', component: 'format_flexsections'},
                        ]).done(function(strings) {
                            var modalPromise = ModalFactory.create({
                                type: ModalFactory.types.DEFAULT,
                                title: strings[0],
                                body: getBody()
                            });

                            $.when(modalPromise).then(function(fmodal) {
                                fmodal.setLarge();
                                return fmodal;
                            }).done(function(modal) {
                                modal.show();
                            }).fail(Notification.exception);
                        });
                    });
                });
            },
        };
    });
