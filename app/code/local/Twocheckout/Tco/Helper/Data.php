<?php

class Twocheckout_Tco_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @var array
     */
    private $_signParams = [
      'return-url',
      'return-type',
      'expiration',
      'order-ext-ref',
      'item-ext-ref',
      'lock',
      'cust-params',
      'customer-ref',
      'customer-ext-ref',
      'currency',
      'prod',
      'price',
      'qty',
      'tangible',
      'type',
      'opt',
      'coupon',
      'description',
      'recurrence',
      'duration',
      'renewal-price',
    ];

    /**
     * @param $params
     * @param $secretWord
     * @param bool $fromResponse
     *
     * @return string
     */
    public function generateSignature(
      $params,
      $secretWord,
      $fromResponse = false
    ) {

        if (!$fromResponse) {
            $signParams = array_filter($params, function ($k) {
                return in_array($k, $this->_signParams);
            }, ARRAY_FILTER_USE_KEY);
        } else {
            $signParams = $params;
            if (isset($signParams['signature'])) {
                unset($signParams['signature']);
            }
        }

        ksort($signParams); // order by key
        // Generate Hash
        $string = '';
        foreach ($signParams as $key => $value) {
            $string .= strlen($value) . $value;
        }

        return bin2hex(hash_hmac('sha256', $string, $secretWord, true));
    }

    /**
     * @param $array
     *
     * @return string
     */
    public function arrayExpand($array)
    {
        $retval = '';
        foreach ($array as $key => $value) {
            $size = strlen(stripslashes($value));
            $retval .= $size . stripslashes($value);
        }
        return $retval;
    }

    /**
     * @param $key
     * @param $data
     *
     * @return string
     */
    public function hmac($key, $data)
    {
        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }

        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }

    /**
     * @param $merchantId
     * @param $secretKey
     *
     * @return array
     */
    public function generateHeaders($merchantId, $secretKey)
    {
        $gmtDate = gmdate('Y-m-d H:i:s');
        $string = strlen($merchantId) . $merchantId . strlen($gmtDate) . $gmtDate;
        $hash = hash_hmac('md5', $string, $secretKey);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        $headers[] = sprintf('X-Avangate-Authentication: code="%s" date="%s" hash="%s"',
          $merchantId, $gmtDate, $hash);

        return $headers;
    }

    /**
     * @param $vendorCode
     * @param $secret
     * @param $requestDateTime
     *
     * @return string
     * @throws \Exception
     */
    public function generateHash($vendorCode, $secret, $requestDateTime)
    {
        return hash_hmac('md5',
          sprintf('%s%s%s%s', strlen($vendorCode), $vendorCode,
            strlen($requestDateTime), $requestDateTime), $secret);
    }

}
