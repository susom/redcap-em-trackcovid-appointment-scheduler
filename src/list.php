<?php

namespace Stanford\WISESharedAppointmentScheduler;

/** @var \Stanford\WISESharedAppointmentScheduler\WISESharedAppointmentScheduler $module */

$id = filter_var($_GET['record_id'], FILTER_SANITIZE_STRING);
$suffix = $module->getSuffix();
$eventId = filter_var($_GET['event_id'], FILTER_SANITIZE_NUMBER_INT);
$reservationEventId = filter_var($_GET['reservation_event_id'], FILTER_SANITIZE_NUMBER_INT);
//$month = filter_var($_GET['month'], FILTER_SANITIZE_NUMBER_INT);
//$year = filter_var($_GET['year'], FILTER_SANITIZE_NUMBER_INT);
$baseline = filter_var($_GET['baseline'], FILTER_SANITIZE_STRING);
$offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT);
$affiliation = filter_var($_GET['affiliation'], FILTER_VALIDATE_INT);
$canceledBaseline = filter_var($_GET['canceled_baseline'], FILTER_VALIDATE_INT);
$data = $module->getMonthSlots($eventId, null, null, $baseline, $offset, $affiliation, $canceledBaseline, $reservationEventId);
$result = array();
$result['data'] = array();
$user = $module->verifyCookie('login', $id);
if (!empty($data)) {
    #$reservationEventId = $module->getReservationEventIdViaSlotEventId($eventId);
    /**
     * prepare data
     */
    foreach ($data as $record_id => $slot) {
        $slot = array_pop($slot);

        /**
         * group by day
         */
        $day = date('d', strtotime($slot['start' . $suffix]));

        /**
         * skip for blackout dates if specified
         */

        if ($module->getProjectSetting('blackout_start') != '' && $module->getProjectSetting('blackout_end') != '') {
            $date = date('Y-m-d', strtotime($slot['start' . $suffix]));
            $cohort = true;

            if ($module->getProjectSetting('blackout_cohort') != '') {
                $cohort = $user['record'][$module->getFirstEventId()]['cohort'] == $module->getProjectSetting('blackout_cohort');
            }

            if ($cohort && strtotime($date) >= strtotime($module->getProjectSetting('blackout_start')) && strtotime($date) <= strtotime($module->getProjectSetting('blackout_end'))) {
                continue;
            }
        }
        /**
         * skip past slots.
         */
        if ($module->isSlotInPast($slot, $suffix)) {
            continue;
        }

        /**
         * if the record id has different name just use whatever is provided.
         */
        if (!isset($slot['record_id'])) {
            $slot['record_id'] = array_pop(array_reverse($slot));
        }

        $module->emLog($slot['start' . $suffix]);
//        $counter = $module->getParticipant()->getSlotActualCountReservedSpots($slot['record_id'],
//            $module->getReservationEvents(), $suffix, $module->getProjectId(), $slot);

        $available = (int)($slot['number_of_participants' . $suffix] - ($slot['number_of_booked_slots'] + $slot['number_of_external_booked_slots']));;
//        $module->emLog($slot['start' . $suffix] . ' available' . $available);
        if ($available <= 0) {
            continue;
        }

        $cancelButton = '';
        $bookButton = '<button type="button"
                                        data-record-id="' . $slot['record_id'] . '"
                                        data-event-id="' . $eventId . '"
                                        data-notes-label="' . $module->getNoteLabel() . '"
                                        data-show-projects="' . $module->showProjectIds() . '"
                                        data-show-attending-options="' . $module->showAttendingOptions() . '"
                                        data-show-location-options="' . $module->showLocationOptions() . '"
                                        data-show-attending-default="' . $module->getDefaultAttendingOption() . '"
                                        data-show-locations="' . (empty($slot['attending_options' . $suffix]) ? CAMPUS_AND_VIRTUAL : $slot['attending_options' . $suffix]) . '"
                                        data-show-notes="' . $module->showNotes() . '"
                                        data-date="' . date('Ymd', strtotime($slot['start' . $suffix])) . '"
                                        data-start="' . date('Hi', strtotime($slot['start' . $suffix])) . '"
                                        data-end="' . date('Hi', strtotime($slot['end' . $suffix])) . '"
                                        data-modal-title="' . date('M/d/Y',
                strtotime($slot['start' . $suffix])) . ' ' . date('h:i A',
                strtotime($slot['start' . $suffix])) . ' - ' . date('h:i A', strtotime($slot['end' . $suffix])) . '"
                                        class="time-slot btn btn-block btn-success">Book
                                </button>';

        $row = array();
        $row[] = date('m/d/Y', strtotime($slot['start' . $suffix]));
        $row[] = $module->getLocationLabel($slot['location' . $suffix]);;
        $row[] = date('h:i A', strtotime($slot['start' . $suffix])) . ' - ' . date('h:i A',
                strtotime($slot['end' . $suffix]));;
        $row[] = '<h5 class="text-center">' . $available . '</h5>';;
        $row[] = $bookButton . $cancelButton;;

        $result['data'][] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($result);
}