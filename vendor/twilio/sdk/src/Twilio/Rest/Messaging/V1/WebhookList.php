<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Messaging\V1;

use Twilio\ListResource;
use Twilio\Version;

/**
 * PLEASE NOTE that this class contains preview products that are subject to change. Use them with caution. If you currently do not have developer preview access, please contact help@twilio.com.
 */
class WebhookList extends ListResource
{
    /**
     * Construct the WebhookList
     *
     * @param Version $version Version that contains the resource
     * @return \Twilio\Rest\Messaging\V1\WebhookList
     */
    public function __construct(Version $version)
    {
        parent::__construct($version);

        // Path Solution
        $this->solution = array();
    }

    /**
     * Constructs a WebhookContext
     *
     * @return \Twilio\Rest\Messaging\V1\WebhookContext
     */
    public function getContext()
    {
        return new WebhookContext($this->version);
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString()
    {
        return '[Twilio.Messaging.V1.WebhookList]';
    }
}