<?php

namespace Jomisacu\DeliveryServiceConnector\Contracts;

interface DriverInterface
{
    /**
     * @param ProductInterface $product
     * @return void
     */
    public function publishProduct(ProductInterface $product);

    /**
     * @param ProductInterface $product
     * @return void
     */
    public function updateProduct(ProductInterface $product);

    /**
     * @param ProductInterface $product
     * @return void
     */
    public function removeProduct(ProductInterface $product);

    /**
     * @param OrderInterface $order
     * @return void
     */
    public function acceptOrder(OrderInterface $order);
}