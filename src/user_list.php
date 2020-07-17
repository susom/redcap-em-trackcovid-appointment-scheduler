<?php

namespace Stanford\TrackCovidAppointmentScheduler;

/** @var \Stanford\TrackCovidAppointmentScheduler\TrackCovidAppointmentScheduler $module */

try {
    if ($user = $module->verifyCookie('login')) {
        $events = $module->getProject()->events['1']['events'];
        $url = $module->getUrl('src/list.php', true, true,
                true) . '&event_id=' . $module->getSlotsEventId() . '&' . COMPLEMENTARY_SUFFIX . '=' . $module->getSuffix();
        $result = array();
        $baseLine = $module->getProjectSetting('baseline-month');
        foreach ($events as $eventId => $event) {

            $year = date("Y");
            // we want to get the month to know which part of month and year to get.
            if ($event['day_offset'] > 0) {
                $offset = $event['day_offset'] / 30;
                $month = $baseLine + $offset;
            } else {
                $month = $baseLine;
            }

            // got next year
            if ($month > 12) {
                $month = 1;
                $year += 1;
            }

            $location = '';
            $row = array();
            //if we did not define reservation for this event skip it.
            if (!in_array('reservation', $module->getProject()->eventsForms[$eventId])) {
                continue;
            }
            // check if user has record for this event

            if (isset($user['record'][$eventId])) {
                $reservation = $module->getReservationArray($user['record'][$eventId]);
                if (empty($reservation)) {
                    $time = 'Not Scheduled';
                    $action = '<button data-month="' . $month . '"  data-year="' . $year . '" data-url="' . $url . '" data-record-id="' . $user['id'] . '" data-key="' . $eventId . '" class="get-list btn btn-success">Schedule</button>';
                } else {
                    $time = date('m/d/Y H:i', strtotime($reservation['start'])) . ' - ' . date('H:i',
                            strtotime($reservation['end']));
                    $locations = parseEnum($module->getProject()->metadata['location']['element_enum']);
                    $location = $locations[$reservation['location']];

                    // prevent cancel if appointment is in less than 24 hours
                    if (strtotime($reservation['start']) - time() < 86406) {
                        $action = 'This Appointment is in less than 24 hours please call to cancel!';
                    } else {
                        $action = '<button data-record-id="' . $user['id'] . '" data-key="' . $eventId . '" data-slot-id="' . $reservation['slot_id'] . '" class="cancel-appointment btn btn-danger">Cancel</button>';
                    }


                    //todo add message to competed or no show appointment.

                }

            } else {
                $time = 'Not Scheduled';
                $action = '<button data-month="' . $month . '" data-year="' . $year . '" data-record-id="' . $user['id'] . '" data-key="' . $eventId . '"  class="get-list btn btn-success">Schedule</button>';
            }
            $row[] = $event['descrip'];
            $row[] = $time;
            $row[] = $location;
            $row[] = $action;
            $result['data'][] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        throw new \LogicException("user not logged in");
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