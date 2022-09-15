<?php


namespace Jomisacu\DeliveryServiceConnector\Hugo;

use Jomisacu\DeliveryServiceConnector\Contracts\ProductInterface;

use function json_encode;

final class Product implements ProductInterface
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var int
     */
    private $sort;

    /**
     * @var string
     */
    private $name;

    /**
     * @var float
     */
    private $price;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $extraInfo;

    /**
     * @var string
     */
    private $eiPartnerCode;

    /**
     * @var Option[]
     */
    private $options = [];

    /**
     * @var string[]
     */
    private $images;

    /**
     * @var string[]
     */
    private $taxonomies = [];

    /**
     * @var array
     */
    private $locations = [];

    /**
     * Product constructor.
     *
     * @param string $sku
     * @param int $sort
     * @param string $name
     * @param float $price
     * @param string $description
     * @param string $extraInfo
     * @param string $eiPartnerCode
     * @param string $image
     */
    public function __construct($sku, $sort, $name, $price, $description, $extraInfo, $eiPartnerCode, $image)
    {
        $this->sku = $sku;
        $this->sort = $sort;
        $this->name = $name;
        $this->price = $price;
        $this->description = $description;
        $this->extraInfo = $extraInfo;
        $this->eiPartnerCode = $eiPartnerCode;

        $this->addImage($image);
    }

    /**
     * @param string $imagePublicUrl
     * @return void
     */
    public function addImage($imagePublicUrl)
    {
        $this->images[] = $imagePublicUrl;
    }

    /**
     * @param Option $option
     * @return void
     */
    public function addOption(Option $option)
    {
        $this->options[] = $option;
    }

    /**
     * @param string $taxonomyIdentifier
     * @return void
     */
    public function addTaxonomy($taxonomyIdentifier)
    {
        $this->taxonomies[] = $taxonomyIdentifier;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $options = [];
        foreach ($this->options as $option) {
            $options[] = $option->toArray();
        }

        $final = [
            'sku' => $this->sku,
            'sort' => $this->sort,
            'name' => $this->name,
            'price' => $this->price,
            'description' => $this->description,
            'extra_info' => $this->extraInfo,
            'ei_partner_code' => $this->eiPartnerCode,
            'images' => [
                [
                    'format' => 'url',
                    'list' => $this->images,
                ],
            ],
        ];

        if ($this->taxonomies) {
            $final['taxonomies'] = $this->taxonomies;
        }

        if ($options) {
            $final['options'] = $options;
        }

        if ($this->locations) {
            $final['locations'] = $this->locations;
        }

        return $final;
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getExtraInfo()
    {
        return $this->extraInfo;
    }

    /**
     * @return string
     */
    public function getEiPartnerCode()
    {
        return $this->eiPartnerCode;
    }

    /**
     * @return Option[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string[]
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @return string[]
     */
    public function getTaxonomies()
    {
        return $this->taxonomies;
    }

    public function addLocation($locationId)
    {
        $this->locations[] = $locationId;
    }

    public function getLocations()
    {
        return $this->locations;
    }
}
