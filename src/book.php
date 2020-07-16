<?php

namespace Stanford\TrackCovidAppointmentScheduler;

use Sabre\DAV\Exception;

/** @var \Stanford\TrackCovidAppointmentScheduler\TrackCovidAppointmentScheduler $module */


try {
    /**
     * if survey booking with NOAUTH ignore login validation.
     */
    if (!defined('USERID') && !defined('NOAUTH')) {
        throw new \LogicException('Please login.');
    }

    $data = $module->sanitizeInput();
    //   if ($data['email' . $module->getSuffix()] == '' || $data['name' . $module->getSuffix()] == '' || $_POST['employee_id'] == '') {
    if ($data['email' . $module->getSuffix()] == '' || $data['name' . $module->getSuffix()] == '') {
        $error = '';
//        if ($_POST['employee_id'] == '') {
//            $error = 'Employee ID is missing';
//        }
        if ($data['name' . $module->getSuffix()] == '') {
            if ($error != '') {
                $error = ', Full Name is missing';
            } else {
                $error = 'Full Name is missing';
            }

        }
        if ($data['email' . $module->getSuffix()] == '') {
            if ($error != '') {
                $error = ', Email Name is missing';
            } else {
                $error = 'Email Name is missing';
            }
        }
        throw new \LogicException($error);
    } else {


        $data['participant_status' . $module->getSuffix()] = RESERVED;
        if (!isset($_POST['employee_id'])) {
            $data['employee_id' . $module->getSuffix()] = USERID;
        } else {
            $data['employee_id' . $module->getSuffix()] = filter_var($_POST['employee_id'], FILTER_SANITIZE_STRING);
        }

        $data['department' . $module->getSuffix()] = filter_var($_POST['department'], FILTER_SANITIZE_STRING);
        $data['supervisor_name' . $module->getSuffix()] = filter_var($_POST['supervisor_name'], FILTER_SANITIZE_STRING);
        $reservationEventId = $module->getReservationEventIdViaSlotEventId($data['event_id']);
        $slot = $module::getSlot(filter_var($_POST['record_id'], FILTER_SANITIZE_STRING), $data['event_id'],
            $module->getProjectId(), $module->getPrimaryRecordFieldName());


        // check if any slot is available
        $counter = $module->getParticipant()->getSlotActualCountReservedSpots(filter_var($_POST['record_id'],
            FILTER_SANITIZE_STRING),
            $reservationEventId, '', $module->getProjectId());

        if ((int)($slot['number_of_participants'] - $counter['counter']) <= 0) {
            throw new Exception("All time slots are booked please try different time");
        }

        if (defined('USERID')) {
            $userid = USERID;
        } else {
            $userid = $data['employee_id' . $module->getSuffix()];
        }

//        $module->doesUserHaveSameDateReservation($date, $userid, $module->getSuffix(), $data['event_id'],
//            $reservationEventId);
        /**
         * let mark it as complete so we can send the survey if needed.
         * Complete status has different naming convention based on the instrument name. so you need to get instrument name and append _complete to it.
         */
        $labels = \REDCap::getValidFieldsByEvents($module->getProjectId(), array($reservationEventId));
        $completed = preg_grep('/_complete$/', $labels);
        $second = array_slice($completed, 1, 1);  // array("status" => 1)

        $data[$second] = REDCAP_COMPLETE;

        // the location is defined in the slot.
        $data['participant_location' . $module->getSuffix()] = $slot['location'];

        $data['redcap_event_name'] = $module->getUniqueEventName($reservationEventId);
        if (!isset($_POST['survey_record_id']) || (isset($_POST['survey_record_id']) && $_POST['survey_record_id'] == "")) {
            $data[$module->getPrimaryRecordFieldName()] = $module->getNextRecordsId($reservationEventId,
                $module->getProjectId());
        } else {
            $data[$module->getPrimaryRecordFieldName()] = filter_var($_POST['survey_record_id'],
                FILTER_SANITIZE_STRING);
        }

        $response = \REDCap::saveData($module->getProjectId(), 'json', json_encode(array($data)));
        if (empty($response['errors'])) {

            //if slot has instructor identified then send email to the instructor
            if (!empty($slot['instructor'])) {
                $data['instructor'] = $slot['instructor'];
            }
            $return = $module->notifyUser($data);
            echo json_encode(array(
                'status' => 'ok',
                'message' => 'Appointment saved successfully!' . (isset($return['error']) ? ' with following errors' . $return['message'] : ''),
                'id' => array_pop($response['ids']),
                'email' => $data['email']
            ));
        } else {
            throw new Exception(implode(",", $response['errors']));
        }
    }
} catch (\LogicException $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
} catch (\Exception $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}