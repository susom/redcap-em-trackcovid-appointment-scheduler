<?php

namespace Stanford\TrackCovidAppointmentScheduler;

/** @var \Stanford\TrackCovidAppointmentScheduler\TrackCovidAppointmentScheduler $module */

use REDCap;

//JS and CSS with inputs URLs
require_once 'urls.php';
if (!isset($_COOKIE['participant_login'])) {
    ?>
    <link rel="stylesheet" href="<?php echo $module->getUrl('src/css/verification_form.css', true, true) ?>">
    <script src="<?php echo $module->getUrl('src/js/login.js', true, true) ?>"></script>
    <script>
        Form.ajaxURL = "<?php echo $module->getUrl("src/verify.php", true,
                true) . '&pid=' . $module->getProjectId() . '&NOAUTH'?>"
    </script>
    <div id="new-form" class="container">
        <div class="row col-10 offset-1">
            <div class="row">
                <div style="padding:10px 0 0;"><img id="survey_logo"
                                                    src="https://redcap.stanford.edu/surveys/index.php?pid=20184&amp;doc_id_hash=a825369bc1de8a7ce50ec7a5765c7fee0a79fc65&amp;__passthru=DataEntry%2Fimage_view.php&amp;s=DPXJ7FAJCX&amp;id=924268"
                                                    alt="image" title="image"
                                                    style="max-width:600px;width:559px;max-width:559px;height:145px;max-height:145px;">
                </div>
            </div>
        </div>
        <div id="errors" class="row col-10 offset-1 text-left alert alert-danger" style="display: none"></div>
        <section><h2>Enter your ID (8 Characters)</h2>
            <div class="row">
                <div class="col-1"><input data-num="1" data-type="newuniq"
                                          class="newuniq overflow-auto form-control p-0" type="text" maxLength="1"
                                          size="5" pattern="[0-9]{1}"/></div>
                <div class="col-1"><input data-num="2" data-type="newuniq"
                                          class="newuniq overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" " />
                </div>
                <div class="col-1"><input data-num="3" data-type="newuniq"
                                          class="newuniq overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5"/></div>
                <div class="col-1"><input data-num="4" data-type="newuniq"
                                          class="newuniq overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" pattern="[0-9]{1}"/></div>
                <div class="col-1"><input data-num="5" data-type="newuniq"
                                          class="newuniq overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" pattern="[0-9]{1}"/></div>
                <div class="col-1"><input data-num="6" data-type="newuniq"
                                          class="newuniq overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" pattern="[0-9]{1}"/></div>
                <div class="col-1"><input data-num="7" data-type="newuniq"
                                          class="newuniq overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" pattern="[0-9]{1}"/></div>
                <div class="col-1"><input data-num="8" data-type="newuniq"
                                          class="newuniq overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" pattern="[0-9]{1}"/></div>
            </div>
        </section>
        <section><h2>Postal Code</h2>
            <div class="row">
                <div class="col-1"><input data-num="1" data-type="zipcode"
                                          class="zipcode overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" min="0" max="9" pattern="[0-9]{1}"/></div>
                <div class="col-1"><input data-num="2" data-type="zipcode"
                                          class="zipcode overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" min="0" max="9" pattern="[0-9]{1}"/></div>
                <div class="col-1"><input data-num="3" data-type="zipcode"
                                          class="zipcode overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" min="0" max="9" pattern="[0-9]{1}"/></div>
                <div class="col-1"><input data-num="4" data-type="zipcode"
                                          class="zipcode overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" min="0" max="9" pattern="[0-9]{1}"/></div>
                <div class="col-1"><input data-num="5" data-type="zipcode"
                                          class="zipcode overflow-auto form-control p-0 " type="text" maxLength="1"
                                          size="5" min="0" max="9" pattern="[0-9]{1}"/></div>
            </div>
        </section>
        <section class="verify">
            <div class="row">
                <button id="verify" type="button" class="btn btn-info btn-lg btn-block">Login</button>
            </div>
        </section>
    </div>;
    <?php
} else {
    //todo redirect to complete list.
}
?>
