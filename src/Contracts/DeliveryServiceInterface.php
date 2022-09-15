<?php

namespace Jomisacu\DeliveryServiceConnector\Contracts;

interface DeliveryServiceInterface
{
    /**
     * @param OrderInterface $order
     * @return void
     */
    public function acceptOrder(OrderInterface $order);

    /**
     * @param $productId
     * @return void
     */
    public function updateProductById($productId);

    /**
     * @param $productId
     * @return void
     */
    public function publishProductById($productId);

    /**
     * @param $productId
     * @return void
     */
    public function removeProductById($productId);

    /**
     * @return string
     */
    public function getName();
}
