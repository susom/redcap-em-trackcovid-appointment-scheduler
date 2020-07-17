User = {
    listURL: '',
    slotsEventId: '',
    cancelURL: '',
    userListURL: '',
    record: {},
    init: function () {
        //$("#appointments").dataTable();
        User.loadUserVisits();

        /**
         * list view in modal
         */
        jQuery(document).on("click", ".get-list", function () {
            /**
             * init the reservation event id for selected slot.
             * @type {jQuery}
             */
            User.record.reservation_event_id = jQuery(this).data('key');
            User.record.participant_id = jQuery(this).data('record-id');
            ;
            jQuery.ajax({
                'url': User.listURL + "&event_id=" + User.slotsEventId + "&year=" + jQuery(this).data('year') + "&month=" + jQuery(this).data('month'),
                'type': 'GET',
                'beforeSend': function () {
                    /**
                     * remove any displayed calendar
                     */
                    jQuery('.slots-container').html('');
                },
                'success': function (data) {
                    $('#list-result').DataTable().clear().destroy()
                    if (data != '') {
                        $('#generic-modal').find('.modal-title').html("Appointments");
                        $('#list-result').DataTable({
                            dom: 'Bfrtip',
                            data: data.data,
                            pageLength: 50,
                            "bDestroy": true,
                            "aaSorting": [[0, "asc"]]
                        });
                        $('#generic-modal').modal('show');
                    } else {
                        data.data = [];
                        $('#list-result').DataTable().clear().destroy()
                        $('#generic-modal').find('.modal-title').html("Appointments");
                        $('#list-result').DataTable({
                            dom: 'Bfrtip',
                            data: data.data,
                            pageLength: 50,
                            "bDestroy": true,
                            "aaSorting": [[0, "asc"]]
                        });
                        $('#generic-modal').modal('show');
                    }


                    //change calendar view event to be displayed in modal
                    jQuery(".calendar-view").removeClass('calendar-view').addClass('survey-calendar-view');
                },
                'error': function (request, error) {
                    var data = JSON.parse(request.responseText)
                    alert(data.message);
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
                    } else {
                        alert(response.message);
                    }
                },
                error: function (request, error) {
                    var data = JSON.parse(request.responseText)
                    alert(data.message);
                },
                'complete': function () {
                    User.loadUserVisits()
                }
            });
        });

        /**
         * Cancel appointment
         */
        jQuery(document).on('click', '.cancel-appointment', function (e) {
            e.stopPropagation();
            e.preventDefault();
            e.stopImmediatePropagation();
            if (confirm("Are you sure you want to cancel this appointment?")) {
                var record_id = jQuery(this).data('record-id');
                var event_id = jQuery(this).data('key');
                /**
                 * Get Manage modal to let user manage their saved appointments
                 */
                jQuery.ajax({
                    url: User.cancelURL + '&record_id=' + record_id + '&event_id=' + event_id,
                    type: 'GET',
                    datatype: 'json',
                    success: function (data) {
                        data = JSON.parse(data);
                        alert(data.message);
                        jQuery('.manage').trigger('click');
                    },
                    error: function (request, error) {
                        var data = JSON.parse(request.responseText)
                        alert(data.message);
                    },
                    'complete': function () {
                        User.loadUserVisits()
                    }
                });
            }
        });

    },
    loadUserVisits: function () {
        jQuery.ajax({
            'url': User.userListURL,
            'type': 'GET',
            'beforeSend': function () {
                /**
                 * remove any displayed calendar
                 */
                jQuery('.slots-container').html('');
            },
            'success': function (data) {
                $('#appointments').DataTable({
                    dom: 'Bfrtip',
                    data: data.data,
                    pageLength: 50,
                    "bDestroy": true,
                    "aaSorting": [[0, "asc"]],
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });
            },
            'error': function (request, error) {
                var data = JSON.parse(request.responseText)
                alert(data.message);
            }
        });
    }
}
window.onload = function () {
    User.init();
}
