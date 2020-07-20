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
            User.cancelURL = "<?php echo $module->getUrl('src/cancel.php', false,
                    true) . '&pid=' . $module->getProjectId() . '&NOAUTH'?>"
            User.userListURL = "<?php echo $module->getUrl('src/user_list.php', false, true) . '&NOAUTH'?>"
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
                    <th>Status</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>

        <?php
    } else {
        redirect($module->getUrl('src/login.php', true, false));
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