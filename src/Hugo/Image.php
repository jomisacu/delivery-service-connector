<?php


namespace Jomisacu\DeliveryServiceConnector\Hugo;

use function json_encode;

final class Image
{
    /**
     * @var string
     */
    private $format;

    /**
     * @var string[]
     */
    private $urls;

    /**
     * @param string $format used to provide the images (url)
     * @param string[] $urls
     */
    public function __construct($format, $urls)
    {
        $this->format = $format;
        $this->urls = $urls;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return string[]
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
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
            'format' => $this->format,
            'list' => $this->urls,
        ];
    }
}