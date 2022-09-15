<?php

namespace Jomisacu\DeliveryServiceConnector\Contracts;

interface ProductInterface
{
    /**
     * @return array
     */
    public function toArray();

    /**
     * @return string
     */
    public function toJson();
}
