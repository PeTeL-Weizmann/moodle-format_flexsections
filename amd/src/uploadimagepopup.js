define([
    'jquery',
    'core/ajax',
    'core/str',
    'core/templates',
    'core/notification',
    'core/modal_factory',
    'core/fragment',
    'jqueryui',

], function($, Ajax, str, Templates, Notification, ModalFactory, Fragment) {

    return {
        init: function(uniqueid) {
            var form = $('#upload_image_form_' + uniqueid),
                self = this;

            form.on('keydown', 'input[type="text"]', function(e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                }
            });

            // Submit.
            form.find('.uploadimagesubmit').click(function(event) {
                self.uploadImage(event, form);
            });

            // Close modal factory popup.
            form.find('.uploadimageclose').click(function() {
                self.closeModalFactory(form);
            });
        },

        uploadImage: function(e, form) {
            let self = this;

            e.preventDefault();

            var serializedForm = form.serializeArray(),
                data = {};

            serializedForm.forEach(function(item) {
                data[item.name] = data[item.name] ? data[item.name] + ',' + item.value : item.value;
            });

            var parseResponse = function(response) {
                response = JSON.parse(response);

                switch (response.type) {
                    case 'course':
                        $('#courseheaderimage').css('background-image', 'url(' + response.imageurl + ')');
                        break;
                    case 'singlesection':
                        $('.card-img').css('background-image', 'url(' + response.imageurl + ')');
                        break;
                    case 'multisection':
                        $('#section-image-' + response.sectionid).css('background-image', 'url(' + response.imageurl + ')');
                        break;
                }

                // Close modal factory popup.
                self.closeModalFactory(form);
            };

            switch (data.type) {
                case 'course':
                    Ajax.call([{
                        methodname: 'format_flexsections_change_courseimage',
                        args: {
                            fileitemid: data.uploadedimage,
                            courseid: data.courseid,
                        },
                        done: parseResponse,
                        fail: Notification.exception
                    }]);
                    break;
                case 'singlesection':
                case 'multisection':
                    Ajax.call([{
                        methodname: 'format_flexsections_change_sectionimage',
                        args: {
                            type: data.type,
                            fileitemid: data.uploadedimage,
                            sectionid: data.sectionid,
                        },
                        done: parseResponse,
                        fail: Notification.exception
                    }]);
                    break;
                default:
                    self.closeModalFactory(form);
            }
        },

        closeModalFactory: function(form) {
            form.parent().parent().parent().find('.close').click();
        },
    };
});
