<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function

use PHPMailer\PHPMailer\PHPMailer;
use Kigkonsult\Icalcreator\Vcalendar;

// Load Composer's autoloader
require 'vendor/autoload.php';

/**
 * Class CalendarEmail
 * @property string $headers
 * @property string $calendarBody
 * @property string $calendarOrganizer
 * @property string $calendarOrganizerEmail
 * @property string $calendarLocation
 * @property string $calendarDate
 * @property string $calendarStartTime
 * @property string $calendarEndTime
 * @property string $calendarSubject
 * @property string $calendarDescription
 * @property array $calendarParticipants
 * @property string $urlString
 * @property PHPMailer $mail
 *
 */
class TrackCovidSharedCalendarEmail extends Message
{

    private $headers;
    private $calendarBody;
    private $calendarOrganizer;
    private $calendarOrganizerEmail;
    private $calendarParticipants = array();
    private $calendarLocation;
    private $calendarDate;
    private $calendarStartTime;
    private $calendarEndTime;
    private $calendarSubject;
    private $calendarDescription;
    private $urlString;
    private $mail;

    /**
     * @return PHPMailer
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * @param PHPMailer $mail
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
    }


    /**
     * @return string
     */
    public function getUrlString()
    {
        return $this->urlString;
    }

    /**
     * @param string $urlString
     */
    public function setUrlString($urlString)
    {
        $this->urlString = $urlString;
    }

    /**
     * @return string
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getCalendarBody()
    {
        return $this->calendarBody;
    }

    /**
     * @param string $calendarBody
     */
    public function setCalendarBody($calendarBody)
    {
        $this->calendarBody = $calendarBody;
    }

    /**
     * @return string
     */
    public function getCalendarOrganizer()
    {
        return $this->calendarOrganizer;
    }

    /**
     * @param string $calendarOrganizer
     */
    public function setCalendarOrganizer($calendarOrganizer)
    {
        $this->calendarOrganizer = $calendarOrganizer;
    }

    /**
     * @return string
     */
    public function getCalendarOrganizerEmail()
    {
        return $this->calendarOrganizerEmail;
    }

    /**
     * @param string $calendarOrganizerEmail
     */
    public function setCalendarOrganizerEmail($calendarOrganizerEmail)
    {
        $this->calendarOrganizerEmail = $calendarOrganizerEmail;
    }

    /**
     * @return string
     */
    public function getCalendarLocation()
    {
        return $this->calendarLocation;
    }

    /**
     * @param string $calendarLocation
     */
    public function setCalendarLocation($calendarLocation)
    {
        $this->calendarLocation = $calendarLocation;
    }

    /**
     * @return string
     */
    public function getCalendarDate()
    {
        return $this->calendarDate;
    }

    /**
     * @param string $calendarDate
     */
    public function setCalendarDate($calendarDate)
    {
        $this->calendarDate = $calendarDate;
    }

    /**
     * @return string
     */
    public function getCalendarStartTime()
    {
        return $this->calendarStartTime;
    }

    /**
     * @param string $calendarStartTime
     */
    public function setCalendarStartTime($calendarStartTime)
    {
        $this->calendarStartTime = $calendarStartTime;
    }

    /**
     * @return string
     */
    public function getCalendarEndTime()
    {
        return $this->calendarEndTime;
    }

    /**
     * @param string $calendarEndTime
     */
    public function setCalendarEndTime($calendarEndTime)
    {
        $this->calendarEndTime = $calendarEndTime;
    }

    /**
     * @return string
     */
    public function getCalendarSubject()
    {
        return $this->calendarSubject;
    }

    /**
     * @param string $calendarSubject
     */
    public function setCalendarSubject($calendarSubject)
    {
        $this->calendarSubject = $calendarSubject;
    }

    /**
     * @return string
     */
    public function getCalendarDescription()
    {
        return $this->calendarDescription;
    }

    /**
     * @param string $calendarDescription
     */
    public function setCalendarDescription($calendarDescription)
    {
        $this->calendarDescription = $calendarDescription;
    }

    /**
     * @return array
     */
    public function getCalendarParticipants()
    {
        return $this->calendarParticipants;
    }

    /**
     * @param array $calendarParticipants
     */
    public function setCalendarParticipants($calendarParticipants)
    {
        foreach ($calendarParticipants as $name => $participant) {
            $this->calendarParticipants[$name] = $participant;
        }
    }


    /**
     * @param array $param
     */
    public function prepareCalendarData($param)
    {
        $this->setCalendarOrganizerEmail($param['calendarOrganizerEmail']);
        $this->setCalendarOrganizer($param['calendarOrganizer']);
        $this->setCalendarSubject($param['calendarSubject']);
        $this->setCalendarDescription($param['calendarDescription']);
        $this->setcalendarLocation($param['calendarLocation']);
        $this->setCalendarDate($param['calendarDate']);
        $this->setCalendarStartTime($param['calendarStartTime']);
        $this->setCalendarEndTime($param['calendarEndTime']);
        $this->setCalendarParticipants($param['calendarParticipants']);
    }

    /**
     * send calendar event email
     * @param array $param
     * @return bool
     */
    public function sendCalendarEmail($param){
        try {

            $this->prepareCalendarData($param);

            $vcalendar = Vcalendar::factory([Vcalendar::UNIQUE_ID => (string)(mt_rand()),])

                // with calendaring info
                ->setMethod(Vcalendar::REQUEST)
                ->setXprop(
                    Vcalendar::X_WR_CALNAME,
                    $this->getSubject()
                )
                ->setXprop(
                    Vcalendar::X_WR_CALDESC,
                    $this->getSubject()
                )
                ->setXprop(
                    Vcalendar::X_WR_RELCALID,
                    "3E26604A-50F4-4449-8B3E-E4F4932D05B5"
                )
                ->setXprop(
                    Vcalendar::X_WR_TIMEZONE,
                    "America/Los_Angeles"
                );

            $event1 = $vcalendar->newVevent()
                ->setTransp(Vcalendar::OPAQUE)
                ->setClass(Vcalendar::P_BLIC)
                ->setSequence(1)
                // describe the event
                ->setSummary($this->getSubject())
                ->setDescription(
                    $this->getCalendarDescription(),
                    [
                        Vcalendar::ALTREP =>
                            'CID:<FFFF__=0ABBE548DFE235B58f9e8a93d@stanford.edu>'
                    ]
                )
                //->setComment( 'It\'s going to be fun..' )
                // place the event
                //->setLocation( 'Kafé Ekorren Stockholm' )
                //->setGeo( '59.32206', '18.12485' )
                // set the time
                ->setDtstart(
                    new DateTime(
                        $this->getCalendarDate() . "T" . $this->getCalendarStartTime(),
                        new DateTimezone('America/Los_Angeles')
                    )
                )
                ->setDtend(
                    new DateTime(
                        $this->getCalendarDate() . "T" . $this->getCalendarEndTime(),
                        new DateTimezone("America/Los_Angeles")
                    )
                )
                // organizer, chair and some participants
                ->setOrganizer(
                    $this->getCalendarOrganizerEmail(),
                    [Vcalendar::CN => $this->getCalendarOrganizer()]
                );
            //set event participants
            foreach ($this->getCalendarParticipants() as $name => $e) {
                $event1->setAttendee(
                    $e,
                    [
                        Vcalendar::ROLE => Vcalendar::REQ_PARTICIPANT,
                        Vcalendar::PARTSTAT => Vcalendar::NEEDS_ACTION,
                        Vcalendar::RSVP => Vcalendar::TRUE,
                        Vcalendar::CN => $name,
                    ]
                );
            }

            //generate event string.
            $vcalendarString =
                // apply appropriate Vtimezone with Standard/DayLight components
                $vcalendar->vtimezonePopulate()
                    // and create the (string) calendar
                    ->createCalendar();


            $tempFile = sys_get_temp_dir() . "/invite.ics";
            $fileHandle = fopen($tempFile, "w");
            fwrite($fileHandle, $vcalendarString);
            $this->setAttachment($tempFile, 'invite.ics');
            fclose($fileHandle);

            return $this->send();
        } catch (\LogicException $e) {
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }

}
