<?php


namespace Jomisacu\DeliveryServiceConnector\Hugo;

use function json_encode;

final class Taxonomy
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * Taxonomy constructor.
     *
     * @param string $identifier
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getIdentifier();
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->getIdentifier());
    }
}