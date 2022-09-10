<?php

namespace Jomisacu\DeliveryServiceConnector\Contracts;

interface ProductInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return array
     */
    public function toArray();

    /**
     * @return string
     */
    public function toJson();
}