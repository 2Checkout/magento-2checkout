<?php

class Twocheckout_TwocheckoutApi
{
    public $sellerId;
    public $secretKey;
    const   API_URL = 'https://api.2checkout.com/rest/';
    const   API_VERSION = '6.0';

    public function __construct($sellerId = null, $secretKey = null)
    {

        $this->secretKey = $secretKey;
        $this->sellerId = $sellerId;
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @return string
     */
    public function getSellerId()
    {
        return $this->sellerId;
    }

    /**
     * @param $secretKey
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @param $sellerId
     */
    public function setSellerId($sellerId)
    {
        $this->sellerId = $sellerId;
    }

    /**
     *  sets the header with the auth has and params
     * @return array
     * @throws Exception
     */
    private function getHeaders()
    {
        if (!$this->sellerId || !$this->secretKey) {
            Mage::throwException('Merchandiser needs a valid 2Checkout SellerId and SecretKey to authenticate!');
        }
        $gmtDate = gmdate('Y-m-d H:i:s');
        $string = strlen($this->sellerId) . $this->sellerId . strlen($gmtDate) . $gmtDate;
        $hash = hash_hmac('md5', $string, $this->secretKey);

        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        $headers[] = 'X-Avangate-Authentication: code="' . $this->sellerId . '" date="' . $gmtDate . '" hash="' . $hash . '"';;

        return $headers;
    }

    /**
     * @param        $endpoint
     * @param        $params
     * @param string $method
     * @return bool|mixed
     * @throws Mage_Core_Exception
     */
    public function call($endpoint, $params, $method = 'POST')
    {
        // if endpoint does not starts or end with a '/' we add it, as the API needs it
        if ($endpoint[0] !== '/') {
            $endpoint = '/' . $endpoint;
        }
        if ($endpoint[-1] !== '/') {
            $endpoint = $endpoint . '/';
        }

        $headers = $this->getHeaders();
        try {
            $url = self::API_URL . self::API_VERSION . $endpoint;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
            }
            $response = curl_exec($ch);

            if ($response === false) {
                exit(curl_error($ch));
            }
            curl_close($ch);

            return json_decode($response, true);
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());

            return false;
        }
    }
}
