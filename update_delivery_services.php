<?php

use Jomisacu\DeliveryServiceConnector\Contracts\DriverInterface;
use Jomisacu\DeliveryServiceConnector\Contracts\ProductInterface;
use Jomisacu\DeliveryServiceConnector\Hugo\Driver;
use Jomisacu\DeliveryServiceConnector\Hugo\Product;

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

if (!isset($action)) {
    exit('action is required');
}

if ($action == 'process_queue') {
    $delivery = isset($_REQUEST['delivery']) ? $_REQUEST['delivery'] : null;
    $limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : null;
    $loop = isset($_REQUEST['loop']) ? $_REQUEST['loop'] : null;

    processQueue($delivery, $limit, $loop);
}

/**
 * @throws Exception
 */
function processQueue($delivery, $limit = 10, $loop = false)
{
    DeliveryHelpers::validateDeliveryService($delivery);

    DeliveryHelpers::validateLimit($limit);

    $events = DeliveryHelpers::getEvents($delivery, $limit);

    $deliveryService = DeliveryHelpers::getDeliveryService($delivery);
    $eventIds = [];

    foreach ($events as $event) {
        if (!$event['product_id']) {
            DeliveryHelpers::deleteEvents([$event['id']]);
        }

        $product = DeliveryHelpers::getProduct($delivery, $event['product_id']);

        if ($event['event_name'] == 'product.changed') {
            echo "Updating product {$product->getName()} in '$delivery' service...\n";
            $deliveryService->updateProduct($product);
            echo "ok\n";
        } elseif ($event['event_name'] == 'product.created') {
            echo "Publishing product {$product->getName()} in '$delivery' service...\n";
            $deliveryService->publishProduct($product);
            echo "ok\n";
        } elseif ($event['event_name'] == 'product.removed') {
            echo "Removing product {$product->getName()} in '$delivery' service...\n";
            $deliveryService->removeProduct($product);
            echo "ok\n";
        }

        $eventIds[] = $event['id'];
    }

    DeliveryHelpers::markEventsProcessed($delivery, $eventIds);

    if (count($eventIds) == $limit) {
        if ($loop) {
            echo <<<SCRIPT
<script>
    setTimeout(function() {
        window.location.href = '/update_delivery_services.php?action=process_queue&delivery=$delivery&loop=1&limit=$limit'
    }, 1000);
</script>
SCRIPT;
            exit;
        }

        exit("Finished\n");
    }
}

class ProductEvents
{
    const PRODUCT_PUBLISHED = 'product.published';
    const PRODUCT_UPDATED = 'product.updated';
    const PRODUCT_REMOVED = 'product.removed';
}

class DeliveryHelpers
{
    private static $pdo;

    public static function logEvent($eventName, $productId)
    {
        self::validateEventName($eventName);

        $sql = '
            INSERT INTO ds_product_events (event_name, product_id, occurred_on) VALUES (?, ?, ?)
        ';
        self::getDatabaseService()->prepare($sql)->execute([$eventName, $productId, time()]);
    }

    /**
     * @throws Exception
     */
    public static function validateEventName($eventName)
    {
        if (!in_array(
            $eventName,
            [
                ProductEvents::PRODUCT_PUBLISHED,
                ProductEvents::PRODUCT_UPDATED,
                ProductEvents::PRODUCT_REMOVED,
            ]
        )) {
            throw new Exception('Wrong product event name');
        }
    }

    /**
     * @return PDO
     */
    public static function getDatabaseService()
    {
        global $dbhost, $dbuser, $dbpass, $dbbase;
        if (self::$pdo === null) {
            $dsn = sprintf('mysql:dbname=%s;host=%s', $dbbase, $dbhost);
            self::$pdo = new PDO($dsn, $dbuser, $dbpass);
        }

        return self::$pdo;
    }

    public static function deleteEvents(array $eventIds)
    {
        $sql = 'DELETE FROM ds_product_events WHERE id IN (' . implode(',', $eventIds) . ')';
        DeliveryHelpers::getDatabaseService()->prepare($sql)->execute();
    }

    /**
     * @param $productId
     * @return ProductInterface
     * @throws Exception
     */
    public static function getProduct($service, $productId)
    {
        DeliveryHelpers::validateDeliveryService($service);

        $product = null;

        if ($service == 'hugo') {
            $product = self::getProductHugo($productId);
        }

        if (null == $product) {
            throw new Exception('Can\'t create product object for "'.$service.'" driver. ');
        }

        return $product;
    }

    /**
     * @param string $delivery
     * @throws Exception
     */
    public static function validateDeliveryService($delivery)
    {
        $deliveryServices = DeliveryHelpers::getDeliveryServiceNames();
        if (!in_array($delivery, $deliveryServices)) {
            throw new Exception('Wrong delivery service. Use one of: ' . implode(', ', $deliveryServices));
        }
    }

    /**
     * @return string[]
     */
    public static function getDeliveryServiceNames()
    {
        return [
            'hugo'
        ];
    }

    private static function getProductHugo($productId)
    {
        $productInfo = self::loadBaynaoProductInfo($productId);

        // TODO aquí hay que poner las categorias de baynao y en los valores poner los identificadores para cada sistema de delivery
        //
        //  TAXONOMIAS EN HUGO
        //
        //  [5bb412f5994c1506ed46fcd2] => BOCINAS
        //  [5be8a051994c15511a6b4f72] => Toners
        //  [5cb0cbd3deff7f742b5c7db4] => Adaptadores
        //  [5d1dc1fb193e9802cd1e3a83] => Baterias
        //  [5d543d31e19103253340a80e] => Antenas
        //  [5d543d31e19103253340a819] => Regletas
        //  [5d80063f7f693227a13f46db] => Adaptadores y Convertidores
        //  [5d80063f7f693227a13f46df] => Protectores y Reguladores De Voltaje
        //  [5dceb8ff12bbdd19bcb33650] => Jack
        //  [5e66632ebed23276f57635e2] => Pantallas
        //  [5ebb84d40d5d2f604c00c243] => Impresoras
        //  [5ec31ba258b8701cef6d49e4] => Tablets
        //  [5ec31e65bf121b3e2963d982] => Memoria Micro SD
        //  [5ee9184d0d5d2f224d593ec4] => Computadoras
        //  [5f0e1f7e22e2ad271d7733a5] => Disco Duro
        //  [5f0e1f7e22e2ad271d7733ab] => Audifonos
        //  [5f1f5f002d0aa27629610ab6] => Reproductor De Musica Mp3
        //  [5f2422662d0aa2669c6de6b1] => Otros
        //  [5fa485646c419d71470df6e1] => Memorias USB
        //  [5fac2217844150171e78d061] => Ups
        //  [60351a29ef836e457936ce75] => Teclados
        //  [6046d9990d5d2f4f9628e2f5] => Laptops
        //  [606dde52df0d6c0e3a4a1c92] => Cargadores
        //  [608091f547ba0a49b40c38b2] => Papel
        //  [60c21c2b39fdf301f803eca7] => Televisores
        //  [61368323974f712349411fde] => Monitores
        //  [619e7221ca8e31541a1edd32] => Switch
        //  [622f47cc6b5ec22913187032] => Bultos
        //  [623a2dc7f7f461320a6755d2] => Memorias Ram
        //  [6286a23351212c02120d6002] => Mouse
        //  [62aa61845745ad4ff40cc947] => Gamepads
        //  [62c4831ba3e93c3d002353fb] => Covers
        //  [62f54c333416781722280a08] => Scanners
        //  [62ffa0fe3ab4ee1d3c55fd28] => abanicos de computadora
        //  [62ffa12b8ec84b100e554712] => Adaptadores de red
        //  [62ffa176b9c90a393270c662] => Alarmas
        //  [62ffa1b004706a0c1609a3e3] => Bandejas de pc
        //  [62ffa1d681d73e75a84c51a5] => bases de pc
        //  [62ffa226d7ce7c3d19098c32] => Cables hdmi
        //  [62ffa2353cbadc1b96239d42] => Cables de pc
        //  [62ffa24a3cbadc1b96239d43] => Camaras digitales
        //  [62ffa26465b9795d36602472] => Camaras de vigilancia
        //  [62ffa29022aa0c215c459c93] => Cartuchos y tintas
        //  [62ffa2ac1ed13a6d5265d254] => Cases de computadora
        //  [62ffa2c9d10cec05567cb712] => Dvds y Cds vacios
        //  [62ffa3126f929b6c9a39d284] => Cooling pad
        //  [62ffa3360a72fa29b36b6192] => Cpu
        //  [62ffa3acdddaa048da64d7cc] => Telefonos ip
        //  [62ffa3c203e77137fa67c8b2] => Tarjetas de video
        //  [62ffa3e840aea141d5544832] => tarjetas de red
        //  [62ffa42c40aea141d5544833] => Antivirus y programas
        //  [62ffa43ddddaa048da64d7cd] => Powerline
        //  [62ffa45803e77137fa67c8b5] => Power supply
        //  [62ffa48acada91688c3600b7] => Motherboard
        //  [62ffa4fa7df829125f53b345] => Lectores de memoria
        //  [62ffa515dddaa048da64d7d1] => Hub usb
        //  [62ffa54d54059240413042a3] => Gabinetes de computadoras
        //  [62ffa5dd0991b062a67e7aa4] => Drv
        //  [62ffa5fadea7e040270eee53] => Drvw y Cdrom

        $taxonomies = [
            // "Bynao category" => "hugo taxonomy idnetifier"
            'Otros' => '5f2422662d0aa2669c6de6b1',
        ];

        $product = new Product(
            $productInfo['sku'],
            1,
            $productInfo['name'],
            $productInfo['price'],
            $productInfo['description'],
            '',
            $productInfo['sku'],
            $productInfo['image_url']
        );

        if (isset($taxonomies[$productInfo['category_id']])) {
            $product->addTaxonomy($taxonomies[$productInfo['category_id']]);
        } else {
            $product->addTaxonomy($taxonomies['Otros']);
        }

        return $product;
    }

    /**
     * @param $productId
     * @return array
     */
    private static function loadBaynaoProductInfo($productId)
    {
        throw new Exception(
            'Debes implementar el metodo loadBaynaoProductInfo que devuelva los datos del producto en forma de array'
        );
    }

    /**
     * @param $service
     * @return DriverInterface
     * @throws Exception
     */
    public static function getDeliveryService($service)
    {
        static $services = [];

        if (isset($services[$service])) {
            return $services[$service];
        }

        DeliveryHelpers::validateDeliveryService($service);

        if ($service == 'hugo') {
            $services[$service] = self::getHugoDriver();

            return $services[$service];
        }

        throw new Exception('Can\'t build the object. The service ' . $service . ' is not registered');
    }

    private static function getHugoDriver()
    {
        $developmentApiUrl = 'https://api-integraciones-integraciones.hugoapp.dev';
        $developmentApiUsername = 'baynao@hugoapp.com';
        $developmentApiPassword = 'ti0dHeP67gfH1JR58MIlUXy';

        $productionApiUrl = 'https://api-integraciones-integraciones.hugoapp.dev';
        $productionApiUsername = 'info@baynao.do';
        $productionApiPassword = '134679258Crazy@';

        if (self::isDevMode()) {
            $apiUrl = $developmentApiUrl;
            $apiUsername = $developmentApiUsername;
            $apiPassword = $developmentApiPassword;
        } else {
            $apiUrl = $productionApiUrl;
            $apiUsername = $productionApiUsername;
            $apiPassword = $productionApiPassword;
        }

        $partnerKey = 'J4AnWkh7yg';

        return new Driver($apiUrl, $partnerKey, $apiUsername, $apiPassword, '');
    }

    public static function isDevMode()
    {
        return true;
    }

    /**
     * @param $delivery
     * @param int $limit
     * @return array{id: int, event_name: string, occurred_on: int, hugo_ok: int}
     * @throws Exception
     */
    public static function getEvents($delivery, $limit = 10)
    {
        DeliveryHelpers::validateDeliveryService($delivery);

        DeliveryHelpers::validateLimit($limit);

        $sql = sprintf('SELECT * FROM ds_product_events WHERE %s_ok <> 1 LIMIT %s', $delivery, $limit);
        $statement = DeliveryHelpers::getDatabaseService()->prepare($sql);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $limit
     * @throws Exception
     */
    public static function validateLimit($limit)
    {
        if (!is_int($limit) || $limit <= 0) {
            throw new Exception('Wrong limit value. Use a greater than zero value');
        }
    }

    /**
     * @throws Exception
     */
    public static function markEventsProcessed($delivery, array $eventIds)
    {
        self::validateDeliveryService($delivery);

        $eventIds = array_filter(
            $eventIds,
            function ($eventId) {
                return is_scalar($eventId) && intval($eventId);
            }
        );

        if (!$eventIds) {
            return;
        }

        self::getDatabaseService()->exec(
            "
            UPDATE ds_product_events 
            SET {$delivery}_ok = 1 
            WHERE id IN (" . implode(", ", $eventIds) . ") 
        "
        );
    }
}
