<?php

namespace Stanford\TrackCovidSharedAppointmentScheduler;

use REDCap;

/** @var \Stanford\TrackCovidSharedAppointmentScheduler\TrackCovidSharedAppointmentScheduler $module */


try {
    $suffix = $module->getSuffix();
    $primary = $module->getScheduler()->getProject()->table_pk;
    $data[$primary] = filter_var($_GET['record_id'], FILTER_SANITIZE_STRING);
    $eventId = filter_var($_GET['event_id'], FILTER_SANITIZE_NUMBER_INT);
    if ($data[$primary] == '') {
        throw new \LogicException('Record ID is missing');
    }
    if ($eventId == '') {
        throw new \LogicException('Event ID is missing');
    }
    if (!$module::isUserHasManagePermission()) {
        throw new \LogicException('You should not be here');
    } else {
        $data['slot_status' . $suffix] = CANCELED;
        $data['number_of_participants'] = 0;
        #$data['redcap_event_name'] = $module->getScheduler()->getSlotsEventId();
        $response = \REDCap::saveData($module->getScheduler()->getProject()->project_id, 'json', json_encode(array($data)));
        if (!empty($response['errors'])) {
            if (is_array($response['errors'])) {
                throw new \Exception(implode(",", $response['errors']));
            } else {
                throw new \Exception($response['errors']);
            }
        } else {

            $slot = $module->getSlot($data[$primary], $module->getScheduler()->getSlotsEventId());
            $message['subject'] = $message['body'] = 'Your reservation at ' . date('m/d/Y',
                    strtotime($slot['start' . $suffix])) . ' at ' . date('H:i',
                    strtotime($slot['start' . $suffix])) . ' to ' . date('H:i',
                    strtotime($slot['end' . $suffix])) . ' has been canceled';
            $reservationEventId = $module->getReservationEventIdViaSlotEventId($eventId);

            $module->notifyParticipants($data[$primary], $reservationEventId, $message);
            echo json_encode(array('status' => 'ok', 'message' => 'Slot canceled successfully!'));
        }
    }
} catch (\LogicException $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}