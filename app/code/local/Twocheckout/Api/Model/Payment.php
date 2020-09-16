<?php


class Twocheckout_Api_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code               = 'twocheckout';
    protected $_canAuthorize       = true;
    protected $_canCapture         = true;
    protected $_canRefund          = true;
    protected $_canUseCheckout     = true;
    protected $_isInitializeNeeded = true;
    protected $_formBlockType      = 'twocheckout/form';
    protected $_infoBlockType      = 'twocheckout/info';

    /**
     * @var Twocheckout_TwocheckoutApi
     */
    protected $_tcoApi;

    /**
     * Twocheckout_Api_Model_Payment constructor.
     */
    public function __construct()
    {
        $this->_tcoApi = new Twocheckout_TwocheckoutApi($this->getSellerId(), $this->getSecretKey());
    }

    /**
     * @return mixed
     */
    public function getSellerId()
    {
        return Mage::getStoreConfig('payment/tco/sid');
    }

    /**
     * sets the tcoSellerId in js to be used with 2payJs
     */
    public function setJsVar()
    {
        echo '<script type="text/javascript"> let tcoSellerId = "' . $this->getSellerId() . '"</script>';
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return Mage::getStoreConfig('payment/tco/secret_key');
    }

    /**
     * @return mixed
     */
    public function getTestMode()
    {
        return Mage::getStoreConfig('payment/tco/demo');
    }

    /**
     * process order after submit, validate and build OBJECT for API call
     * we also treat the response form API
     * @param string $paymentAction
     * @param object $stateObject
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $token = Mage::app()->getRequest()->getParam('ess_token');
            $order = $this->getInfoInstance()->getOrder();
            $amount = round($order->getGrandTotal(), 2);
            $incrementId = $order->getIncrementId();
            $mageHelper = Mage::helper('paygate');
            $currency = $order->getBaseCurrencyCode();
            if (!Mage::helper('twocheckout')->canUseCurrency($currency)) {
                Mage::throwException($mageHelper->__('This currency is not available!'));
            }

            $orderParams = [
                'Currency'                  => $currency,
                'Language'                  => 'EN', // we dont have a language available in this shitty magento
                'Country'                   => $order->getBillingAddress()->getCountryId(),
                'CustomerIP'                => Mage::helper('core/http')->getRemoteAddr(),
                'Source'                    => 'MAGENTO1',
                'ExternalCustomerReference' => 'Ext_CR_' . $incrementId,
                'ExternalReference'         => $incrementId,
                'Items'                     => $this->getItem($incrementId, $amount, $currency),
                'BillingDetails'            => $this->getBillingAddress($order->getBillingAddress(), $order->getCustomerEmail()),
                'PaymentDetails'            => $this->getPaymentDetails($token, $currency),
            ];

            $apiResponse = $this->_tcoApi->call('orders', $orderParams);

            if (!$apiResponse) { // we dont get any response from 2co
                Mage::throwException($mageHelper->__('Your payment could not be processed! Please refresh the page and try again later'));
            }
            if (isset($apiResponse['error_code'])) {
                Mage::throwException($mageHelper->__('Unable to proceed. Please refresh the page try again.'));
            }
            if (isset($apiResponse['Errors']) && !empty($apiResponse['Errors'])) { // we get an response with ERRORS from 2co
                $errorMessage = '';
                foreach ($apiResponse['Errors'] as $key => $value) {
                    $search = "/<[\s\S]+?>/"; // remove html tags
                    $value = str_replace('<li>', PHP_EOL . ' - ', $value);
                    $error = <<<EOT
$value
EOT;
                    $error = str_replace('"', '\'', $error);
                    $errorMessage = preg_replace($search, PHP_EOL, $error);
                }
                Mage::throwException($errorMessage);
            }
            try {
                // first we check if we have to redirect to 3dSecure
                if (!$this->authorize3DS($apiResponse)) {
                    Mage::helper('twocheckout')->updateOrder($order, $apiResponse);
                }
            } catch (Exception $e) {
                Mage::throwException($mageHelper->__('Payment capturing error: Could not Authorize Transaction'));
            }
        } catch (Mage_Checkout_Exception $e) {
            Mage::throwException($mageHelper->__('Something went wrong, the order was not placed!'));
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('twocheckout/Redirect3DSecure');
    }

    /**
     * check CC has a 3DS redirect
     * @param $response
     * @return bool|string
     */
    private function authorize3DS($response)
    {
        $session = Mage::getSingleton('checkout/session');

        if (isset($response['PaymentDetails']['PaymentMethod']['Authorize3DS']) &&
            isset($response['PaymentDetails']['PaymentMethod']['Authorize3DS']['Href']) &&
            !empty($response['PaymentDetails']['PaymentMethod']['Authorize3DS']['Href'])) {
            $url = $response['PaymentDetails']['PaymentMethod']['Authorize3DS']['Href'] . '?avng8apitoken=' .
                $response['PaymentDetails']['PaymentMethod']['Authorize3DS']['Params']['avng8apitoken'];
            $session->setData("twocheckout_3ds_redirect_url", $url);

            return true;
        } else {
            if (in_array($response['ApproveStatus'], ['FRAUD', 'INVALIDDATA'])) {
                $session->setData("twocheckout_3ds_redirect_url", Mage::getUrl('checkout/onepage/failure'));
            } else {
                $session->setData("twocheckout_3ds_redirect_url", Mage::getUrl('checkout/onepage/success'));
            }

            return false;
        }
    }

    /**
     * @param $token
     * @param $currency
     * @return array
     */
    private function getPaymentDetails($token, $currency)
    {
        return [
            'Type'          => $this->getTestMode() == 1 ? 'TEST' : 'EES_TOKEN_PAYMENT',
            'Currency'      => $currency,
            'CustomerIP'    => Mage::helper('core/http')->getRemoteAddr(),
            'PaymentMethod' => [
                'EesToken'           => $token,
                'Vendor3DSReturnURL' => Mage::getUrl('twocheckout/Redirect3DSecure/success'),
                'Vendor3DSCancelURL' => Mage::getUrl('twocheckout/Redirect3DSecure/cancel')
            ]
        ];
    }

    /**
     * build the 2co format array for billingAddress
     * @param $billing
     * @param $email
     * @return array
     */
    private function getBillingAddress($billing, $email)
    {
        $address = [
            'Address1'    => $billing->getStreet(1),
            'City'        => $billing->getCity(),
            'CountryCode' => $billing->getCountryId(),
            'Email'       => $email,
            'FirstName'   => $billing->getFirstname(),
            'LastName'    => $billing->getLastname(),
            'Phone'       => $billing->getTelephone(),
            'State'       => $billing->getRegion(),
            'Zip'         => $billing->getPostcode(),
            'Company'     => $billing->getCompany()
        ];

        if ($billing->getStreet(2)) {
            $address['Address2'] = $billing->getStreet(2);
        }

        return $address;
    }

    /** we send only a ITEM as entire order with the cart_id
     * @param $id
     * @param $total
     * @param $currency
     * @return array
     */
    private function getItem($id, $total, $currency)
    {

        return [
            [
                'Code'         => null,
                'Quantity'     => 1,
                'Name'         => 'Cart_' . $id,
                'Description'  => 'N / A',
                'IsDynamic'    => true,
                'Tangible'     => false,
                'PurchaseType' => 'PRODUCT',
                'Price'        => [
                    'Amount'   => number_format($total, 2, '.', ''),
                    'Type'     => 'CUSTOM',
                    'Currency' => $currency
                ]
            ]
        ];
    }

}
