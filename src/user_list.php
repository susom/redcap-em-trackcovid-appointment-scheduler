<?php

namespace Stanford\TrackCovidAppointmentScheduler;

/** @var \Stanford\TrackCovidAppointmentScheduler\TrackCovidAppointmentScheduler $module */

try {
    if ($user = $module->verifyCookie('login')) {
        $events = $module->getProject()->events['1']['events'];
        $url = $module->getUrl('src/list.php', true, true,
                true) . '&event_id=' . $module->getSlotsEventId() . '&' . COMPLEMENTARY_SUFFIX . '=' . $module->getSuffix();
        $result = array();
        foreach ($events as $eventId => $event) {
            if ($event['day_offset'] == 0) {
                $module->setBaseLine(true);
            } else {
                $module->setBaseLine(false);
            }
            list($month, $year) = $module->getEventMonthYear($event['day_offset']);

            $location = '';
            $row = array();
            //if we did not define reservation for this event skip it.
            if (!in_array('reservation', $module->getProject()->eventsForms[$eventId])) {
                continue;
            }
            // check if user has record for this event
            $status = 'Not Scheduled';
            if (isset($user['record'][$eventId])) {
                $reservation = $module->getReservationArray($user['record'][$eventId]);
                if (empty($reservation)) {
                    $time = '';
                    $action = $module->getScheduleActionButton($month, $year, $url, $user, $eventId,
                        $event['day_offset']);
                } else {

                    $time = date('D m Y H:i', strtotime($reservation['start'])) . ' - ' . date('H:i',
                            strtotime($reservation['end']));
                    $locations = parseEnum($module->getProject()->metadata['location']['element_enum']);

                    $location = $locations[$user['record'][$eventId]['participant_location']];

                    if ($module->isBaseLine()) {
                        $module->setBaseLineDate($reservation['start']);
                    }

                    // prevent cancel if appointment is in less than 24 hours
                    if (strtotime($reservation['start']) - time() < 86406) {
                        $action = 'This Appointment is in less than 24 hours please call to cancel!';
                    } else {
                        $action = $module->getCancelActionButton($user, $eventId, $reservation);
                    }

                    // determine the status
                    $statuses = parseEnum($module->getProject()->metadata['participant_status']["element_enum"]);
                    $status = $statuses[$user['record'][$eventId]['participant_status']];
                }

            } else {
                $time = '';
                $action = $module->getScheduleActionButton($month, $year, $url, $user, $eventId, $event['day_offset']);
            }
            $row[] = $event['descrip'];
            $row[] = $status;
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