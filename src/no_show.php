<?php

namespace Stanford\TrackCovidAppointmentScheduler;

/** @var \Stanford\TrackCovidAppointmentScheduler\TrackCovidAppointmentScheduler $module */


try {
    /**
     * check if user still logged in
     */
    if (!$module::isUserHasManagePermission()) {
        throw new \LogicException('You cant be here');
    }
    $primaryField = \REDCap::getRecordIdField();
    $data[$primaryField] = filter_var($_GET[$primaryField], FILTER_SANITIZE_NUMBER_INT);
    $data['participant_status'] = filter_var($_GET['participant_status'], FILTER_SANITIZE_NUMBER_INT);

    $eventId = filter_var($_GET['event_id'], FILTER_SANITIZE_NUMBER_INT);
    if ($data[$primaryField] == '') {
        throw new \LogicException('Participation ID is missing');
    }
    if ($eventId == '') {
        throw new \LogicException('Event ID is missing');
    } else {

        $data['redcap_event_name'] = \REDCap::getEventNames(true, true, $eventId);
        $response = \REDCap::saveData('json', json_encode(array($data)));

        if (empty($response['errors'])) {
            //TODO notify instructor about the cancellation
            echo json_encode(array('status' => 'ok', 'message' => 'Appointment mark No Show!'));
        } else {
            throw new \LogicException(implode(",", $response['errors']));
        }
    }
} catch (\LogicException $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}