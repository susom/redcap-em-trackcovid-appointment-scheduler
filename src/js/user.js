User = {
    listURL: '',
    slotsEventId: '',
    cancelURL: '',
    instancesListURL: '',
    loginURL: '',
    record: {},
    locations: [],
    timezones: {
        300: 'EST',
        360: 'CST',
        420: 'MST',
        480: 'PST',
    },
    currentOffset: null,
    userTimezone: '',
    init: function () {


        // calculate user timezone
        this.calculateUserTimezone();

        //$("#appointments").dataTable();
        this.loadUserVisits();


        $(document).on("click", ".logout", function () {
            eraseCookie('login');
            eraseCookie('preferred-location');
            window.location.replace(User.loginURL);
        });

        $(document).on('click', ".location-info", function () {

            var locationId = $(this).data('location');
            // add SITE to record id
            var location = User.locations['SITE' + locationId];
            var text = ''
            text += "<br><strong>Address:</strong> <a target='_blank' href='" + location[User.locationsEventId]['map_link'] + "'>" + location[User.locationsEventId]['testing_site_address'] + "</a>";
            text += "<br><strong>Details:</strong> " + location[User.locationsEventId]['site_details'];
            text += "<br><strong>Google Map Link:</strong> <a target='_blank' href='" + location[User.locationsEventId]['map_link'] + "'>" + location[User.locationsEventId]['map_link'] + "</a>";
            jQuery('#location-modal').find('.modal-title').html(location[User.locationsEventId]['title'] + " Information");
            jQuery('#location-modal').find('.modal-body').html(text);
            jQuery('#location-modal').css('top', '50%');
            $('#location-modal').modal('show');
        });

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

            // we need this to determine displaying the complete button or not.
            User.currentOffset = jQuery(this).data('offset');
            ;
            jQuery.ajax({
                'url': User.listURL + "&event_id=" + User.slotsEventId + "&user_timezone=" + User.userTimezone + "&baseline=" + jQuery(this).data('baseline') + "&offset=" + jQuery(this).data('offset') + "&affiliation=" + jQuery(this).data('affiliation') + "&canceled_baseline=" + jQuery(this).data('canceled-baseline') + "&reservation_event_id=" + jQuery(this).data('key') + "&record_id=" + jQuery(this).data('record-id'),
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
                            dom: '<"day-filter"><"location-filter"><lf<t>ip>',
                            data: data.data,
                            pageLength: 50,
                            "bDestroy": true,
                            "aaSorting": [[0, "asc"]],
                            initComplete: function () {
                                // we only need day and location filter.
                                this.api().columns([0, 1]).every(function (index) {

                                    var column = this;
                                    if (index === 0) {
                                        var select = $('<select id="day-options"><option value=""></option></select>')
                                            .appendTo($('.day-filter'))
                                            .on('change', function () {
                                                var val = $.fn.dataTable.util.escapeRegex(
                                                    $(this).val()
                                                );

                                                // set preferred location so it will be selected next time.
                                                column
                                                    .search(val ? '^' + val + '$' : '', true, false)
                                                    .draw();
                                            });
                                    }

                                    if (index === 1) {
                                        var select = $('<select id="location-options"><option value=""></option></select>')
                                            .appendTo($('.location-filter'))
                                            .on('change', function () {
                                                var val = $.fn.dataTable.util.escapeRegex(
                                                    $(this).val()
                                                );

                                                // set preferred location so it will be selected next time.
                                                setCookie('preferred-location', val, 365);
                                                column
                                                    .search(val ? '^' + val + '$' : '', true, false)
                                                    .draw();
                                            });
                                    }

                                    column.data().unique().sort().each(function (d, j) {
                                        select.append('<option value="' + d + '">' + d + '</option>')
                                    });

                                    // if preferred location is saved then select that
                                    if (getCookie('preferred-location') != null) {
                                        // need to check the preferred location does exist in the list.
                                        var exists = false;
                                        $('#location-options option').each(function () {
                                            if (this.value == getCookie('preferred-location')) {
                                                exists = true;
                                                return false;
                                            }
                                        });
                                        if (exists === true) {
                                            $("#location-options").val(getCookie('preferred-location')).trigger('change');
                                        }
                                    }
                                });
                            }
                        });
                        $('#generic-modal').modal('show');
                    } else {
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
                        //alert(response.message);
                        $('#booking').modal('hide');

                        // only show this for baseline visit.
                        if (User.currentOffset === 0) {
                            //$("#complete-section").show();
                            $('#complete-modal').modal('show');
                        }

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
                    User.loadUserVisits();
                    $('#generic-modal').modal('hide');
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
                var slot_id = jQuery(this).data('slot-id');
                /**
                 * Get Manage modal to let user manage their saved appointments
                 */
                jQuery.ajax({
                    url: User.cancelURL + '&record_id=' + record_id + '&event_id=' + event_id + '&reservation_slot_id=' + slot_id,
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

        /**
         * No Show appointment
         */
        jQuery(document).on('click', '.participants-no-show', function (e) {
            e.stopPropagation();
            e.preventDefault();
            e.stopImmediatePropagation();
            var participation_id = jQuery(this).data('participant-id');
            var event_id = jQuery(this).data('event-id');
            var url = jQuery('#participants-no-show-url').val();
            var status = jQuery(this).data('status');
            var notes = jQuery("#skip-notes").val();
            if (confirm("Are you sure you want to update the status of this reservation")) {

                /**
                 * Get Manage modal to let user manage their saved appointments
                 */
                jQuery.ajax({
                    url: url + '&participations_id=' + participation_id + "&event_id=" + event_id + "&reservation_participant_status=" + status + "&notes=" + notes,
                    type: 'GET',
                    datatype: 'json',
                    success: function (data) {
                        data = JSON.parse(data);
                        alert(data.message);
                    },
                    error: function (request, error) {
                        alert("Request: " + JSON.stringify(request));
                    },
                    'complete': function () {
                        User.loadUserVisits();
                        $('#skip-note-modal').modal('hide');
                    }
                });
            }
        });

        jQuery(document).on('click', '.skip-appointment', function (e) {
            $("#skip-appointment-form").attr('data-participant-id', $(this).data('participant-id'))
            $("#skip-appointment-form").attr('data-event-id', $(this).data('event-id'))
            $('#skip-note-modal').modal('show');
        });

        jQuery("#complete-schedule").click(function () {
            $('#complete-modal').modal('show');
        });

        setTimeout(function () {
            new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');
        }, 500);
    },
    calculateUserTimezone: function () {
        var offset = new Date().getTimezoneOffset();

        //offset = 420
        // only if not PST
        if (offset !== '480') {
            User.userTimezone = offset
            $("#timezone").text('Time(' + User.timezones[offset] + ')')
            $("#visits-timezone").text('Date(' + User.timezones[offset] + ')')

        }
    },
    loadUserVisits: function () {
        jQuery.ajax({
            'url': User.instancesListURL + "&user_timezone=" + User.userTimezone,
            'type': 'GET',
            'beforeSend': function () {
                /**
                 * remove any displayed calendar
                 */
                jQuery('.slots-container').html('');
            },
            'success': function (data) {
                $('#appointments').DataTable({
                    dom: '<"previous-filter"><lf<t>ip>',
                    data: data.data,
                    pageLength: 50,
                    "bDestroy": true,
                    columnDefs: [{
                        targets: 0,
                        visible: false
                    }],
                    // "aaSorting": [[0, "asc"]],
                    initComplete: function () {
                        // we only need day and location filter.
                        this.api().columns([2]).every(function (index) {
                            // below function will add filter to remove previous/completed appointments
                            var column = this;
                            $('<input type="checkbox" id="previous-filter" name="old" checked/>')
                                .appendTo($('.previous-filter'))
                                .on('change', function () {
                                    var val = $.fn.dataTable.util.escapeRegex(
                                        $(this).val()
                                    );
                                    if (document.getElementById('previous-filter').checked) {
                                        column
                                            .search("^$", true, false)
                                            .draw();
                                    } else {
                                        column
                                            .search("|Completed|Skipped|No Show", true, false)
                                            .draw();
                                    }

                                });

                        });
                    }
                });
            },
            'error': function (request, error) {
                var data = JSON.parse(request.responseText)
                alert(data.message);
            },
            'complete': function () {
                $("#previous-filter").trigger('change')
            }
        });
    },
    pad: function (number, length) {
        var str = "" + number
        while (str.length < length) {
            str = '0' + str
        }
        return str
    }
}
window.onload = function () {
    User.init();
}

function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

$body = jQuery("body");

jQuery(document).on({
    ajaxStart: function () {
        $body.addClass("loading");
    },
    ajaxStop: function () {
        $body.removeClass("loading");
    }
});