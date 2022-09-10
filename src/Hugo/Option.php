<?php


namespace Jomisacu\DeliveryServiceConnector\Hugo;

use function json_encode;

final class Option
{
    /**
     * @var string
     */
    private $categoryName;

    /**
     * @var bool
     */
    private $isRequired;

    /**
     * @var bool
     */
    private $multiple;

    /**
     * @var int
     */
    private $max;

    /**
     * @var OptionValue[]
     */
    private $optionsValues;

    /**
     * Option constructor.
     *
     * @param string $categoryName
     * @param bool $isRequired
     * @param bool $multiple
     * @param int $max
     * @param OptionValue[] $optionsValues
     */
    public function __construct(
        $categoryName,
        $isRequired,
        $multiple,
        $max,
        array $optionsValues
    ) {
        $this->categoryName = $categoryName;
        $this->isRequired = $isRequired;
        $this->multiple = $multiple;
        $this->max = $max;
        $this->optionsValues = $optionsValues;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * @return bool
     */
    public function isMultiple()
    {
        return $this->multiple;
    }

    /**
     * @return int
     */
    public function getMax()
    {
        return $this->max;
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
        $optionsValues = [];

        foreach ($this->getOptionsValues() as $value) {
            $optionsValues[] = $value->toArray();
        }

        return [
            'category_name' => $this->categoryName,
            'is_required' => $this->isRequired,
            'multiple' => $this->multiple,
            'max' => $this->max,
            'options_values' => $optionsValues
        ];
    }

    /**
     * @return OptionValue[]
     */
    public function getOptionsValues()
    {
        return $this->optionsValues;
    }
}