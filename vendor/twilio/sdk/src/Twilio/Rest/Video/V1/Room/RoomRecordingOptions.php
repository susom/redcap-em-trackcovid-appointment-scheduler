<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Video\V1\Room;

use Twilio\Options;
use Twilio\Values;

abstract class RoomRecordingOptions
{
    /**
     * @param string $status Read only the recordings with this status
     * @param string $sourceSid Read only the recordings that have this source_sid
     * @param \DateTime $dateCreatedAfter Read only Recordings that started on or
     *                                    after this ISO 8601 datetime with time
     *                                    zone
     * @param \DateTime $dateCreatedBefore Read only Recordings that started before
     *                                     this ISO 8601 date-time with time zone
     * @return ReadRoomRecordingOptions Options builder
     */
    public static function read(
        $status = Values::NONE,
        $sourceSid = Values::NONE,
        $dateCreatedAfter = Values::NONE,
        $dateCreatedBefore = Values::NONE
    ) {
        return new ReadRoomRecordingOptions($status, $sourceSid, $dateCreatedAfter, $dateCreatedBefore);
    }
}

class ReadRoomRecordingOptions extends Options
{
    /**
     * @param string $status Read only the recordings with this status
     * @param string $sourceSid Read only the recordings that have this source_sid
     * @param \DateTime $dateCreatedAfter Read only Recordings that started on or
     *                                    after this ISO 8601 datetime with time
     *                                    zone
     * @param \DateTime $dateCreatedBefore Read only Recordings that started before
     *                                     this ISO 8601 date-time with time zone
     */
    public function __construct(
        $status = Values::NONE,
        $sourceSid = Values::NONE,
        $dateCreatedAfter = Values::NONE,
        $dateCreatedBefore = Values::NONE
    ) {
        $this->options['status'] = $status;
        $this->options['sourceSid'] = $sourceSid;
        $this->options['dateCreatedAfter'] = $dateCreatedAfter;
        $this->options['dateCreatedBefore'] = $dateCreatedBefore;
    }

    /**
     * Read only the recordings with this status. Can be: `processing`, `completed`, or `deleted`.
     *
     * @param string $status Read only the recordings with this status
     * @return $this Fluent Builder
     */
    public function setStatus($status)
    {
        $this->options['status'] = $status;
        return $this;
    }

    /**
     * Read only the recordings that have this `source_sid`.
     *
     * @param string $sourceSid Read only the recordings that have this source_sid
     * @return $this Fluent Builder
     */
    public function setSourceSid($sourceSid)
    {
        $this->options['sourceSid'] = $sourceSid;
        return $this;
    }

    /**
     * Read only recordings that started on or after this [ISO 8601](https://en.wikipedia.org/wiki/ISO_8601) datetime with time zone.
     *
     * @param \DateTime $dateCreatedAfter Read only Recordings that started on or
     *                                    after this ISO 8601 datetime with time
     *                                    zone
     * @return $this Fluent Builder
     */
    public function setDateCreatedAfter($dateCreatedAfter)
    {
        $this->options['dateCreatedAfter'] = $dateCreatedAfter;
        return $this;
    }

    /**
     * Read only Recordings that started before this [ISO 8601](https://en.wikipedia.org/wiki/ISO_8601) datetime with time zone.
     *
     * @param \DateTime $dateCreatedBefore Read only Recordings that started before
     *                                     this ISO 8601 date-time with time zone
     * @return $this Fluent Builder
     */
    public function setDateCreatedBefore($dateCreatedBefore)
    {
        $this->options['dateCreatedBefore'] = $dateCreatedBefore;
        return $this;
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString()
    {
        $options = array();
        foreach ($this->options as $key => $value) {
            if ($value != Values::NONE) {
                $options[] = "$key=$value";
            }
        }
        return '[Twilio.Video.V1.ReadRoomRecordingOptions ' . \implode(' ', $options) . ']';
    }
}