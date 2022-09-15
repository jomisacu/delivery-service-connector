<?php

namespace Jomisacu\DeliveryServiceConnector\Contracts;

interface ProductRepositoryInterface
{
    /**
     * @param int|string $productId
     * @return ProductInterface
     */
    public function findById($productId);
}
