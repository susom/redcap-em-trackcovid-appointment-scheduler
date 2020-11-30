<?php


namespace Stanford\TrackCovidSharedAppointmentScheduler;

/** @var \Stanford\TrackCovidSharedAppointmentScheduler\TrackCovidSharedAppointmentScheduler $module */

try {
    /**
     * check if user still logged in
     */
    if (!$module::isUserHasManagePermission()) {
        throw new \LogicException('You cant be here');
    }

    //get records for all reservations.
    $records = $module->getParticipant()->getAllReservedSlots($module->getProjectId(),
        array_keys($module->getProject()->events['1']['events']));
    $statuses = parseEnum($module->getProject()->metadata['visit_status']["element_enum"]);
    //get all open time slots so we can exclude past reservations.
    $slots = $module->getAllOpenSlots();
    $firstEvent = $module->getFirstEventId();
    $locations = $module->getDefinedLocations();
    $trackcovid_monthly_followup_survey_complete_statuses = parseEnum($module->getProject()->metadata['trackcovid_monthly_followup_survey_complete']['element_enum']);
    $managerURL = $module->getProjectSetting('manager-scheduler-url');
    $visitSummary = $module->getProjectSetting('visit-summary-instrument');
    $url = $module->getUrl('src/user.php', false,
        true);
    if ($records) {
        ?>
        <div class="container-fluid">
            <table id="booked-slots" class="display">
                <thead>
                <tr>
                    <th>Record ID</th>
                    <th>Demographics Information</th>
                    <th>Visit type</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Appointment time</th>
                    <th>Consent status</th>
                    <th>Survey status</th>
                    <th>Visit status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($records as $id => $events) {
                    $user = $module->getParticipant()->getUserInfo($id, $firstEvent);
                    foreach ($events as $eventId => $record) {

                        //skip past, skipped or empty reservation
                        #if (empty($record['reservation_datetime']) || $module->isReservationInPast($record['reservation_datetime']) || $module->isAppointmentSkipped($record['visit_status'])) {
                        if (empty($record['reservation_datetime']) || $module->isReservationInPast($record['reservation_datetime'])) {
                            continue;
                        }
                        //if past reservation we do not want to see it.
                        //exception for imported reservation.
                        if (empty($record['reservation_slot_id'])) {
                            $slot['start'] = $record['reservation_datetime'];
                            // because we do not know the end of the lost we assumed its 15 minutes after the start
                            $slot['_end'] = date('Y-m-d H:i:s', strtotime($record['reservation_datetime']) + 900);
                        } else {
                            //if past reservation we do not want to see it.
                            if (!array_key_exists($record['reservation_slot_id'], $slots)) {
                                continue;
                            } else {
                                $slot = end($slots[$record['reservation_slot_id']]);
                            }
                        }


                        if ($record['trackcovid_baseline_survey_complete']) {
                            $status = $trackcovid_monthly_followup_survey_complete_statuses[$record['trackcovid_baseline_survey_complete']];
                        } elseif ($record['trackcovid_monthly_followup_survey_complete']) {
                            $status = $trackcovid_monthly_followup_survey_complete_statuses[$record['trackcovid_monthly_followup_survey_complete']];
                        } else {
                            $status = 'Incomplete';
                        }
                        ?>
                        <tr>
                            <td><?php echo $id ?></td>
                            <td>
                                <div class="row"><?php echo $user['first_name'] . ' ' . $user['last_name'] ?>
                                    DOB:<?php echo $user['dob'] ? date('m/d/Y', strtotime($user['dob'])) : '' ?></div>
                                <div class="row"><?php echo $user['email'] ?> </div>
                                <div class="row"><?php echo $user['phone_number'] ?></div>
                                <div class="row">
                                    MRN: <?php echo $user['mrn_ucsf'] ? $user['mrn_ucsf'] : $user['mrn_stanford'] ?></div>
                            </td>
                            <td><?php echo $module->getProject()->events[1]['events'][$eventId]['descrip'] ?></td>
                            <!--                            <td>-->
                            <td><?php echo $locations[$record['reservation_participant_location']] ? $locations[$record['reservation_participant_location']] : 'N/A'; ?></td>
                            <td><?php echo date('m/d/Y', strtotime($slot['start'])) ?></td>
                            <td><?php echo date('H:i', strtotime($slot['start'])) . ' - ' . date('H:i',
                                        strtotime($slot['end'])) ?></td>
                            <td><?php echo $user['consent_date'] || $user['consent_date_esp'] || $user['consent_date_chi'] || $user['consent_date_tag'] || $user['consent_date'] ? 'Completed' : 'Incomplete' ?></td>
                            <td><?php echo $status ?></td>
                            <td><?php echo $statuses[$record['visit_status']]; ?></td>
                            <td>
                                <button data-participant-id="<?php echo $id ?>"
                                        data-event-id="<?php echo $eventId ?>"
                                        data-reservation-slot-id="<?php echo $record['reservation_slot_id'] ?>"
                                        data-status="<?php echo false ?>"
                                        class="participants-no-show btn btn-sm btn-danger">Cancel
                                </button>
                                <div class="clear"></div>
                                <strong><a target="_blank" href="<?php echo rtrim(APP_PATH_WEBROOT_FULL,
                                            '/') . APP_PATH_WEBROOT . 'DataEntry/index.php?pid=' . $module->getProjectId() . '&page=' . $visitSummary . '&id=' . $id . '&event_id=' . $eventId ?>">Go
                                        to Visit Summary</a></strong>
                                <div class="clear"></div>
                                <strong><a target="_blank"
                                           href="<?php echo $url . $module->replaceRecordLabels('&' . $managerURL, $user) ?>">Go
                                        to Scheduling Page</a></strong>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>


        </div>
        <?php
    } else {
        echo 'No saved participation for you';
    }
} catch (\LogicException $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
?>
<!-- LOAD JS -->
<script src="<?php echo $module->getUrl('src/js/manage_calendar.js') ?>"></script>

