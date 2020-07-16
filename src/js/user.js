User = {
    listURL: '',
    slotsEventId: '',
    record: {},
    init: function () {
        $("#appointments").dataTable();

        /**
         * list view in modal
         */
        jQuery(document).on("click", ".survey-type", function () {
            var view = jQuery(this).data('default-view');
            /**
             * init the reservation event id for selected slot.
             * @type {jQuery}
             */
            User.record.reservation_event_id = jQuery(this).data('key');
            User.record.participant_id = jQuery(this).data('record-id');
            ;
            jQuery.ajax({
                'url': User.listURL + "&event_id=" + User.slotsEventId,
                'type': 'GET',
                'beforeSend': function () {
                    /**
                     * remove any displayed calendar
                     */
                    jQuery('.slots-container').html('');
                },
                'success': function (data) {
                    $('#generic-modal').find('.modal-title').html("Appointments");
                    $('#list-result').DataTable({
                        dom: 'Bfrtip',
                        data: data.data,
                        pageLength: 50,
                        "bDestroy": true,
                        "aaSorting": [[0, "asc"]],
                        buttons: [
                            'copy', 'csv', 'excel', 'pdf', 'print'
                        ]
                    });
                    $('#generic-modal').modal('show');

                    //change calendar view event to be displayed in modal
                    jQuery(".calendar-view").removeClass('calendar-view').addClass('survey-calendar-view');
                },
                'error': function (request, error) {
                    alert("Request: " + JSON.stringify(request));
                }
            });
        });

        /**
         * Show Form to complete for selected time
         */
        jQuery(document).on('click', '.time-slot', function (e) {
            e.stopPropagation();
            e.preventDefault();
            e.stopImmediatePropagation();
            User.record.record_id = jQuery(this).data('record-id');
            User.record.event_id = jQuery(this).data('event-id');
            /**
             * Capture start and end time for Email calendar
             */
            User.record.calendarStartTime = jQuery(this).data('start');
            User.record.calendarEndTime = jQuery(this).data('end');
            /**
             * Capture date for Email calendar
             */
            User.record.calendarDate = jQuery(this).data('date');
            data = User.record
            $.ajax({
                url: User.submitURL,
                type: 'POST',
                data: User.record,
                success: function (response) {
                    response = JSON.parse(response);
                    if (response.status == 'ok') {
                        alert(response.message);
                        $('#booking').modal('hide');
                        record = {};
                        if (currentView != '') {
                            currentView.trigger('click');
                        }

                        /**
                         * when this book came from survey page lets return the reservation id back to the survey.
                         */
                        var survey_record_id_field = jQuery("#survey-record-id-field").val();
                        if (jQuery("input[name=" + survey_record_id_field + "]").length) {
                            completeSurveyReservation(response);
                        }

                    } else {
                        alert(response.message);
                    }
                },
                error: function (request, error) {
                    var data = JSON.parse(request.responseText)
                    alert(data.message);
                }
            });
        });

    }
}
window.onload = function () {
    User.init();
}
