<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Notify\V1\Service;

use Twilio\Options;
use Twilio\Values;

/**
 * PLEASE NOTE that this class contains beta products that are subject to change. Use them with caution.
 */
abstract class BindingOptions
{
    /**
     * @param string $tag A tag that can be used to select the Bindings to notify
     * @param string $notificationProtocolVersion The protocol version to use to
     *                                            send the notification
     * @param string $credentialSid The SID of the Credential resource to be used
     *                              to send notifications to this Binding
     * @param string $endpoint Deprecated
     * @return CreateBindingOptions Options builder
     */
    public static function create(
        $tag = Values::NONE,
        $notificationProtocolVersion = Values::NONE,
        $credentialSid = Values::NONE,
        $endpoint = Values::NONE
    ) {
        return new CreateBindingOptions($tag, $notificationProtocolVersion, $credentialSid, $endpoint);
    }

    /**
     * @param \DateTime $startDate Only include usage that has occurred on or after
     *                             this date
     * @param \DateTime $endDate Only include usage that occurred on or before this
     *                           date
     * @param string $identity The `identity` value of the resources to read
     * @param string $tag Only list Bindings that have all of the specified Tags
     * @return ReadBindingOptions Options builder
     */
    public static function read(
        $startDate = Values::NONE,
        $endDate = Values::NONE,
        $identity = Values::NONE,
        $tag = Values::NONE
    ) {
        return new ReadBindingOptions($startDate, $endDate, $identity, $tag);
    }
}

class CreateBindingOptions extends Options
{
    /**
     * @param string $tag A tag that can be used to select the Bindings to notify
     * @param string $notificationProtocolVersion The protocol version to use to
     *                                            send the notification
     * @param string $credentialSid The SID of the Credential resource to be used
     *                              to send notifications to this Binding
     * @param string $endpoint Deprecated
     */
    public function __construct(
        $tag = Values::NONE,
        $notificationProtocolVersion = Values::NONE,
        $credentialSid = Values::NONE,
        $endpoint = Values::NONE
    ) {
        $this->options['tag'] = $tag;
        $this->options['notificationProtocolVersion'] = $notificationProtocolVersion;
        $this->options['credentialSid'] = $credentialSid;
        $this->options['endpoint'] = $endpoint;
    }

    /**
     * A tag that can be used to select the Bindings to notify. Repeat this parameter to specify more than one tag, up to a total of 20 tags.
     *
     * @param string $tag A tag that can be used to select the Bindings to notify
     * @return $this Fluent Builder
     */
    public function setTag($tag)
    {
        $this->options['tag'] = $tag;
        return $this;
    }

    /**
     * The protocol version to use to send the notification. This defaults to the value of `default_xxxx_notification_protocol_version` for the protocol in the [Service](https://www.twilio.com/docs/notify/api/service-resource). The current version is `"3"` for `apn`, `fcm`, and `gcm` type Bindings. The parameter is not applicable to `sms` and `facebook-messenger` type Bindings as the data format is fixed.
     *
     * @param string $notificationProtocolVersion The protocol version to use to
     *                                            send the notification
     * @return $this Fluent Builder
     */
    public function setNotificationProtocolVersion($notificationProtocolVersion)
    {
        $this->options['notificationProtocolVersion'] = $notificationProtocolVersion;
        return $this;
    }

    /**
     * The SID of the [Credential](https://www.twilio.com/docs/notify/api/credential-resource) resource to be used to send notifications to this Binding. If present, this overrides the Credential specified in the Service resource. Applies to only `apn`, `fcm`, and `gcm` type Bindings.
     *
     * @param string $credentialSid The SID of the Credential resource to be used
     *                              to send notifications to this Binding
     * @return $this Fluent Builder
     */
    public function setCredentialSid($credentialSid)
    {
        $this->options['credentialSid'] = $credentialSid;
        return $this;
    }

    /**
     * Deprecated.
     *
     * @param string $endpoint Deprecated
     * @return $this Fluent Builder
     */
    public function setEndpoint($endpoint)
    {
        $this->options['endpoint'] = $endpoint;
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
        return '[Twilio.Notify.V1.CreateBindingOptions ' . \implode(' ', $options) . ']';
    }
}

class ReadBindingOptions extends Options
{
    /**
     * @param \DateTime $startDate Only include usage that has occurred on or after
     *                             this date
     * @param \DateTime $endDate Only include usage that occurred on or before this
     *                           date
     * @param string $identity The `identity` value of the resources to read
     * @param string $tag Only list Bindings that have all of the specified Tags
     */
    public function __construct(
        $startDate = Values::NONE,
        $endDate = Values::NONE,
        $identity = Values::NONE,
        $tag = Values::NONE
    ) {
        $this->options['startDate'] = $startDate;
        $this->options['endDate'] = $endDate;
        $this->options['identity'] = $identity;
        $this->options['tag'] = $tag;
    }

    /**
     * Only include usage that has occurred on or after this date. Specify the date in GMT and format as `YYYY-MM-DD`.
     *
     * @param \DateTime $startDate Only include usage that has occurred on or after
     *                             this date
     * @return $this Fluent Builder
     */
    public function setStartDate($startDate)
    {
        $this->options['startDate'] = $startDate;
        return $this;
    }

    /**
     * Only include usage that occurred on or before this date. Specify the date in GMT and format as `YYYY-MM-DD`.
     *
     * @param \DateTime $endDate Only include usage that occurred on or before this
     *                           date
     * @return $this Fluent Builder
     */
    public function setEndDate($endDate)
    {
        $this->options['endDate'] = $endDate;
        return $this;
    }

    /**
     * The [User](https://www.twilio.com/docs/chat/rest/user-resource)'s `identity` value of the resources to read.
     *
     * @param string $identity The `identity` value of the resources to read
     * @return $this Fluent Builder
     */
    public function setIdentity($identity)
    {
        $this->options['identity'] = $identity;
        return $this;
    }

    /**
     * Only list Bindings that have all of the specified Tags. The following implicit tags are available: `all`, `apn`, `fcm`, `gcm`, `sms`, `facebook-messenger`. Up to 5 tags are allowed.
     *
     * @param string $tag Only list Bindings that have all of the specified Tags
     * @return $this Fluent Builder
     */
    public function setTag($tag)
    {
        $this->options['tag'] = $tag;
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
        return '[Twilio.Notify.V1.ReadBindingOptions ' . \implode(' ', $options) . ']';
    }
}