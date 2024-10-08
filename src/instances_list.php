<?php

namespace Stanford\TrackCovidSharedAppointmentScheduler;

/** @var \Stanford\TrackCovidSharedAppointmentScheduler\TrackCovidSharedAppointmentScheduler $module */

try {
    $id = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
    $userTimezone = filter_var($_GET['user_timezone'], FILTER_SANITIZE_STRING);
    if ($user = $module->verifyCookie('login', $id)) {
        $events = $module->getProject()->events['1']['events'];

        $module->setChildEligibility($user['record'][$module->getFirstEventId()]['elig_child']);
        $url = $module->getUrl('src/list.php', true, true,
                true) . '&event_id=' . $module->getSlotsEventId() . '&' . COMPLEMENTARY_SUFFIX . '=' . $module->getSuffix();
        $result = array();
        $noSkip = false;
        $regularUser = !defined('USERID') && !$module::isUserHasManagePermission();
        $statuses = parseEnum($module->getProject()->metadata['reservation_visit_status']["element_enum"]);
        foreach ($events as $eventId => $event) {
            $instance = $module->getSchedulerInstanceViaReservationId($eventId);
            //reset offset date
            $module->setOffsetDate('');
            if (empty($module->getSchedulerInstanceViaReservationId($eventId))) {
                continue;
            }

            $offset = $instance['offset-days'] != ''?$instance['offset-days']:$event['day_offset'];

            if ($offset == 0) {
                $module->setBaseLine(true);
            } else {
                $module->setBaseLine(false);
            }


            list($month, $year) = $module->getEventMonthYear($offset);

//            // for regular user skip the bonus visits. but not for coordinator
//            if ($event['day_offset'] >= 200 && $regularUser && $user['record'][$eventId]['reservation_datetime'] == '') {
//                continue;
//            } elseif ($event['day_offset'] >= 200) {
//                $event['day_offset'] = -1;
//                $module->setBonusVisit(true);
//            } else {
//                $module->setBonusVisit(false);
//            }

            $location = '';
            $canceledBaseline = false;
            $row = array();
            //if we did not define reservation for this event skip it.
            if (!in_array('reservation', $module->getProject()->eventsForms[$eventId])) {
                continue;
            }

            // verify logic is valid
            if (!$module->verifyInstanceLogic($eventId, $id)) {
                continue;
            }


            // check if user has record for this event
            $status = '';
            if (isset($user['record'][$eventId])) {
                $slot = $module->getReservationArray($user['record'][$eventId]);

                // capture edge case for imported reservation records with no slot id.
                if (empty($slot) && $user['record'][$eventId]['reservation_datetime'] != '') {
                    $slot['start'] = $user['record'][$eventId]['reservation_datetime'];
                    $slot['end'] = date('Y-m-d H:i:s', strtotime($user['record'][$eventId]['reservation_datetime']) + 60 * 15);
                }


                if (empty($slot)) {
                    $time = '';
                    if ($module->isAppointmentSkipped($user['record'][$eventId]['reservation_visit_status'])) {
                        $action = 'This appointment is skipped';
                        $noSkip = true;
                        // determine the status

                        $status = $statuses[$user['record'][$eventId]['reservation_visit_status']];
                        // for proto check if baseline was ever canceled.
                    } elseif ($user['record'][$eventId]['reservation_baseline_cancellation_date']) {
                        //if you reach this then the appointment was created then canceled.
                        if ($user['record'][$eventId]['reservation_baseline_cancellation_date']) {
                            $module->setBaseLineDate($user['record'][$eventId]['reservation_baseline_cancellation_date']);
                            $canceledBaseline = true;
                        }

                        $action = $module->getScheduleActionButton($month, $year, $url, $user, $eventId,
                            $offset, $canceledBaseline);

                        $module->setBaseLineDate('');
                        //} elseif ($module->isAppointmentNoShow($user['record'][$eventId]['reservation_visit_status']) || $user['record'][$eventId]['reservation_reschedule_counter'] != '') {
                    } else {
                        //when some forms in this event are filled but nothing related to reservation.
                        $action = $module->getScheduleActionButton($month, $year, $url, $user, $eventId,
                            $offset, $canceledBaseline);
                    }

                } else {

                    // update slot to user timezone
                    if ($userTimezone != $module->getPST()) {
                        $slot = $module->modifySlotBasedOnUserTimezone($slot, $userTimezone);
                    }

                    $time = date('D m/d/Y H:i', strtotime($slot['start'])) . ' - ' . date('H:i',
                            strtotime($slot['end'])) . ($userTimezone != $module->getPST() ? '<br><strong><small>' . date('h:i A', strtotime($slot['start_orig'])) . ' - ' . date('h:i A',
                                strtotime($slot['end_orig'])) . ' (PT)</small></strong>' : '');
                    $locations = $module->getDefinedLocations();

                    $location = $locations[$user['record'][$eventId]['reservation_participant_location']] . ' <svg class="location-info" data-location="' . $user['record'][$eventId]['reservation_participant_location'] . '" width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-patch-question" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM8.05 9.6c.336 0 .504-.24.554-.627.04-.534.198-.815.847-1.26.673-.475 1.049-1.09 1.049-1.986 0-1.325-.92-2.227-2.262-2.227-1.02 0-1.792.492-2.1 1.29A1.71 1.71 0 0 0 6 5.48c0 .393.203.64.545.64.272 0 .455-.147.564-.51.158-.592.525-.915 1.074-.915.61 0 1.03.446 1.03 1.084 0 .563-.208.885-.822 1.325-.619.433-.926.914-.926 1.64v.111c0 .428.208.745.585.745z"/><path fill-rule="evenodd" d="M10.273 2.513l-.921-.944.715-.698.622.637.89-.011a2.89 2.89 0 0 1 2.924 2.924l-.01.89.636.622a2.89 2.89 0 0 1 0 4.134l-.637.622.011.89a2.89 2.89 0 0 1-2.924 2.924l-.89-.01-.622.636a2.89 2.89 0 0 1-4.134 0l-.622-.637-.89.011a2.89 2.89 0 0 1-2.924-2.924l.01-.89-.636-.622a2.89 2.89 0 0 1 0-4.134l.637-.622-.011-.89a2.89 2.89 0 0 1 2.924-2.924l.89.01.622-.636a2.89 2.89 0 0 1 4.134 0l-.715.698a1.89 1.89 0 0 0-2.704 0l-.92.944-1.32-.016a1.89 1.89 0 0 0-1.911 1.912l.016 1.318-.944.921a1.89 1.89 0 0 0 0 2.704l.944.92-.016 1.32a1.89 1.89 0 0 0 1.912 1.911l1.318-.016.921.944a1.89 1.89 0 0 0 2.704 0l.92-.944 1.32.016a1.89 1.89 0 0 0 1.911-1.912l-.016-1.318.944-.921a1.89 1.89 0 0 0 0-2.704l-.944-.92.016-1.32a1.89 1.89 0 0 0-1.912-1.911l-1.318.016z"/></svg>';

                    if ($module->isBaseLine()) {
                        //use baseline appointment
                        $module->setBaseLineDate($slot['start']);

                    } else {
                        if ($instance['offset-date-field'] && $user['record'][$instance['offset-date-field-event']][$instance['offset-date-field']]) {
                            $module->setOffsetDate($user['record'][$instance['offset-date-field-event']][$instance['offset-date-field']]);
                        }
                    }

                    // for stanford participants do not allow schedule non-baseline visits
//                    if (false) {
//                        $action = 'Please schedule your next appointments using MyHealth App. Please note your MyHealth Account will be created for you on your baseline visits.';
//                    } else {
                    // prevent cancel if appointment is in less than 48 hours
//                    if (strtotime($slot['start']) - time() < 172812 && strtotime($slot['start']) - time() > 0 && !$module->isBonusVisit()) {
//                        $action = 'This Appointment is in less than 48 hours please call to cancel!';
//                    } else
                    if ($user['record'][$eventId]['reservation_visit_status'] == 1) {
                        $action = 'Appointment Completed';
                    } elseif ($user['record'][$eventId]['reservation_participant_status'] == RESERVED && !$module->isBonusVisit()) {
                        $action = $module->getCancelActionButton($user, $eventId, $slot);
                    } elseif ($user['record'][$eventId]['reservation_participant_status'] == RESERVED && $module->isBonusVisit()) {
                        $action = 'To cancel please call us!';
//                        } elseif ($module->isAppointmentSkipped($user['record'][$eventId]['reservation_visit_status'])) {
//                            $action = 'This appointment is skipped';
//                            $noSkip = true;
                    } elseif ($module->isAppointmentNoShow($user['record'][$eventId]['reservation_visit_status'])) {
                        $action = $module->getScheduleActionButton($month, $year, $url, $user, $eventId, $offset);
                    }
                    //}


                    // determine the status
                    $status = $statuses[$user['record'][$eventId]['reservation_visit_status']];
                }

            } else {
                $time = '';

                if (!$module->isBaseLine()) {

                    if ($instance['offset-date-field'] && $user['record'][$instance['offset-date-field-event']][$instance['offset-date-field']]) {
                        $module->setOffsetDate($user['record'][$instance['offset-date-field-event']][$instance['offset-date-field']]);
                    }
                }

                $action = $module->getScheduleActionButton($month, $year, $url, $user, $eventId, $offset);


            }

            //when manager is viewing this page give the option to skip this visit.
            if (!$regularUser && !$noSkip) {
                $action .= $module->getSkipActionButton($user, $eventId);
            }

            $row[] = $offset;
            //$row[] = $event['descrip'];
            $row[] = $module->getProject()->eventInfo[$eventId]['custom_event_label'];
            $row[] = $status;
            $row[] = $time;
            $row[] = $location;
            $row[] = $action;
            $result['data'][] = $row;
            $noSkip = false;
            $action = '';
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