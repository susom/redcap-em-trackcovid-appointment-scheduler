<?php

namespace Stanford\TrackCovidAppointmentScheduler;

/** @var \Stanford\TrackCovidAppointmentScheduler\TrackCovidAppointmentScheduler $module */

try {
    if ($user = $module->verifyCookie('login')) {
        //JS and CSS with inputs URLs
        define('USERID', $user['record'][$module->getFirstEventId()]['full_name']);
        $recordId = $user['id'];
        $url = $module->getUrl('src/list.php', true, true,
                true) . '&event_id=' . $module->getSlotsEventId() . '&' . COMPLEMENTARY_SUFFIX . '=' . $module->getSuffix();
        require_once 'urls.php';
        ?>
        <link rel="stylesheet" href="<?php echo $module->getUrl('src/css/types.css', true, true) ?>">
        <script src="<?php echo $module->getUrl('src/js/user.js', true, true) ?>"></script>
        <script>
            User.listURL = "<?php echo $url ?>"
            User.slotsEventId = "<?php echo $module->getSlotsEventId() ?>"
            User.submitURL = "<?php echo $module->getUrl('src/book.php', false,
                    true) . '&pid=' . $module->getProjectId() . '&NOAUTH' ?>"
        </script>
        <div id="brandbar">
            <div class="container">
                <div class="row">
                    <div class="col-3">
                        <a href="http://www.stanford.edu"><img
                                    src="https://www-media.stanford.edu/su-identity/images/brandbar-stanford-logo@2x.png"
                                    alt="Stanford University" width="152" height="23"></a>
                    </div>
                    <div class="col-9">
                        <nav class="navbar-expand-sm navbar-dark">
                            <div class="collapse navbar-collapse justify-content-end" id="navbarCollapse">
                                <?php
                                if (defined('USERID')) {
                                    ?>
                                    <ul class="navbar-nav">
                                        <li class="nav-item active">
                                            <a class="nav-link" href="#">Logged in
                                                as: <?php echo(defined('USERID') ? USERID : ' NOT LOGGED IN') ?></a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="manage nav-link" href="#">Manage my Appointments</a>
                                        </li>
                                    </ul>
                                    <?php
                                }
                                ?>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <table id="appointments" class="display table table-striped table-bordered"
                   cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th>Visit</th>
                    <th>Time</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $events = $module->getProject()->events['1']['events'];

                foreach ($events as $eventId => $event) {
                    $location = '';
                    //if we did not define reservation for this event skip it.
                    if (!in_array('reservation', $module->getProject()->eventsForms[$eventId])) {
                        continue;
                    }
                    // check if user has record for this event

                    if (isset($user['record'][$eventId])) {
                        $reservation = $module->getReservationArray($user['record'][$eventId]);
                        if (empty($reservation)) {
                            $time = 'Not Scheduled';
                            $action = '<button data-url="' . $url . '" data-record-id="' . $recordId . '" data-key="' . $eventId . '" class="survey-type btn btn-success">Schedule</button>';
                        } else {
                            $time = date('m/d/Y H:i', strtotime($reservation['start']));
                            $locations = parseEnum($module->getProject()->metadata['location']['element_enum']);
                            $location = $locations[$reservation['location']];
                            $action = '<button data-record-id="' . $user['id'] . '" data-key="' . $eventId . '" data-slot-id="' . $reservation['slot_id'] . '" class="cancel-appointment btn btn-danger">Cancel</button>';
                        }

                    } else {
                        $time = 'Not Scheduled';
                        $action = '<button data-url="' . $url . '" data-record-id="' . $recordId . '" data-key="' . $eventId . '"  class="survey-type btn btn-success">Schedule</button>';
                    }
                    ?>
                    <tr>
                        <td><?php echo $event['descrip'] ?></td>
                        <td><?php echo $time ?></td>
                        <td><?php echo $location ?></td>
                        <td><?php echo $action ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>

        <?php
    } else {
        redirect($module->getUrl('src/login.php', false, false));
    }

    require_once 'models.php';
    ?>
    <div class="loader"><!-- Place at bottom of page --></div>
    <?php
} catch (\LogicException $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
} catch (\Exception $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}