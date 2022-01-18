<?php

namespace Stanford\WISESharedAppointmentScheduler;

use REDCap;

/** @var \Stanford\WISESharedAppointmentScheduler\WISESharedAppointmentScheduler $module */


try {
    $suffix = $module->getSuffix();
    $primary = $module->getPrimaryRecordFieldName();
    $data[$primary] = filter_var($_POST['record_id'], FILTER_SANITIZE_STRING);
    $eventId = filter_var($_POST['event_id'], FILTER_SANITIZE_NUMBER_INT);
    if ($data[$primary] == '') {
        throw new \LogicException('Record ID is missing');
    }
    if ($eventId == '') {
        throw new \LogicException('Event ID is missing');
    }
    if (!$module::isUserHasManagePermission()) {
        throw new \LogicException('You should not be here');
    } else {
        $data['start' . $suffix] = date('Y-m-d H:i:s', strtotime(preg_replace("([^0-9/])", "", $_POST['start'])));
        $data['end' . $suffix] = date('Y-m-d H:i:s', strtotime(preg_replace("([^0-9/])", "", $_POST['end'])));
        $data['notes' . $suffix] = filter_var($_POST['notes'], FILTER_SANITIZE_STRING);
        //$data['instructor' . $suffix] = filter_var($_POST['instructor'], FILTER_SANITIZE_STRING);
        $data['location' . $suffix] = filter_var($_POST['location'], FILTER_SANITIZE_STRING);
        $data['redcap_event_name'] = $module->getScheduler()->getProject()->getUniqueEventNames($module->getScheduler()->getSlotsEventId());
        #$reservationEventId = $module->getReservationEventIdViaSlotEventId($eventId);
        $response = \REDCap::saveData($module->getScheduler()->getProject()->project_id, 'json', json_encode(array($data)));
        if (!empty($response['errors'])) {
            throw new \LogicException(implode("\n", $response['errors']));
        } else {
            $message['subject'] = $message['body'] = 'Your ' . $module->getUniqueEventName($data['event_id']) . ' at' . date('m/d/Y',
                    strtotime($data['start' . $suffix])) . ' has been updated';
//            $module->notifyParticipants($data['record_id'], $reservationEventId, $message);
            echo json_encode(array('status' => 'ok', 'message' => 'Slot Updated successfully!'));
        }
    }
} catch (\LogicException $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}