<?php

namespace Jomisacu\DeliveryServiceConnector\Hugo;

use Jomisacu\DeliveryServiceConnector\Contracts\ProductRepositoryInterface;

final class ProductRepositoryHugoJson implements ProductRepositoryInterface
{
    /// this is for test. This file holds a product json structure
    private $filepath = __DIR__ . '/product.json';

    /**
     * @inheritDoc
     */
    public function findById($productId)
    {
        $productInfo = json_decode(file_get_contents($this->filepath), true);

        $product = new Product(
            $productInfo['sku'],
            $productInfo['sort'],
            $productInfo['name'],
            $productInfo['price'],
            $productInfo['description'],
            $productInfo['extra_info'],
            $productInfo['ei_partner_code'],
            $productInfo['images'][0]['list'][0]
        );
        $product->addTaxonomy($productInfo['taxonomies'][0]);
        $product->addImage("https:\/\/baynao.com.do\/baynao_images\/1\/product-9127-02.jpg");

        return $product;
    }
}
