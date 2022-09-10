<?php


namespace Jomisacu\DeliveryServiceConnector\Hugo;

use function json_encode;

final class OptionValue
{
    /**
     * @var string
     */
    private $nameItem;

    /**
     * @var float
     */
    private $price;

    /**
     * @var string
     */
    private $eiPartnerCode;

    /**
     * OptionValue constructor.
     *
     * @param string $nameItem
     * @param float $price
     * @param string $eiPartnerCode
     */
    public function __construct($nameItem, $price, $eiPartnerCode)
    {
        $this->nameItem = $nameItem;
        $this->price = $price;
        $this->eiPartnerCode = $eiPartnerCode;
    }

    /**
     * @return string
     */
    public function getNameItem()
    {
        return $this->nameItem;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getEiPartnerCode()
    {
        return $this->eiPartnerCode;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name_item' => $this->nameItem,
            'price' => $this->price,
            'ei_partner_code' => $this->eiPartnerCode,
        ];
    }
}