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

    //get records for all reservations.
    $records = $module->getParticipant()->getAllReservedSlots($module->getProjectId());

    //get all open time slots so we can exclude past reservations.
    $slots = $module->getAllOpenSlots();
    if ($records) {
        ?>
        <div class="container">
            <table id="booked-slots" class="display">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($records as $id => $events) {
                    $user = $module->getParticipant()->getUserInfo($id, $module->getFirstEventId());
                    foreach ($events as $eventId => $record) {
                        //if past reservation we do not want to see it.
                        if (!array_key_exists($record['slot_id'], $slots)) {
                            continue;
                        } else {
                            $slot = end($slots[$record['slot_id']]);
                        }
                        $locations = parseEnum($module->getProject()->metadata['location']['element_enum']);
                        ?>
                        <tr>
                            <td><?php echo $user['full_name'] ?></td>
                            <td><?php echo $user['email'] ?></td>
                            <td><?php echo $user['phone_number'] ?></td>
                            <!--                            <td>-->
                            <td><?php echo $locations[$record['participant_location']]; ?></td>
                            <td><?php echo date('m/d/Y', strtotime($slot['start'])) ?></td>
                            <td><?php echo date('H:i', strtotime($slot['start'])) ?></td>
                            <td><?php echo date('H:i', strtotime($slot['end'])) ?></td>
                            <td>
                                <select data-participant-id="<?php echo $id ?>"
                                        data-event-id="<?php echo $eventId ?>"
                                        class="participants-no-show">
                                    <option>CHANGE STATUS</option>
                                    <?php
                                    foreach ($module->getParticipantStatus() as $key => $status) {
                                        // list all statuses from reservation instrument. update comment.
                                        ?>
                                        <option value="<?php echo $key ?>" <?php echo($record['participant_status'] == $key ? 'selected' : '') ?>><?php echo $status ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
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

