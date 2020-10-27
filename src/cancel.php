<?php

namespace Stanford\TrackCovidSharedAppointmentScheduler;

/** @var \Stanford\TrackCovidSharedAppointmentScheduler\TrackCovidSharedAppointmentScheduler $module */


try {
    $primary = $module->getPrimaryRecordFieldName();
    $data[$primary] = $_GET[$primary];
    $eventId = filter_var($_GET['event_id'], FILTER_SANITIZE_NUMBER_INT);
    $slotId = filter_var($_GET['reservation_slot_id'], FILTER_SANITIZE_STRING);
    if ($data[$primary] == '') {
        throw new \LogicException('Participation ID is missing');
    } else {
        $data['reservation_slot_id'] = false;
        $data['reservation_participant_id'] = false;
        $data['reservation_datetime'] = false;
        $data['reservation_date'] = false;
        $data['reservation_site_affiliation'] = false;
        $data['reservation_participant_location'] = false;
        $data['reservation_participant_status'] = false;
        $data['visit_status'] = false;
        // get previous notes
        $data['summary_notes'] = $module->getRecordSummaryNotes($data[$primary],
                $eventId) . '&#13;&#10;[' . date('Y-m-d H:i:s') . ']: Appointment was canceled';

        $rescheduleCounter = $module->getRecordRescheduleCounter($data[$module->getPrimaryRecordFieldName()], $eventId);
        if ($rescheduleCounter == '') {
            $data['reservation_reschedule_counter'] = 0;

            //if this cancellation for baseline visit then use its date as med point for the window.
            if ($module->getProject()->events['1']['events'][$eventId]['day_offset'] == 0) {
                $data['reservation_baseline_cancellation_date'] = $module->getRecordReservationDateTime($data[$primary], $eventId);
            }

        }

        $data['redcap_event_name'] = $module->getUniqueEventName($eventId);
        $response = \REDCap::saveData($module->getProjectId(), 'json', json_encode(array($data)), 'overwrite');
        if (empty($response['errors'])) {

            $slot = $module->getSlot($slotId, $module->getScheduler()->getSlotsEventId());
            // update booked spots
            $module->getScheduler()->updateSlotBookedSpots($slot, -1);

            //TODO notify instructor about the cancellation
            echo json_encode(array('status' => 'ok', 'message' => 'Appointment canceled successfully!'));
        } else {
            if (is_array($response['errors'])) {
                throw new \Exception(implode(",", $response['errors']));
            } else {
                throw new \Exception($response['errors']);
            }
        }

    }
} catch (\LogicException $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}