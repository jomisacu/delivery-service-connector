<?php

namespace Jomisacu\DeliveryServiceConnector\Contracts;

interface OrderInterface
{
    /**
     * @return string
     */
    public function getIdentifier();
}