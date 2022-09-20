<?php


namespace Jomisacu\DeliveryServiceConnector\Hugo;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Jomisacu\DeliveryServiceConnector\Contracts\DeliveryServiceInterface;
use Jomisacu\DeliveryServiceConnector\Contracts\OrderInterface;
use Jomisacu\DeliveryServiceConnector\Contracts\ProductInterface;
use Jomisacu\DeliveryServiceConnector\Contracts\ProductRepositoryInterface;
use Jomisacu\DeliveryServiceConnector\OrderRejectedException;

use function json_decode;
use function json_encode;

final class DeliveryService implements DeliveryServiceInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $partnerKey;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $locationId;

    /**
     * Driver constructor.
     * @param string $baseUrl
     * @param string $partnerKey
     * @param string $username
     * @param string $password
     * @param string $locationId
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        $baseUrl,
        $partnerKey,
        $username,
        $password,
        $locationId = null
    ) {
        $this->productRepository = $productRepository;
        $this->baseUrl = $baseUrl;
        $this->partnerKey = $partnerKey;
        $this->username = $username;
        $this->password = $password;
        $this->locationId = $locationId;
    }

    /**
     * @return array
     * @throws GuzzleException
     */
    public function accessTokenRequestWithGuzzle()
    {
        $client = new Client();
        $credentials = [
            'username' => $this->username,
            'password' => $this->password,
        ];
        $options = [
            RequestOptions::BODY => json_encode($credentials),
            RequestOptions::HEADERS => $this->getDefaultHeaders(),
            RequestOptions::ALLOW_REDIRECTS => 10,
        ];
        $response = $client->post($this->baseUrl . '/api/v1/partners/tokens', $options);

        if ($json = $response->getBody()->getContents()) {
            $info = json_decode($json, true);
            if (isset($info['data']['access_token'])) {
                return $info;
            }
        }

        throw new CantRetrieveAccessTokenException('Error retrieving the access token');
    }

    /**
     * @return string[]
     */
    private function getDefaultHeaders()
    {
        return [
            'Accept-Language' => 'en',
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        $cached = $this->getCachedAccessToken($this->username);

        if ($cached) {
            return $cached;
        }

        $json = $this->accessTokenRequestWithCurl();
        $this->cacheToken($this->username, json_encode($json));

        return $json['data']['access_token'];
    }

    /**
     * @param string $username
     * @return false|string
     */
    private function getCachedAccessToken($username)
    {
        $filename = $this->getAccessTokenCacheFilename($username);

        if (!file_exists($filename)) {
            return false;
        }

        $jsonResponse = json_decode(file_get_contents($filename), true);

        if (
            !isset($jsonResponse['data']['access_token'])
            || !$jsonResponse['data']['access_token']
            || !isset($jsonResponse['data']['expires_in'])
            || !$jsonResponse['data']['expires_in']
        ) {
            return false;
        }

        if (time() - filemtime($filename) > $jsonResponse['data']['expires_in']) {
            return false;
        }

        return $jsonResponse['data']['access_token'];
    }

    /**
     * @param string $username
     * @return string
     */
    private function getAccessTokenCacheFilename($username)
    {
        return sys_get_temp_dir() . '/hugo_cache_' . md5($username) . '.json';
    }

    /**
     * @return array
     */
    public function accessTokenRequestWithCurl()
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $this->baseUrl . '/api/v1/partners/tokens',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
    "username":"' . $this->username . '",
    "password":"' . $this->password . '" 
}',
                CURLOPT_HTTPHEADER => array(
                    'Accept-Language: en',
                    'Content-Type: application/json'
                ),
            )
        );

        $response = curl_exec($curl);

        if ($error = curl_error($curl)) {
            throw new CantRetrieveAccessTokenException('Error retrieving the access token: ' . $error);
        }

        try {
            return json_decode($response, true);
        } catch (Exception $exception) {
            throw new CantRetrieveAccessTokenException('Error retrieving the access token');
        }
    }

    /**
     * @param string $username
     * @param string $getAccessTokenJsonResponse
     * @return void
     */
    private function cacheToken($username, $getAccessTokenJsonResponse)
    {
        file_put_contents($this->getAccessTokenCacheFilename($username), $getAccessTokenJsonResponse);
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws OrderRejectedException
     */
    public function acceptOrder(OrderInterface $order)
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => sprintf(
                    "%s/api/v1/partners/%s/orders/%s",
                    $this->baseUrl,
                    $this->partnerKey,
                    $order->getIdentifier()
                ),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => '{
    "status": "accepted"
}',
                CURLOPT_HTTPHEADER => array(
                    'Accept-Language: en'
                ),
            )
        );

        $response = curl_exec($curl);

        if ($response) {
            $json = json_decode($response, true);

            if (isset($json['errors'])) {
                throw new OrderRejectedException($json['errors'][0]['message'], $json['errors'][0]['code']);
            }
        }
    }

    public function getPartnerInfo()
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $this->baseUrl . '/api/v1/partners/' . $this->partnerKey,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $this->getDefaultHeadersForCurl(),
            ]
        );

        $response = curl_exec($curl);
        $partnerInfo = [];
        if ($response) {
            $partnerInfo = json_decode($response);
        }

        return $partnerInfo;
    }

    /**
     * @return array
     */
    private function getDefaultHeadersForCurl()
    {
        $headers = [];

        foreach ($this->getDefaultHeaders() as $header => $value) {
            $headers[] = sprintf('%s: %s', $header, $value);
        }

        return $headers;
    }

    public function updateProductById($productId)
    {
        $this->updateProduct($this->productRepository->findById($productId));
    }

    /**
     * @param ProductInterface $product
     * @return void
     * @throws GuzzleException
     */
    private function updateProduct(ProductInterface $product)
    {
        $this->publishProduct($product);
    }

    /**
     * @param ProductInterface $product
     * @return void
     * @throws GuzzleException
     */
    private function publishProduct(ProductInterface $product)
    {
        $body = [
            'products' => [
                $product->toArray(),
            ]
        ];

        $client = new Client();
        $options = [
            RequestOptions::HEADERS => $this->getDefaultHeaders(),
            RequestOptions::BODY => json_encode($body),
            RequestOptions::ALLOW_REDIRECTS => true,
        ];
        $response = $client->post(
            $this->baseUrl . "/api/v1/partners/$this->partnerKey/products",
            $options
        );
    }

    public function publishProductById($productId)
    {
        $this->publishProduct($this->productRepository->findById($productId));
    }

    public function removeProductById($productId)
    {
        $this->removeProduct($this->productRepository->findById($productId));
    }

    /**
     * @param ProductInterface $product
     * @return void
     * @throws GuzzleException
     */
    private function removeProduct(ProductInterface $product)
    {
        $client = new Client();
        $body = [
            'type' => 'deactivate',
            'productsSku' => [$product->getSku()],
        ];

        if (isset($this->locationId) && $this->locationId) {
            $body['locationsId'] = [$this->locationId];
        }

        $options = [
            RequestOptions::HEADERS => $this->getDefaultHeaders(),
            RequestOptions::BODY => json_encode($body)
        ];
        $response = $client->put($this->baseUrl . sprintf("/api/v1/partners/%s/products", $this->partnerKey), $options);
//        echo $response->getBody()->getContents();
//        exit;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'hugo';
    }
}
