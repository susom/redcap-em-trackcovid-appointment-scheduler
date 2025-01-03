<?php

namespace Stanford\TrackCovidSharedAppointmentScheduler;

use REDCap;

include_once 'emLoggerTrait.php';
include_once 'Participant.php';
include_once 'Scheduler.php';
include_once 'TrackCovidSharedCalendarEmail.php';

if (file_exists(__DIR__ . '../vendor/autoload.php')) {
    // Required if your environment does not handle autoloading
    require __DIR__ . '/vendor/autoload.php';
}

use Stanford\ChartLogin\ChartLogin\ChartLogin;

// Test action
/**
 * Constants where appointment  is located
 */
define('CAMPUS_AND_VIRTUAL', 0);
define('VIRTUAL_ONLY', 1);
define('CAMPUS_ONLY', 2);

/**
 * Constants for appointment location text
 */
define('CAMPUS_AND_VIRTUAL_TEXT', 'Redwood City Campus , or Virtual via Zoom Meeting.');
define('VIRTUAL_ONLY_TEXT', 'Virtual via Zoom Meeting.');
define('CAMPUS_ONLY_TEXT', 'Redwood City Campus');

/**
 * COHORT
 */
define('COHOR_1', 1);
define('COHOR_2', 2);
/**
 * Constants for participation statuses
 */
define('AVAILABLE', 0);
define('RESERVED', 1);
define('CANCELED', 2);
define('NO_SHOW', 3);
define('NOT_SCHEDULED', 4);
define('COMPLETE', 5);
define('SKIPPED', 6);

/**
 * Constants for statuses  text
 */
define('AVAILABLE_TEXT', 'Available');
define('RESERVED_TEXT', 'Reserved');
define('CANCELED_TEXT', 'Canceled');
define('NO_SHOW_TEXT', 'No_Show');


define('MODULE_NAME', 'Appointment_scheduler');


/**
 * REDCap constants
 */
define('REDCAP_INCOMPLETE', 0);
define('REDCAP_UNVERIFIED', 1);
define('REDCAP_COMPLETE', 2);


/**
 * Complementary Constants (if you change in config.json you MUST update below constants accordingly)
 */
define('COMPLEMENTARY_EMAIL', 'complementary_email');
define('COMPLEMENTARY_NAME', 'complementary_name');
define('COMPLEMENTARY_MOBILE', 'complementary_mobile');
define('COMPLEMENTARY_NOTES', 'complementary_notes');
define('COMPLEMENTARY_PROJECT_ID', 'complementary_project_id');


define('COMPLEMENTARY_SUFFIX', 'complementary_suffix');
define('PROJECTID', 'projectid');

define("SURVEY_RESERVATION_FIELD", "survey_reservation_id");
define("RESERVATION_SLOT_FIELD", "reservation_slot_id");
define("DEFAULT_EMAIL", "redcap-scheduler@stanford.edu");
define("DEFAULT_NAME", "REDCap Admin");


define("LOCATION", "location");

define("PARTICIPANT_STATUS", "reservation_participant_status");

define('PT', 480);

/**
 * Class TrackCovidSharedAppointmentScheduler
 * @package Stanford\TrackCovidSharedAppointmentScheduler
 * @property \TrackCovidSharedCalendarEmail $emailClient
 * @property  array $instances
 * @property int $eventId
 * @property array $eventInstance
 * @property array $calendarParams
 * @property \Stanford\TrackCovidSharedAppointmentScheduler\Participant $participant
 * @property \Monolog\Logger $logger
 * @property string $suffix
 * @property int $mainSurveyId
 * @property int $projectId
 * @property int $recordId
 * @property \Project $project
 * @property boolean $baseLine
 * @property boolean $bonusVisit
 * @property string $baseLineDate
 * @property string $offsetDate
 * @property array $locationRecords
 * @property float $childEligibility
 * @property \Stanford\TrackCovidSharedAppointmentScheduler\Scheduler $scheduler
 */
class TrackCovidSharedAppointmentScheduler extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;

    public static $timezones = [
        300 => 'ET',
        360 => 'CT',
        420 => 'MT',
        480 => 'PT',
    ];
    /**
     * @var \TrackCovidSharedCalendarEmail|null
     */
    private $emailClient = null;


    /**
     * @var array of all instances in the project
     */
    private $instances;

    /**
     * @var array for specific instance
     */
    private $eventInstance;


    private $mainSurveyId;
    /**
     * @var int
     */
    private $eventId;

    /**
     * @var array
     */
    private $calendarParams;

    /**
     * @var \Participant;
     */
    private $participant;

    /**
     * @var string
     */
    private $suffix;

    /**
     * @var int
     */
    private $projectId;

    /**
     * @var
     */
    private $recordId;
    /**
     * @var
     */
    private $project;


    private $baseLine = false;

    private $baseLineDate = '';

    public $locationRecords;


    private $scheduler;

    private $bonusVisit = false;

    private $childEligibility;

    private $offsetDates;

    private $schedulerLoginEM;

    private $hoursLock = 0;

    private $lockTime = 0;

    /**
     * TrackCovidSharedAppointmentScheduler constructor.
     */
    public function __construct()
    {
        try {

            parent::__construct();


            /**
             * so when you enable this it does not throw an error !!
             */
            if ($_GET && ((isset($_GET['projectid']) and $_GET['projectid'] != null) || (isset($_GET['pid']) and $_GET['pid'] != null))) {

                if ($this->getProjectSetting('scheduler-login-em') != '') {
                    $this->setSchedulerLoginEM(\ExternalModules\ExternalModules::getModuleInstance($this->getProjectSetting('scheduler-login-em')));
                }
            }


            /**
             * Initiate suffix if exists
             */
            $this->setSuffix();

            /**
             * Initiate Email Client
             */
            $this->setEmailClient();


            /**
             * Initiate Email Participant
             */
            $this->setParticipant(new  Participant());

            /**
             * Only call this class when event is provided.
             */
            if (isset($_GET['event_id']) || isset($_POST['event_id'])) {

                $eventId = isset($_GET['event_id']) ? $_GET['event_id'] : $_POST['event_id'];
                /**
                 * sanitize variable and save it
                 */
                $this->setEventId(filter_var($eventId, FILTER_SANITIZE_NUMBER_INT));

                /**
                 * when event id exist lets find its instance
                 */
                $this->setEventInstance($this->getEventId());
            }


        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    public function getLoginURL($recordId)
    {
        /** @var ChartLogin $schedulerLoginEM */
        $schedulerLoginEM = $this->getSchedulerLoginEM();
        $loginInstrument = $schedulerLoginEM->getProjectSetting('login-instrument');
        $url = REDCap::getSurveyLink($recordId, $loginInstrument, $this->getFirstEventId());
        return $url;
    }

    private function sortRecordsByDate($records, $eventId)
    {
        $temp = array();
        $result = array();
        foreach ($records as $id => $record) {
            $date = date('Y-m-d H:i:s', strtotime($record[$eventId]['start']));
            $temp[$date][$id] = $record;
        }
        ksort($temp);
        foreach ($temp as $timestamp) {
            if (empty($result)) {
                $result = $timestamp;
            } else {
                // use loop to preserve the key
                foreach ($timestamp as $key => $item) {
                    $result[$key] = $item;
                }
            }
        }
        return $result;
    }

    /**
     * @param $eventId
     * @param null $month
     * @param null $year
     * @return mixed
     */
    public function getEventSlots(
        $eventId,
        $year = null,
        $month = null,
        $baseline = '',
        $offset = 0,
        $affiliation = null,
        $canceledBaseline = false,
        $reservationEventId = ''
    )
    {
        try {
            if ($this->getScheduler()->getSlotsEventId()) {

                $variable = 'start' . $this->getSuffix();
                $instance = $this->getSchedulerInstanceViaReservationId($reservationEventId);
                list($start, $end) = $this->getStartEndWindow($baseline, $offset, $canceledBaseline, $instance);


                $blockingDate = null;
                if ($offset != -1 && $reservationEventId && $this->isEventBookingBlocked($reservationEventId)) {
                    $blockingDate = $this->getBookingBlockDate($reservationEventId);
                }
                // get instance locations if availabe.
                $instance_locations = json_decode($instance ? $instance['instance_locations'] : [], true);

                $records = $this->getScheduler()->getSlots();
                foreach ($records as $record) {
                    //check if booking is blocked for this record
                    if ($blockingDate && strtotime($record[$this->getScheduler()->getSlotsEventId()][$variable]) >= strtotime($blockingDate)) {
                        continue;
                    }

                    // if locations are restricted for this instance make sure the slot is in the right location.
                    if (!empty($instance_locations)) {
                        if (!array_key_exists($record[$this->getScheduler()->getSlotsEventId()]['location'], $instance_locations)) {
                            continue;
                        }
                    }


                    if (strtotime($record[$this->getScheduler()->getSlotsEventId()][$variable]) > $this->getLockTime($reservationEventId) && strtotime($record[$this->getScheduler()->getSlotsEventId()][$variable]) > strtotime($start) && strtotime($record[$this->getScheduler()->getSlotsEventId()][$variable]) < strtotime($end) && $record[$this->getScheduler()->getSlotsEventId()]['slot_status'] != CANCELED) {
                        $data[] = $record;
                    }
                }

                return $this->sortRecordsByDate($data, $eventId);
            } else {
                throw new \LogicException('Not event id passed, Aborting!');
            }
        } catch (\LogicException $e) {
            //error($e->getMessage());
            echo $e->getMessage();
        }
    }

    public function isWeekend($date)
    {
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 0 || $weekDay == 6);
    }


    public function verifyInstanceLogic($eventId, $recordId)
    {
        $instance = $this->getEventIdInstance($eventId);
        if (!empty($instance)) {
            if ($instance['instance-logic-enabler'] != '') {
                return REDCap::evaluateLogic($instance['instance-logic-enabler'], $this->getProjectId(), $recordId);
            } else {
                // if no logic defined for this instance then use default config
                return $instance['default-instance-visibility'] == '1' ? true : false;
            }
        }

        // by default if instance is not defined for scheduler the
        return false;
    }

    /**
     * @param $slot
     * @param $userTimezone
     * @return mixed
     */
    public function modifySlotBasedOnUserTimezone($slot, $userTimezone)
    {
        // differance between user timezone and PT
        $diff = ($this->getPST() - $userTimezone) * 60;
        $slot['start_orig'] = $slot['start'];
        $slot['end_orig'] = $slot['end'];
        $slot['start'] = date('Y-m-d H:i:s', (strtotime($slot['start']) + $diff));
        $slot['end'] = date('Y-m-d H:i:s', (strtotime($slot['end']) + $diff));
        return $slot;
    }

    /**
     * @return array
     */
    public function getAllOpenSlots($suffix = '')
    {
        try {
            /*
                 * TODO Check if date within allowed window
                 */
            $filter = "[start$suffix] > '" . date('Y-m-d') . "' AND " . "[slot_status$suffix] != '" . CANCELED . "'";
            $param = array(
                'project_id' => $this->getScheduler()->getProject()->project_id,
                #'filterLogic' => $filter,
                'return_format' => 'array'
            );
            return REDCap::getData($param);
        } catch (\LogicException $e) {
            echo $e->getMessage();
        }
    }


    /**
     * @return array
     */
    public function prepareInstructorsSlots($records, $suffix)
    {
        $result = array();
        /**
         * just to reduce load on DB
         */
        $events = array();
        try {
            if (!empty($records)) {
                foreach ($records as $slots) {
                    foreach ($slots as $event_id => $slot) {
                        if (!isset($events[$event_id])) {
                            $events[$event_id] = $this->getUniqueEventName($event_id);
                        }

                        $slot['event_name'] = $events[$event_id];
                        $slot['event_id'] = $event_id;
                        $result[] = $slot;
                    }
                }

                return $result;
            } else {
                throw new \LogicException('No slots found');
            }
        } catch (\LogicException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param array $user
     */
    public function notifyUser($user, $reservationEventId, $slot = null)
    {
        $instance = $this->getEventIdInstance($reservationEventId);
        $this->calendarParams['calendarOrganizerEmail'] = ($instance['sender_email'] != '' ? $instance['sender_email'] : DEFAULT_EMAIL);
        $this->calendarParams['calendarOrganizer'] = ($instance['sender_name'] != '' ? $instance['sender_name'] : DEFAULT_NAME);
        $this->calendarParams['calendarDescription'] = $instance['calendar_body'];
        $this->calendarParams['calendarLocation'] = $user['reservation_participant_location'];
        $this->calendarParams['calendarDate'] = preg_replace("([^0-9/])", "", $_POST['calendarDate']);
        $this->calendarParams['calendarStartTime'] = preg_replace("([^0-9/])", "", $_POST['calendarStartTime']);
        $this->calendarParams['calendarEndTime'] = preg_replace("([^0-9/])", "", $_POST['calendarEndTime']);
        $this->calendarParams['calendarParticipants'] = array($user['name'] => $user['email']);
        $this->calendarParams['calendarSubject'] = '--CONFIRMATION-- Your appointment is scheduled at ' . date('m/d/Y',
                strtotime($this->calendarParams['calendarDate'])) . ' between ' . date('h:i A',
                strtotime($this->calendarParams['calendarStartTime'])) . ' and ' . date('h:i A',
                strtotime($this->calendarParams['calendarEndTime']));
        return $this->sendEmail($user['email'],
            ($instance['sender_email'] != '' ? $instance['sender_email'] : DEFAULT_EMAIL),
            ($instance['sender_name'] != '' ? $instance['sender_name'] : DEFAULT_NAME),
//            '--APPT CONFIRMATION-- ' . $user['newuniq'] . ' Please arrive' .
//            ' on ' . date('m/d/Y', strtotime($this->calendarParams['calendarDate'])) .
//            ' between ' . date('h:i A', strtotime($this->calendarParams['calendarStartTime'])) .
//            ' and ' . date('h:i A', strtotime($this->calendarParams['calendarEndTime'])),
            $instance['calendar_subject'],
            $this->replaceREDCapCustomLabels($this->replaceRecordLabels($instance['calendar_body'], $slot), array(), $user['newuniq']),
            true
        );

    }

    public function notifyCRCs($user, $reservationEventId, $slot = null)
    {
        $instance = $this->getEventIdInstance($reservationEventId);
        $this->calendarParams['calendarOrganizerEmail'] = DEFAULT_EMAIL;
        $this->calendarParams['calendarOrganizer'] = DEFAULT_NAME;
        $this->calendarParams['calendarDescription'] = $this->replaceREDCapCustomLabels($this->replaceRecordLabels($instance['crc-email-body'], $slot), array(), $user['newuniq']);
        $this->calendarParams['calendarLocation'] = $user['reservation_participant_location'];
        $this->calendarParams['calendarDate'] = preg_replace("([^0-9/])", "", $_POST['calendarDate']);
        $this->calendarParams['calendarStartTime'] = preg_replace("([^0-9/])", "", $_POST['calendarStartTime']);
        $this->calendarParams['calendarEndTime'] = preg_replace("([^0-9/])", "", $_POST['calendarEndTime']);
        $this->calendarParams['calendarParticipants'] = array($user['name'] => $user['email']);
        $this->calendarParams['calendarSubject'] = $this->replaceREDCapCustomLabels($this->replaceRecordLabels($instance['crc-email-subject'], $slot), array(), $user['newuniq']);

        $crc_emails = explode(',', $instance['crc-email-list']);
        foreach ($crc_emails as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendEmail($email,
                    DEFAULT_EMAIL,
                    DEFAULT_NAME,
                    $this->calendarParams['calendarSubject'],
                    $this->calendarParams['calendarDescription'],
                    true
                );
            }else{
                $this->emError('EMAIL NOT VALID: ' . $email);
            }

        }
    }

    /**
     * send calendar or regular emails
     * @param string $email
     * @param string $senderEmail
     * @param string $senderName
     * @param string $subject
     * @param string $body
     * @param bool $calendar
     * @param string $url
     */
    public function sendEmail($email, $senderEmail, $senderName, $subject, $body, $calendar = false, $url = '')
    {
        $this->emailClient->setTo($email);
        $this->emailClient->setFrom($senderEmail);
        $this->emailClient->setFromName($senderName);
        $this->emailClient->setSubject($subject);
        $this->emailClient->setBody($body);
        $this->emailClient->setUrlString("<a href='" . $this->getSchedulerURL() . "'>View Appointment Scheduler</a>");
        if ($calendar) {
            $result = $this->emailClient->sendCalendarEmail($this->calendarParams);
            $this->emLog("Email result: " . $result ? 1 : 0);
        } else {
            $result = $this->emailClient->send();
        }
        return $result;
    }

    /**
     * @return array
     */
    public function sanitizeInput()
    {
        $data = array();
//        $data['email' . $this->getSuffix()] = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
//        $data['name' . $this->getSuffix()] = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
//        $data['mobile' . $this->getSuffix()] = filter_var($_POST['mobile'], FILTER_SANITIZE_STRING);
        //$data['participant_notes' . $this->getSuffix()] = filter_var($_POST['notes'], FILTER_SANITIZE_STRING);
//        $data['project_id' . $this->getSuffix()] = filter_var($_POST['project_id'], FILTER_SANITIZE_NUMBER_INT);
        $data['reservation_slot_id' . $this->getSuffix()] = filter_var($_POST['record_id'], FILTER_SANITIZE_STRING);
//        $data['private' . $this->getSuffix()] = filter_var($_POST['private'], FILTER_SANITIZE_NUMBER_INT);
        $data['reservation_participant_location' . $this->getSuffix()] = filter_var($_POST['type'],
            FILTER_SANITIZE_NUMBER_INT);

        /**
         * For Event data you do not need to append suffix info because it will not be saved.
         */
        $data['event_id'] = filter_var($_POST['event_id'], FILTER_SANITIZE_NUMBER_INT);
        $data['redcap_event_name'] = $this->getUniqueEventName($data['event_id']);
        // $data['date'] = date('Y-m-d', strtotime(filter_var($_POST['date'], FILTER_SANITIZE_NUMBER_INT)));

        return $data;
    }

    /**
     * @param $type
     * @return string
     */
    public function getTypeText($type)
    {
        switch ($type) {
            case VIRTUAL_ONLY:
                $typeText = VIRTUAL_ONLY_TEXT;
                break;
            case CAMPUS_ONLY:
                $typeText = CAMPUS_ONLY_TEXT;
                break;
            default:
                $typeText = CAMPUS_AND_VIRTUAL_TEXT;
        }
        return $typeText;
    }

    /**
     * @param array $data
     * @param int $id
     */
    public function updateTimeSLot($data, $id)
    {
        $filters = '';
        foreach ($data as $key => $value) {
            $filters = " $key = '$value' ,";
        }

        $filters = rtrim($filters, ",");
        $sql = sprintf("UPDATE  redcap_appointment_participant SET $filters WHERE id = $id");

        if (!db_query($sql)) {
            throw new \LogicException('cant update participant');
        }
    }

    public function notifyParticipants($slotId, $eventId, $message)
    {
        $instance = $this->getEventInstance();
        $participants = $this->participant->getSlotActualReservedSpots($slotId, $eventId, $this->getProjectId());
        foreach ($participants as $participant) {
            $result = end($participant);
            $this->emailClient->setCalendarOrganizerEmail(($instance['sender_email'] != '' ? $instance['sender_email'] : DEFAULT_EMAIL));
            $this->emailClient->setCalendarOrganizer(($instance['sender_name'] != '' ? $instance['sender_name'] : DEFAULT_NAME));
            $this->emailClient->setTo($result['email']);
            $this->emailClient->setFrom(($instance['sender_name'] != '' ? $instance['sender_name'] : DEFAULT_NAME));
            $this->emailClient->setFromName($result['email']);
            $this->emailClient->setSubject($message['subject']);
            $this->emailClient->setBody($message['body']);
            $this->emailClient->send();
            $this->forceCancellation($result[$this->getPrimaryRecordFieldName()], $eventId);
        }
    }

    public function forceCancellation($recordId, $eventId)
    {
        $data['reservation_participant_status'] = false;
        $data[$this->getPrimaryRecordFieldName()] = $recordId;
        $data['redcap_event_name'] = \REDCap::getEventNames(true, true, $eventId);
        $response = \REDCap::saveData('json', json_encode(array($data)));
    }

    /**
     * @param int $event_id
     * @param int $record_id
     * @return array
     */
    public function getSlot($record_id, $event_id)
    {
        try {
            if ($event_id) {
                $record = $this->getScheduler()->getSlot($record_id);
                return $record[$event_id];
            } else {
                throw new \LogicException('Not event id passed, Aborting!');
            }
        } catch (\LogicException $e) {
            echo $e->getMessage();
        }
    }


    public function getNextRecordsId($eventId, $projectId)
    {
        $data_table = method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($projectId) : "redcap_data";

        $sql = sprintf("SELECT max(cast(record as SIGNED)) as record_id from $data_table WHERE project_id = '$projectId' AND event_id = '$eventId'");

        $this->emLog("SQL Statement:", $sql);
        $result = db_query($sql);
        if (!$result) {
            throw new \LogicException('cant find next record ');
        }

        $data = db_fetch_assoc($result);
        $this->emLog("Resulted Data:", $data);
        $id = $data['record_id'];
        $id++;
        $this->emLog("Return ID", $id);
        return $id;
    }

    /**
     * @param array $instances
     * @param int $slotEventId
     * @return bool
     */
    public function getReservationEventIdViaSlotEventId($slotEventId)
    {
        $instances = $this->getInstances();
        foreach ($instances as $instance) {

            /**
             * If its regular appointment
             */
            if ($this->getSuffix() == '') {
                if ($instance['slot_event_id'] == $slotEventId) {
                    return $instance['reservation_event_id'];
                }
            } else {
                if ($instance['survey_complementary_slot_event_id'] == $slotEventId) {
                    return $instance['survey_complementary_reservation_event_id'];
                }
            }

        }
        return false;
    }

    /**
     * @param int $eventId
     * @return string
     */
    public function getSuffixViaEventId($eventId)
    {
        $instances = $this->getInstances();
        foreach ($instances as $instance) {
            /**
             * if the event id passed is survey_complementary_slot_event_id or survey_complementary_reservation_event_id
             */
            if ($instance['survey_complementary_slot_event_id'] == $eventId || $instance['survey_complementary_reservation_event_id'] == $eventId) {
                return $instance['complementary_suffix'];
            }
        }
        return '';
    }

    /**
     * @param string $user
     * @return bool|\mysqli_result
     */
    public function getUserProjects($user)
    {
        // Retrieve the projects that the user has access to
        $query = "select pr.project_id, pr.app_title " .
            " from redcap_user_rights ur, redcap_projects pr " .
            " where ur.username = '" . $user . "'" .
            " and ur.project_id = pr.project_id order by pr.project_id";
        return db_query($query);
    }

    /**
     * @return boolean
     */
    public function getNoteLabel()
    {
        $instance = $this->identifyCurrentInstance($this->getEventId());
        return $instance['note_textarea_label'];
    }

    /**
     * @return boolean
     */
    public function showLocationsOptions()
    {
        $instance = $this->identifyCurrentInstance($this->getEventId());
        return $instance['location_options'];
    }

    /**
     * @return boolean
     */
    public function showProjectIds()
    {
        $instance = $this->identifyCurrentInstance($this->getEventId());
        return $instance['show_projects'];
    }

    /**
     * @return boolean
     */
    public function showAttendingOptions()
    {
        $instance = $this->identifyCurrentInstance($this->getEventId());
        return $instance['show_attending_options'];
    }

    /**
     * @return boolean
     */
    public function showLocationOptions()
    {
        $instance = $this->identifyCurrentInstance($this->getEventId());
        return $instance['show_location_options'];
    }

    /**
     * @return int
     */
    public function getEventCohort($eventId)
    {
        $instances = $this->getInstances();
        foreach ($instances as $instance) {

            if ($instance['reservation_event_id'] == $eventId) {
                return $instance['assigned-cohort'];
            }
        }
        return false;
    }

    /**
     * @return int
     */
    public function getDefaultAttendingOption()
    {
        $instance = $this->identifyCurrentInstance($this->getEventId());
        return $instance['show_attending_default'];
    }

    /**
     * @return boolean
     */
    public function showNotes()
    {
        $instance = $this->identifyCurrentInstance($this->getEventId());
        return $instance['show_notes'];
    }

    /**
     * @param int $eventId
     * @return bool|array
     */
    private function identifyCurrentInstance($eventId)
    {
        foreach ($this->getInstances() as $instance) {
            if ($instance['slot_event_id'] == $eventId) {
                return $instance;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isBaseLine()
    {
        return $this->baseLine;
    }

    /**
     * @param bool $baseLine
     */
    public function setBaseLine($baseLine)
    {
        $this->baseLine = $baseLine;
    }

    /**
     * @return string|null
     */
    public function getOffsetDate()
    {
        return $this->offsetDates;
    }

    /**
     * @param string $offsetDate
     */
    public function setOffsetDate(string $offsetDate): void
    {
        $this->offsetDates = $offsetDate;
    }


    /**
     * @return string
     */
    public function getBaseLineDate()
    {
        return $this->baseLineDate;
    }

    /**
     * @param string $baseLineDate
     */
    public function setBaseLineDate($baseLineDate)
    {
        $this->baseLineDate = $baseLineDate;
    }


    /**
     * @return int
     */
    public function getSlotsEventId()
    {
        foreach ($this->getInstances() as $instance) {
            if ($instance['instrument_id_for_complementary_appointment'] == $this->getMainSurveyId()) {
                return $instance['slot_event_id'];
            }
        }
        throw new \LogicException("No Event is assigned");
    }

    /**
     * @return int
     */
    public function getReservationEventId()
    {
        if ($this->getInstances()) {
            foreach ($this->getInstances() as $instance) {
                if ($instance['instrument_id_for_complementary_appointment'] == $this->getMainSurveyId()) {
                    return $instance['reservation_event_id'];
                }
            }
            // throw new \LogicException("No Event is assigned");
        }
    }

    /**
     * @param $pid
     * @param int|null $month
     * @param int|null $year
     * @return bool|\mysqli_result
     */
    public function getProjectREDCapCalendar($pid, $year = null, $month = null)
    {
        if ($month != '' && $year != '') {
            $date = "$year-$month-01";
            $filter = "event_date BETWEEN '" . date('Y-m-01', strtotime($date)) . "' AND " . " '" . date('Y-m-t',
                    strtotime($date)) . "'";
        } else {
            $filter = "event_date BETWEEN '" . date('Y-m-d') . "' AND " . " '" . date('Y-m-d',
                    strtotime('first day of next month')) . "'";
        }
        $sql = sprintf("SELECT * from redcap_events_calendar WHERE project_id = $pid AND $filter");

        return db_query($sql);
    }

    /**
     * @param string $slotDate
     * @param string $email
     * @param string $suffix
     * @param int $slotEventId
     * @param int $reservationEventId
     */
    public function doesUserHaveSameDateReservation($slotDate, $sunetId, $suffix, $slotEventId, $reservationEventId)
    {
        $reservations = $this->participant->getUserParticipation($sunetId, $suffix, $this->getProjectId(), RESERVED);

        foreach ($reservations as $reservation) {
            $record = $reservation[$reservationEventId];
            $reservationSlot = $this->getSlot($record['reservation_slot_id'], $this->getScheduler()->getSlotsEventId());
            $reservationSlotDate = date('Y-m-d', strtotime($reservationSlot['start']));
            if ($reservationSlotDate == $slotDate) {
                throw new \LogicException("you cant book more than one reservation on same date. please select another date");
            }
        }
    }

    public function redcap_module_link_check_display($project_id, $link, $record = null, $instrument = null, $instance = null, $page = null)
    {

        $link['url'] .= '&projectid=' . $project_id;
        return $link;
    }

    /**
     * @param $eventId
     * @return array|mixed|null
     */
    public function getUniqueEventName($eventId)
    {
        return $this->getProject()->getUniqueEventNames($eventId);
    }

    /**
     * @return mixed
     */
    public function getPrimaryRecordFieldName()
    {
        return $this->getProject()->table_pk;
    }

    public function getSchedulerURL()
    {
        return $this->getUrl('src/type.php', true,
                false) . '&' . $this->getSuffix() . '&' . PROJECTID . '=' . $this->getProjectId();
    }

    /**
     * @return bool
     */
    public static function isUserHasManagePermission()
    {
        if (defined('PROJECT_ID') and (!defined('NOAUTH') || NOAUTH == false)) {

            //this function return right for main user when hit it with survey respondent!
            $right = REDCap::getUserRights();
            $user = $right[USERID];
            if ($user['design'] === "1" || $user['forms']['reservation'] === "1") {
                return true;
            }
        } elseif (defined('SUPER_USER') && SUPER_USER == "1") {
            return true;
        }

        return false;
    }


    /**
     * get project name
     * @param $projectId
     * @return mixed
     */
    public static function getProjectName($projectId)
    {
        try {
            $project = new \Project($projectId);
            $name = $project->project['app_title'];
            unset($project);
            return $name;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getDefinedLocations()
    {
        try {

//            $projectId = ($_GET['projectid'] != null ? filter_var($_GET['projectid'],
//                FILTER_SANITIZE_NUMBER_INT) : filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT));
//
//            $project = new \Project($projectId);
            $locations = $this->getScheduler()->getProject()->metadata[LOCATION]['element_enum'];
            return parseEnum($locations);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getParticipantStatus()
    {
        try {

            $projectId = ($_GET['projectid'] != null ? filter_var($_GET['projectid'],
                FILTER_SANITIZE_NUMBER_INT) : filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT));

            $project = new \Project($projectId);
            $locations = $project->metadata['reservation_participant_status']['element_enum'];
            return parseEnum($locations);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getLocationLabel($id)
    {
        $locations = $this->getDefinedLocations();
        return $locations[$id];
    }


    public function isSlotInPast($slot, $suffix)
    {
        /**
         * skip past slots.
         */
        if (time() > strtotime($slot['start' . $suffix])) {
            return true;
        }
        return false;
    }


    public function verifyUser($newuniq, $zipcode_abs)
    {
        #$filter = "[newuniq] = '" . strtoupper($newuniq) . "' AND [zipcode_abs] = '" . $zipcode_abs . "'";
        //
        $param = array(
            'project_id' => $this->getProjectId(),
            'records' => [$newuniq],
            #'filterLogic' => $filter,
            'return_format' => 'array',
            'events' => $this->getFirstEventId()
        );
        $data = REDCap::getData($param);

        // this to check if participant withdraw from the ths study.
        $withdraw = $data[$newuniq][$this->getFirstEventId()]['calc_inactive'];
        if (empty($data)) {
            return false;
        } else {
            if ($withdraw) {
                return false;
            }
            return $data;
        }
    }

    public function generateUniqueCodeHash($newuniq)
    {
        return hash('sha256', $newuniq);
    }

    public function setUserCookie($name, $value, $time = 86406)
    {
        $this->deleteUserCookie('login');
        #day
        setcookie($name, $value, time() + $time);
    }

    public function deleteUserCookie($name)
    {
        #day
        setcookie($name, '', -1, '/');
    }

    public function verifyCookie($name, $recordID = false)
    {
//        if(!isset($_COOKIE[$name])){
//            return false;
//        }

        // when manager hits user page. they must be logged in and have right permission on redcap.

        if (defined('USERID') && ((isset($_GET['code']) && isset($_GET['zip'])) || $recordID) && self::isUserHasManagePermission()) {
            if ($recordID) {
                $param = array(
                    'project_id' => $this->getProjectId(),
                    'return_format' => 'array',
                    'records' => [$recordID]
                );
            } else {
                $param = array(
                    'project_id' => $this->getProjectId(),
                    'return_format' => 'array',
                    //'events' => [$this->getFirstEventId()]
                );
            }
            $records = REDCap::getData($param);
            foreach ($records as $id => $record) {
                if (filter_var($_GET['code'], FILTER_SANITIZE_STRING) == $record[$this->getFirstEventId()][$this->getProjectSetting('validation-field')]) {
                    $this->setUserCookie('login', $this->generateUniqueCodeHash($record[$this->getFirstEventId()][$this->getProjectSetting('validation-field')]));
                    return array('id' => $id, 'record' => $record);
                }
                if ($recordID && $this->getProjectSetting('validation-field') == $this->getProject()->table_pk) {
                    if ($recordID == $id) {
                        $this->setUserCookie('login', $this->generateUniqueCodeHash($id));
                        return array('id' => $id, 'record' => $record);
                    }
                }
            }
        } else {
            if ($recordID) {
                $param = array(
                    'project_id' => $this->getProjectId(),
                    'return_format' => 'array',
                    'records' => [$recordID]
                );
            } else {
                $param = array(
                    'project_id' => $this->getProjectId(),
                    'return_format' => 'array',
                    //'events' => [$this->getFirstEventId()]
                );
            }
            $records = REDCap::getData($param);
            foreach ($records as $id => $record) {
                $hash = $this->generateUniqueCodeHash(htmlentities($record[$this->getFirstEventId()][$this->getProjectSetting('validation-field')]));
                // this to check if participant withdraw from the ths study.
                $withdraw = $record[$id][$this->getFirstEventId()]['calc_inactive'];
                if ($hash == $_COOKIE[$name]) {
                    if ($withdraw) {
                        return false;
                    }
                    return array('id' => $id, 'record' => $record);
                }
            }
        }
        return false;
    }

    public function getReservationArray($data)
    {
        if (isset($data['reservation_slot_id']) && $data['reservation_slot_id'] != '') {
            return $this->getSlot($data['reservation_slot_id'], $this->getScheduler()->getSlotsEventId());
        }
        return false;
    }


    public function isReservationInPast($date)
    {
        /**
         * open the window one day for manager page
         */
        $d = date('Y-m-d', strtotime($date));
        /**
         * skip past reseravtion.
         */
        if (time() > strtotime('+1 day', strtotime($d))) {
            return true;
        }
        return false;
    }

    public function getEventMonthYear($offset)
    {
        $year = date('Y');
        $baseLine = $this->getProjectSetting('baseline-month', $this->getProjectId());
        if ($offset > 0) {
            $o = $offset / 30;
            $month = $baseLine + $o;
        } else {
            $month = $baseLine;
        }

        // got next year
        if ($month > 12) {
            $month = 1;
            $year += 1;
        }
        return array($month, $year);
    }

    public function getPST()
    {
        return PT - (date('I') ? 60 : 0);
    }

    public function getTimezoneAbbr($offset)
    {
        return TrackCovidSharedAppointmentScheduler::$timezones[$offset + (date('I') ? 60 : 0)];
    }

    private function getStartEndWindow($baseline, $offset, $canceledBaseline, $instance)
    {
        $windowSize = $instance['window-size'] ?: 20;

        if ($offset) {
            $add = $offset * 60 * 60 * 24;
        }

        if ($this->getChildEligibility() == '1') {
            $add = 60 * 60 * 24 * 2;
        } elseif ($this->getChildEligibility() == '0.5') {
            $add = 60 * 60 * 24 * 7;
        }
        if ($baseline) {

            $add = $offset * 60 * 60 * 24;
            $week = 604800;
            if (!$canceledBaseline) {
                $start = date('Y-m-d', strtotime($baseline) + $add);
            } else {
                $start = date('Y-m-d', strtotime($baseline));
            }


            // is start in the past then make start within next 12 horus to give CRC time to prepare.
            if (strtotime($start) < time() + 43200) {
                $start = date('Y-m-d H:i:s', time() + $add);
            }

            $end = date('Y-m-d H:i:s', strtotime($start) + $windowSize * 24 * 60 * 60);


            // final check if $end is lower than start add one week to end
            if (strtotime($start) > strtotime($end)) {
                $end = date('Y-m-d', strtotime($start) + $windowSize);
            }
        } else {

            # allow participant to book up 12 pm after two days.
            $start = date('Y-m-d  H:i:s', time() + $add);

            #open till end of year days restriction.
            $end = date('Y-m-d', strtotime('12/31'));
            // if start date is after end then make sure end date is at the end of start year.
            if ($start > $end) {
                $nextyear = mktime(0, 0, 0, date("m"), date("d"), date("Y") + 1);
                $end = date('Y-m-d', strtotime($nextyear) + strtotime($start));
            }
        }
        return array($start, $end);
    }

    public function getScheduleActionButton($month, $year, $url, $user, $eventId, $offset = 0, $canceledBaseline = false)
    {
        $instance = $this->getSchedulerInstanceViaReservationId($eventId);

        if ($this->isBaseLine() || $this->getBaseLineDate() || $this->getOffsetDate()) {

            $instance = $this->getSchedulerInstanceViaReservationId($eventId);

            list($start, $end) = $this->getStartEndWindow(($this->getOffsetDate() ?: $this->getBaseLineDate()), $offset, $canceledBaseline, $instance);

            return '<button data-baseline="' . ($this->getOffsetDate() ?: $this->getBaseLineDate()) . '" data-canceled-baseline="' . $canceledBaseline . '"  data-month="' . $month . '"  data-year="' . $year . '" data-url="' . $url . '" data-record-id="' . $user['id'] . '" data-key="' . $eventId . '" data-offset="' . $offset . '" class="get-list btn btn-sm btn-success">Schedule</button><br><small>(Schedule between ' . date('Y-m-d', strtotime($start)) . ' and ' . date('Y-m-d', strtotime($end)) . ')</small>';
        } else {
            return '<button data-baseline="' . ($this->getOffsetDate() ?: $this->getBaseLineDate()) . '" data-canceled-baseline="' . $canceledBaseline . '"  data-month="' . $month . '"  data-year="' . $year . '" data-url="' . $url . '" data-record-id="' . $user['id'] . '" data-key="' . $eventId . '" data-offset="' . $offset . '" class="get-list btn btn-sm btn-success">Schedule</button><br>';

        }

    }

    public function getCancelActionButton($user, $eventId, $slot)
    {
        return '<button data-record-id="' . $user['id'] . '" data-key="' . $eventId . '" data-slot-id="' . $slot[$this->getScheduler()->getProject()->table_pk] . '" class="cancel-appointment btn btn-danger">Cancel</button>';
    }

    public function getSkipActionButton($user, $eventId)
    {
        $statuses = parseEnum($this->getProject()->metadata['reservation_visit_status']["element_enum"]);
        return '<br><button data-participant-id="' . $user['id'] . '" data-event-id="' . $eventId . '" data-status="' . $this->getSkippedIndex() . '"  class="skip-appointment btn btn-sm btn-warning">Skip</button>';
    }

    public function getBaseLineEventID()
    {
        $events = $this->getProject()->events[1]['events'];
        foreach ($events as $id => $event) {
            if ($event['day_offset'] == 0) {
                return $id;
            }
        }
    }

    public function insertLocationInEmailBody($locationId, $body)
    {
        if (strpos($body, '[location]') !== false) {
            $locations = $this->getLocationRecords();
            $location = $locations['SITE_' . $locationId];
//            $text = "<br>Title: " . $location[$this->getScheduler()->getTestingSitesEventId()]['title'];
//            $text .= "<br>Address: " . $location[$this->getScheduler()->getTestingSitesEventId()]['testing_site_address'];
//            $text .= "<br>Details: " . $location[$this->getScheduler()->getTestingSitesEventId()]['site_details'];
//            if ($location[$this->getScheduler()->getTestingSitesEventId()]['map_link']) {
//                $text .= "<br>Google Map Link: <a href='" . $location[$this->getScheduler()->getTestingSitesEventId()]['map_link'] . "'>" . $location[$this->getScheduler()->getTestingSitesEventId()]['map_link'] . "</a>";
//
//            }
            return str_replace('[location]', $location[$this->getScheduler()->getTestingSitesEventId()]['testing_site_address'], $body);
        }
        return $body;
    }

    public function getLocationRecords()
    {
        if (!$this->locationRecords) {
            $param = array(
                'project_id' => $this->getScheduler()->getProject()->project_id,
                'events' => [$this->getScheduler()->getTestingSitesEventId()]
            );
            $results = \REDCap::getData($param);
            $locations = array();
            //filter the locations based on what defined on config.json
            foreach ($results as $id => $result) {
                // if (in_array($id, $this->getScheduler()->getSites())) {
                $locations[$id] = $result;
                // }
            }
            $this->locationRecords = $locations;
            return $this->locationRecords;
        } else {
            return $this->locationRecords;
        }
    }

    public function replaceRecordLabels($text, $row)
    {
        $origin = $text;
        preg_match_all("/\[(.*?)\]/", $text, $matches);
        foreach ($matches[1] as $match) {
            if (isset($row[$match])) {
                if ($match == 'location') {
                    $text = $this->insertLocationInEmailBody($row['location'], $text);
                } elseif ($match == 'start') {
                    $text = str_replace("[" . $match . "]", date('F jS, Y', strtotime($row[$match])), $text);
                } else {
                    $text = str_replace("[" . $match . "]", $row[$match], $text);
                }
            }
        }

        if ($origin != $text) {
            return $text;
        } else {
            return $origin;
        }
    }

    public function replaceREDCapCustomLabels($text, $row, $recordId)
    {
        $origin = $text;
        preg_match_all("/\[(\w.*)\]/", $text, $matches);
        foreach ($matches[0] as $match) {
            $label = \Piping::replaceVariablesInLabel($match, $recordId, null, 1, $row, true, null, false);
            if ($label) {
                $text = str_replace($match, $label, $text);
            }
        }

        if ($origin != $text) {
            return $text;
        } else {
            return $origin;
        }
    }

    public function getFormattedTimestamp($timestamp)
    {
        return date('m-d-Y', $timestamp);
    }

    public function buildWeeklyTotalsTable($weekDays)
    {
        $result = array();
        $records = $this->getParticipant()->getAllReservedSlots($this->getProjectId(), array_keys($this->getProject()->events['1']['events']));
        foreach ($records as $id => $events) {
            foreach ($events as $eventId => $record) {
                // make sure there is date to check for
                if (empty($record['reservation_date'])) {
                    continue;
                }
                $date = $this->getFormattedTimestamp(strtotime($record['reservation_date']));
                if ($this->isDateInWeek($weekDays, $date)) {
                    if (array_key_exists($record['reservation_participant_location'], $result)) {
                        if (array_key_exists($date, $result[$record['reservation_participant_location']])) {
                            $result[$record['reservation_participant_location']][$date]++;
                        } else {
                            $result[$record['reservation_participant_location']][$date] = 1;
                        }
                        $result[$record['reservation_participant_location']]['total']++;
                    } else {
                        $result[$record['reservation_participant_location']][$date] = 1;
                        $result[$record['reservation_participant_location']]['total'] = 1;
                    }

                }
            }
        }
        return $result;
    }

    public function isDateInWeek($weekDays, $date)
    {
        return in_array($date, $weekDays);
    }

    public function getWeekdaysDates($index = 0)
    {
        $week = 604800;
        $day = 60 * 60 * 24;
        $result = array();
        // get closest sunday as starting point
        $lastSunday = strtotime('Last Sunday', time());
        // if we are looking for different week from current one then adjust the starting sunday based on index
        if ($index != 0) {

            $delta = $week * $index;
            $startSunday = $lastSunday + $delta;
        } else {
            $startSunday = $lastSunday;
        }
        for ($i = 1; $i <= 7; $i++) {
            $result[] = $this->getFormattedTimestamp($startSunday + ($i * $day));
        }
        return $result;
    }

    public function getRecordRescheduleCounter($recordId, $eventId)
    {
        $param = array(
            'project_id' => $this->getProjectId(),
            'events' => [$eventId],
            'recocrds' => [$recordId]
        );
        $data = REDCap::getData($param);
        if (isset($data[$recordId][$eventId]['reservation_reschedule_counter'])) {
            return $data[$recordId][$eventId]['reservation_reschedule_counter'];
        }
        return false;
    }

    public function getRecordReservationDateTime($recordId, $eventId)
    {
        $param = array(
            'project_id' => $this->getProjectId(),
            'events' => [$eventId],
            'recocrds' => [$recordId]
        );
        $data = REDCap::getData($param);
        if (isset($data[$recordId][$eventId]['reservation_datetime'])) {
            return $data[$recordId][$eventId]['reservation_datetime'];
        }
        return false;
    }

    public function getRecordSummaryNotes($recordId, $eventId)
    {
        $param = array(
            'project_id' => $this->getProjectId(),
            'events' => [$eventId],
            'recocrds' => [$recordId]
        );
        $data = REDCap::getData($param);
        if (isset($data[$recordId][$eventId]['summary_notes'])) {
            return $data[$recordId][$eventId]['summary_notes'];
        }
        return false;
    }

    /**
     * @param array $instances
     * @param int $slotEventId
     * @return bool
     */
    public function getSlotEventIdFromReservationEventId($reservationEventId)
    {
        $instances = $this->getInstances();
        foreach ($instances as $instance) {

            if ($instance['reservation_event_id'] == $reservationEventId) {
                return $instance['slot_event_id'];
            }

        }
        return false;
    }


    /**
     * @param array $instances
     * @param int $slotEventId
     * @return bool
     */
    public function getSchedulerInstanceViaReservationId($reservationEventId)
    {
        $instances = $this->getInstances();
        foreach ($instances as $instance) {

            if ($instance['reservation_event_id'] == $reservationEventId) {
                return $instance;
            }

        }
        return false;
    }

    public function isEventBookingBlocked($eventId)
    {
        $instances = $this->getInstances();
        foreach ($instances as $instance) {
            if ($instance['reservation_event_id'] == $eventId && $instance['block-booking']) {
                return true;
            }
        }
        return false;
    }

    public function getBookingBlockDate($eventId)
    {
        $instances = $this->getInstances();
        foreach ($instances as $instance) {
            if ($instance['reservation_event_id'] == $eventId && $instance['block-booking']) {
                return $instance['block-booking-date'];
            }
        }
        return false;
    }

    /**
     * @param array $instances
     * @param int $slotEventId
     * @return array
     */
    public function getReservationEventIdViaSlotEventIds($slotEventId)
    {
        $result = array();
        $instances = $this->getInstances();
        foreach ($instances as $instance) {

            /**
             * If its regular appointment
             */
            if ($this->getSuffix() == '') {
                if ($instance['slot_event_id'] == $slotEventId) {
                    $result[] = $instance['reservation_event_id'];
                }
            } else {
                if ($instance['survey_complementary_slot_event_id'] == $slotEventId) {
                    $result[] = $instance['survey_complementary_reservation_event_id'];
                }
            }

        }
        return $result;
    }


    public function getEventIdInstance($eventId)
    {
        foreach ($this->getInstances() as $instance) {
            if ($instance['reservation_event_id'] == $eventId) {
                return $instance;
            }
        }
        return array();
    }

    public function getReservationEvents()
    {
        $result = array();
        foreach ($this->getInstances() as $instance) {
            $result[] = $instance['reservation_event_id'];
        }
        return $result;
    }

    public function getSkippedIndex()
    {
        $statuses = parseEnum($this->getProject()->metadata['reservation_visit_status']["element_enum"]);
        return array_search('Skipped', $statuses);
    }

    public function isAppointmentNoShow($status)
    {
        $statuses = parseEnum($this->getProject()->metadata['reservation_visit_status']["element_enum"]);
        $s = array_search('No Show', $statuses);
        return $status == $s;
    }

    public function isAppointmentSkipped($status)
    {
        return $status == $this->getSkippedIndex();
    }

    /**
     * @return Scheduler
     */
    public function getScheduler()
    {
        if (!$this->scheduler) {
            $this->setScheduler(new Scheduler(new \Project($this->getProjectSetting('slots-project')), json_decode($this->getProjectSetting('allowed-testing-sites'), true), $this->getProjectSetting('slots-project-event-id'), $this->getProjectSetting('slots-project-testing-sites-event-id')));
        }
        return $this->scheduler;
    }

    /**
     * @param Scheduler $scheduler
     */
    public function setScheduler(Scheduler $scheduler)
    {
        $this->scheduler = $scheduler;
    }


    /**
     * @return int
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * @param int $recordId
     */
    public function setRecordId()
    {
        $temp = func_get_args();
        $recordId = $temp[0];
        $this->recordId = $recordId;
    }

    /**
     * @return \Project
     */
    public function getProject()
    {
        if (!$this->project) {
            global $Proj;
            $this->setProject($Proj);
        }
        return $this->project;
    }

    /**
     * @param \Project $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        if (!$this->projectId) {
            $projectId = ($_GET['projectid'] != null ? filter_var($_GET['projectid'],
                FILTER_SANITIZE_NUMBER_INT) : filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT));
            $this->setProjectId($projectId);

        }
        return $this->projectId;
    }

    /**
     * @param int $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * @return array
     */
    public function getCalendarParams()
    {
        return $this->calendarParams;
    }

    /**
     * @param array $calendarParams
     */
    public function setCalendarParams($calendarParams)
    {
        $this->calendarParams = $calendarParams;
    }


    /**
     * @return mixed
     */
    public function getMainSurveyId()
    {
        return $this->mainSurveyId;
    }

    /**
     * @param mixed $mainSurveyId
     */
    public function setMainSurveyId($mainSurveyId)
    {
        $this->mainSurveyId = $mainSurveyId;
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    public function setSuffix()
    {
        $this->suffix = (isset($_GET['complementary_suffix']) ? filter_var($_GET['complementary_suffix'],
            FILTER_SANITIZE_STRING) : '');
    }

    /**
     * @return \TrackCovidSharedCalendarEmail
     */
    public function getEmailClient()
    {
        return $this->emailClient;
    }

    /**
     * @param \TrackCovidSharedCalendarEmail $emailClient
     */
    public function setEmailClient()
    {
        $this->emailClient = new \TrackCovidSharedCalendarEmail;
    }


    /**
     * @return \Stanford\TrackCovidSharedAppointmentScheduler\Participant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * @param \Stanford\TrackCovidSharedAppointmentScheduler\Participant $participant
     */
    public function setParticipant($participant)
    {
        $this->participant = $participant;
    }


    /**
     * @return array
     */
    public function getEventInstance()
    {
        if (!$this->eventInstance) {
            $this->setEventInstance($this->getFirstEventId($this->getProjectId()));
        }
        return $this->eventInstance;
    }

    /**
     * Pass event id and search for it in the instances array
     * @param int $eventId
     */
    public function setEventInstance($eventId)
    {
        foreach ($this->getInstances() as $instance) {
            if ($instance['slot_event_id'] == $eventId) {
                $this->eventInstance = $instance;
            }
        }
    }

    /**
     * @return int
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param int $eventId
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * @return mixed
     */
    public function getInstances()
    {
        if (!$this->instances) {
            $this->setInstances();
        }
        return $this->instances;
    }

    /**
     * save $instances
     */
    public function setInstances()
    {
        $this->instances = $this->getSubSettings('instance', $this->getProjectId());;
    }

    /**
     * @return bool
     */
    public function isBonusVisit()
    {
        return $this->bonusVisit;
    }

    /**
     * @param bool $bonusVisit
     */
    public function setBonusVisit($bonusVisit)
    {
        $this->bonusVisit = $bonusVisit;
    }

    /**
     * @return mixed
     */
    public function getChildEligibility()
    {
        return $this->childEligibility;
    }

    /**
     * @param mixed $childEligibility
     */
    public function setChildEligibility($childEligibility): void
    {
        $this->childEligibility = $childEligibility;
    }

    /**
     * @return mixed
     */
    public function getSchedulerLoginEM()
    {
        return $this->schedulerLoginEM;
    }

    /**
     * @param mixed $schedulerLoginEM
     */
    public function setSchedulerLoginEM($schedulerLoginEM): void
    {
        $this->schedulerLoginEM = $schedulerLoginEM;
    }


    public function getParticipantName($id)
    {
        $eventId = $this->getProjectSetting('name-event') ?: $this->getFirstEventId();
        $field = $this->getProjectSetting('name-field');
        $param = array(
            'project_id' => $this->getProjectId(),
            'events' => [$eventId],
            'recocrds' => [$id]
        );
        $data = REDCap::getData($param);
        return $data[$id][$eventId][$field];
    }

    public function getFirstEventId($pid = null)
    {
        $pid = $this->requireProjectId($pid);
        $results = $this->query("
			select event_id
			from redcap_events_arms a
			join redcap_events_metadata m
				on a.arm_id = m.arm_id
			where a.project_id = ?
			order by arm_num, day_offset, descrip
		", [$pid]);

        $row = $results->fetch_assoc();
        return $row['event_id'];
    }


    /**
     * @return null
     */
    public function getHoursLock($reservationEventId)
    {
        if(!$this->hoursLock){
            $instance = $this->getSchedulerInstanceViaReservationId($reservationEventId);
            $this->setHoursLock($instance['hours-lock']?:0);
        }
        return $this->hoursLock;
    }

    /**
     * @param null $hoursLock
     */
    public function setHoursLock($hoursLock): void
    {
        $this->hoursLock = $hoursLock;
    }

    /**
     * @return null
     */
    public function getLockTime($reservationEventId)
    {
        if(!$this->lockTime){
            $this->setLockTime((int)(time() + $this->getHoursLock($reservationEventId) * 60 * 60));
        }
        return $this->lockTime;
    }

    /**
     * @param null $lockTime
     */
    public function setLockTime($lockTime): void
    {
        $this->lockTime = $lockTime;
    }


}