<?php

namespace Stanford\TrackCovidSharedAppointmentScheduler;

use REDCap;

/** @var \Stanford\TrackCovidSharedAppointmentScheduler\TrackCovidSharedAppointmentScheduler $module */


try {
    $recordId = filter_var($_POST['participant_id'], FILTER_SANITIZE_STRING);
    if ($user = $module->verifyCookie('login', $recordId)) {
        /**
         * if survey booking with NOAUTH ignore login validation.
         */
        if (!defined('USERID') && !defined('NOAUTH')) {
            throw new \LogicException('Please login.');
        }

        $data = $module->sanitizeInput();


        $data['reservation_participant_status' . $module->getSuffix()] = RESERVED;
        if (!isset($_POST['participant_id'])) {
            $data['reservation_participant_id'] = USERID;
        } else {
            $data['reservation_participant_id'] = filter_var($_POST['participant_id'], FILTER_SANITIZE_STRING);
        }
        $reservationEventId = filter_var($_POST['reservation_event_id'], FILTER_VALIDATE_INT);
        $slot = $module->getSlot(filter_var($_POST['record_id'], FILTER_SANITIZE_STRING), $module->getScheduler()->getSlotsEventId());

        $userTimezone = filter_var($_POST['usertimezone'], FILTER_SANITIZE_STRING);
        $instance = $module->getSchedulerInstanceViaReservationId($reservationEventId);

        if ($userTimezone != $module->getPST()) {
            $slot = $module->modifySlotBasedOnUserTimezone($slot, $userTimezone);
        }

        // check if any slot is available
        $counter = $module->getParticipant()->getSlotActualCountReservedSpots(filter_var($_POST['record_id'],
            FILTER_SANITIZE_STRING),
            $reservationEventId, '', $module->getProjectId(), $slot);
        if ((int)($slot['number_of_participants'] - $counter['counter']) <= 0) {
            throw new \Exception("All time slots are booked please try different time");
        }


//        $module->doesUserHaveSameDateReservation($date, $userid, $module->getSuffix(), $data['event_id'],
//            $reservationEventId);
        /**
         * let mark it as complete so we can send the survey if needed.
         * Complete status has different naming convention based on the instrument name. so you need to get instrument name and append _complete to it.
         */
        $reservation = end($module->getProject()->eventsForms[$reservationEventId]);
        $data[$reservation . '_complete'] = REDCAP_COMPLETE;  // array("status" => 1)

        // the location is defined in the slot.
        $data['reservation_participant_location' . $module->getSuffix()] = $slot['location'];

        // find out what is the site affiliation.
        $locations = $module->getLocationRecords();

        // if user has different timezone. use start_orig from slot.
        $data['reservation_datetime'] = $slot['start_orig'] ?? $slot['start'];
        $data['reservation_date'] = date('Y-m-d', strtotime($slot['start']));
        $data['reservation_created_at'] = date('Y-m-d H:i:s');


        $data['redcap_event_name'] = $module->getUniqueEventName($reservationEventId);
        $data[$module->getPrimaryRecordFieldName()] = filter_var($_POST['participant_id'],
            FILTER_SANITIZE_STRING);

        // if this appointment was scheduled before make sure to count that.

        $rescheduleCounter = $module->getRecordRescheduleCounter($data[$module->getPrimaryRecordFieldName()],
            $reservationEventId);
        if ($rescheduleCounter != '') {
            $data['reservation_reschedule_counter'] = $rescheduleCounter + 1;
        }


        $response = \REDCap::saveData($module->getProjectId(), 'json', json_encode(array($data)));
        if (empty($response['errors'])) {

            // update booked spots
            $module->getScheduler()->updateSlotBookedSpots($slot);

            // add email and mobile to notify the user about
            if ($instance['receiver_email_field'] && $user['record'][$reservationEventId][$instance['receiver_email_field']]) {
                $data['email'] = $user['record'][$reservationEventId][$instance['receiver_email_field']];
            } elseif ($user['record'][$module->getFirstEventId()][$module->getProject()->project['survey_email_participant_field']] != '') {
                $data['email'] = $user['record'][$module->getFirstEventId()][$module->getProject()->project['survey_email_participant_field']];
            }

            $data['mobile'] = $user['record'][$reservationEventId][$module->getProject()->project['survey_phone_participant_field']];
            $data['newuniq'] = $user['id'];
            $return = $module->notifyUser($data, $reservationEventId, $slot);
            echo json_encode(array(
                'status' => 'ok',
                'message' => 'Appointment saved successfully!' . (isset($return['error']) ? ' with following errors' . $return['message'] : ''),
                'id' => array_pop($response['ids']),
                'email' => $data['email']
            ));
        } else {
            if (is_array($response['errors'])) {
                throw new \Exception(implode(",", $response['errors']));
            } else {
                throw new \Exception($response['errors']);
            }

        }
    } else {
        throw new \LogicException("User not login");
    }

} catch (\LogicException $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
} catch (\Exception $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}