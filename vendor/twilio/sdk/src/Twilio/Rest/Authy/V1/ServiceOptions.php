<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Authy\V1;

use Twilio\Options;
use Twilio\Values;

/**
 * PLEASE NOTE that this class contains preview products that are subject to change. Use them with caution. If you currently do not have developer preview access, please contact help@twilio.com.
 */
abstract class ServiceOptions
{
    /**
     * @param string $friendlyName A human readable description of this resource.
     * @return UpdateServiceOptions Options builder
     */
    public static function update($friendlyName = Values::NONE)
    {
        return new UpdateServiceOptions($friendlyName);
    }
}

class UpdateServiceOptions extends Options
{
    /**
     * @param string $friendlyName A human readable description of this resource.
     */
    public function __construct($friendlyName = Values::NONE)
    {
        $this->options['friendlyName'] = $friendlyName;
    }

    /**
     * A human readable description of this resource, up to 64 characters.
     *
     * @param string $friendlyName A human readable description of this resource.
     * @return $this Fluent Builder
     */
    public function setFriendlyName($friendlyName)
    {
        $this->options['friendlyName'] = $friendlyName;
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
        return '[Twilio.Authy.V1.UpdateServiceOptions ' . \implode(' ', $options) . ']';
    }
}