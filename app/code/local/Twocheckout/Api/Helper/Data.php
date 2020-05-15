<?php


class Twocheckout_Api_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var array
     */
    protected $_supportedCurrencyCodes = [
        'AFN',
        'ALL',
        'DZD',
        'ARS',
        'AUD',
        'AZN',
        'BSD',
        'BDT',
        'BBD',
        'BZD',
        'BMD',
        'BOB',
        'BWP',
        'BRL',
        'GBP',
        'BND',
        'BGN',
        'CAD',
        'CLP',
        'CNY',
        'COP',
        'CRC',
        'HRK',
        'CZK',
        'DKK',
        'DOP',
        'XCD',
        'EGP',
        'EUR',
        'FJD',
        'GTQ',
        'HKD',
        'HNL',
        'HUF',
        'INR',
        'IDR',
        'ILS',
        'JMD',
        'JPY',
        'KZT',
        'KES',
        'LAK',
        'MMK',
        'LBP',
        'LRD',
        'MOP',
        'MYR',
        'MVR',
        'MRO',
        'MUR',
        'MXN',
        'MAD',
        'NPR',
        'TWD',
        'NZD',
        'NIO',
        'NOK',
        'PKR',
        'PGK',
        'PEN',
        'PHP',
        'PLN',
        'QAR',
        'RON',
        'RUB',
        'WST',
        'SAR',
        'SCR',
        'SGF',
        'SBD',
        'ZAR',
        'KRW',
        'LKR',
        'SEK',
        'CHF',
        'SYP',
        'THB',
        'TOP',
        'TTD',
        'TRY',
        'UAH',
        'AED',
        'USD',
        'VUV',
        'VND',
        'XOF',
        'YER'
    ];

    /**
     * array of 2CO allowed currency
     * @param $currency
     * @return bool
     */
    public function canUseCurrency($currency)
    {

        return in_array(strtoupper($currency), $this->_supportedCurrencyCodes);
    }

    /**
     * @return array
     */
    public function getSupportedCurrency()
    {
        return $this->_supportedCurrencyCodes;
    }

    /**
     * updates an order
     * @param $order
     * @param $apiResponse
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function updateOrder($order, $apiResponse)
    {
        if (in_array($apiResponse['ApproveStatus'], ['FRAUD', 'INVALIDDATA'])) {
            $order->setState(
                Mage_Sales_Model_Order::STATE_CANCELED,
                Mage_Sales_Model_Order::STATUS_FRAUD,
                '2checkout automatic fraud mechanism flag raised!',
                true // send email to customer???
            )->cancel();
        } else {
            $order->setData('ext_order_id', $apiResponse['RefNo'])
                  ->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)
                  ->setIsInProcess(true);
        }
        $order->setStoreId(Mage::app()->getStore()->getStoreId());
        $order->save();

        return $order;
    }


}
