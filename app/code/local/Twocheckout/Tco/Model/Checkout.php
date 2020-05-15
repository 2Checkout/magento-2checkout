<?php

class Twocheckout_Tco_Model_Checkout extends Mage_Payment_Model_Method_Abstract
{

    /**
     * {@inheritDoc}
     */
    protected $_canAuthorize = true;

    /**
     * {@inheritDoc}
     */
    protected $_canRefund = true;

    /**
     * {@inheritDoc}
     */
    protected $_canCapture = true;

    /**
     * {@inheritDoc}
     */
    protected $_code = 'tco';

    /**
     * @var string
     */
    protected $_paymentMethod = 'shared';

    /**
     * @var Mage_Checkout_Model_Session
     */
    private $_session;

    /**
     * @var \Mage_Sales_Model_Order
     */
    private $_saleOrder;

    /**
     * @var string
     */
    private $_redirectUrl;

    /**
     * @var
     */
    private $_quote;

    /**
     * @return \Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        if(isset($this->_session)) {
            return $this->_session;
        }

        return Mage::getSingleton('checkout/session');
    }

    /**
     * @param \Mage_Checkout_Model_Session $session
     */
    public function setCheckout(Mage_Checkout_Model_Session $session)
    {
        $this->_session = $session;
    }

    /**
     * @return \Mage_Core_Helper_Abstract|\Twocheckout_Tco_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('tco');
    }

    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        if(isset($this->_redirectUrl)) {
            return $this->_redirectUrl;
        }

        return Mage::getUrl('tco/response');
    }

    /**
     * @param $redirectUrl
     */
    public function setOrderPlaceRedirectUrl($redirectUrl)
    {
        $this->_redirectUrl = $redirectUrl;
    }

    /**
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->getConfigData('sid');
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return 'https://secure.2checkout.com/checkout/buy/?';
    }

    /**
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function getQuote()
    {
        if(isset($this->_quote)) {
            return $this->_quote;
        }

        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        return $this->getSaleOrder()
          ->loadByIncrementId($orderIncrementId);
    }

    /**
     * @param $quote
     */
    public function setQuote($quote)
    {
        $this->_quote = $quote;
    }

    /**
     * @param $saleOrder
     */
    public function setSaleOrder($saleOrder)
    {
        $this->_saleOrder = $saleOrder;
    }

    /**
     * @return false|\Mage_Core_Model_Abstract|\Mage_Sales_Model_Order
     */
    public function getSaleOrder()
    {
        if(isset($this->_saleOrder)) {
            return $this->_saleOrder;
        }

        return Mage::getModel('sales/order');
    }

    /**
     * @return float
     */
    public function checkTotal()
    {
        $items = $this->getQuote()->getAllItems();
        $orderId = $this->getCheckout()->getLastRealOrderId();
        /**
         * @var $order Mage_Sales_Model_Order
         */
        $order = $this->getSaleOrder()->loadByIncrementId($orderId);
        $taxFull = $order->getFullTaxInfo();
        $shipMethod = $order->getShipping_description();
        $coupon = $order->getCoupon_code();
        $lineItemTotal = 0;
        //get products
        if ($items) {
            foreach ($items as $item) {
                /** @var $item \Mage_Sales_Model_Order_Item */
                if ($item->getParentItem()) {
                    continue;
                }

                $lineItemTotal += floatval(number_format($item->getPrice() * $item->getQtyOrdered(), 2, '.', ''));
            }
        }

        //get taxes
        if ($taxFull) {
            foreach ($taxFull as $rate) {
                $lineItemTotal += round($rate['amount'], 2);
            }
        }
        //get shipping
        if ($shipMethod) {
            $lineItemTotal += round($order->getShippingAmount(), 2);
        }
        //get coupons
        if ($coupon) {
            $lineItemTotal -= trim(round($order->getBase_discount_amount(), 2),
              '-');
        }
        return floatval($lineItemTotal);
    }

    /**
     * @return array
     */
    public function getInlineFormFields()
    {
        $orderId = $this->getCheckout()->getLastRealOrderId();

        /** @var $order Mage_Sales_Model_Order */
        $order = $this->getSaleOrder()->loadByIncrementId($orderId);
        $billingAddress = $order->getBillingAddress();
        $inlineLinkParams = [];

        $billingAddressData = [
          'name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
          'phone' => $billingAddress->getTelephone(),
          'country' => $billingAddress->getCountry(),
          'state' => $billingAddress->getRegion(),
          'email' => $order->getData('customer_email'),
          'address' => $billingAddress->getStreet1(),
          'address2' => !empty($billingAddress->getStreet2()) ? $billingAddress->getStreet2() : '',
          'city' => $billingAddress->getCity(),
          'zip' => $billingAddress->getPostcode(),
        ];

        $inlineLinkParams['billing_address'] = json_encode($billingAddressData);

        $shippingAddressData = [
          'ship-name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
          'ship-country' =>  $billingAddress->getCountry(),
          'ship-state' => $billingAddress->getRegion(),
          'ship-city' => $billingAddress->getCity(),
          'ship-email' => $order->getData('customer_email'),
          'ship-address' =>  $billingAddress->getStreet1(),
          'ship-address2' => !empty($billingAddress->getStreet2()) ? $billingAddress->getStreet2() : '',
        ];

        $inlineLinkParams['shipping_address'] = json_encode($shippingAddressData);

        $productData[] = [
          'type' => 'PRODUCT',
          'name' => 'Cart_' . $orderId,
          'price' => $this->checkTotal(),
          'tangible' => 0,
          'qty' => 1,
        ];

        $inlineLinkParams['products'] = json_encode($productData);
        $inlineLinkParams['currency'] = strtolower($order->getOrderCurrencyCode());
        $inlineLinkParams['language'] = substr(Mage::app()
          ->getLocale()
          ->getLocaleCode(), 0, 2);

        $inlineLinkParams['url_data'] = json_encode([
          'type' => 'redirect',
          'url' => Mage::getUrl('tco/response'),
        ]);
        $inlineLinkParams['test'] = ($this->getConfigData('demo') == '1') ? '1' : '0';
        $inlineLinkParams['order-ext-ref'] = $orderId;
        $inlineLinkParams['return-url'] = $this->getOrderPlaceRedirectUrl();
        $inlineLinkParams['customer-ext-ref'] = $order->getData('customer_email');
        $inlineLinkParams['src'] = 'MAGENTO1';
        $inlineLinkParams['mode'] = 'DYNAMIC';

        return $inlineLinkParams;
    }


    /**
     * @return array
     */
    public function getFormFields()
    {
        $orderId = $this->getCheckout()->getLastRealOrderId();
        /** @var $order Mage_Sales_Model_Order */
        $order = $this->getSaleOrder()->loadByIncrementId($orderId);
        $billingAddress = $order->getBillingAddress();
        $buyLinkParams = [];

        $buyLinkParams['name'] = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $buyLinkParams['phone'] = $billingAddress->getTelephone();
        $buyLinkParams['country'] = $billingAddress->getCountry();
        $buyLinkParams['state'] = $billingAddress->getRegion();
        $buyLinkParams['email'] = $order->getData('customer_email');
        $buyLinkParams['address'] = $billingAddress->getStreet1();
        if (!empty($billingAddress->getStreet2())) {
            $buyLinkParams['address2'] = $billingAddress->getStreet2();
        }
        $buyLinkParams['city'] = $billingAddress->getCity();

        $buyLinkParams['ship-name'] = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $buyLinkParams['ship-country'] = $billingAddress->getCountry();
        $buyLinkParams['ship-state'] = $billingAddress->getRegion();
        $buyLinkParams['ship-city'] = $billingAddress->getCity();
        $buyLinkParams['ship-email'] = $order->getData('customer_email');
        $buyLinkParams['ship-address'] = $billingAddress->getStreet1();
        $buyLinkParams['ship-address2'] = !empty($billingAddress->getStreet2()) ? $billingAddress->getStreet2() : '';

        $buyLinkParams['zip'] = $billingAddress->getPostcode();

        $buyLinkParams['prod'] = 'Cart_' . $orderId;
        $buyLinkParams['price'] = $this->checkTotal();
        $buyLinkParams['qty'] = 1;
        $buyLinkParams['type'] = 'PRODUCT';
        $buyLinkParams['tangible'] = 0;
        $buyLinkParams['src'] = 'MAGENTO1';

        $buyLinkParams['return-url'] = $this->getOrderPlaceRedirectUrl();
        // url NEEDS a protocol(http or https)
        $buyLinkParams['return-type'] = 'redirect';
        $buyLinkParams['expiration'] = time() + (3600 * 5);
        $buyLinkParams['order-ext-ref'] = $orderId;
        $buyLinkParams['item-ext-ref'] = date('YmdHis');
        $buyLinkParams['customer-ext-ref'] = $order->getData('customer_email');
        $buyLinkParams['currency'] = strtolower($order->getOrderCurrencyCode());
        $buyLinkParams['language'] = substr(Mage::app()
          ->getLocale()
          ->getLocaleCode(), 0, 2);
        $buyLinkParams['test'] = ($this->getConfigData('demo') == '1') ? '1' : '0';
        // sid in this case is the merchant code
        $buyLinkParams['merchant'] = $this->getMerchantId();
        $buyLinkParams['dynamic'] = 1;
        $buyLinkParams['signature'] = $this->getHelper()->generateSignature(
          $buyLinkParams,
          $this->getConfigData('secret_word')
        );

        return $buyLinkParams;
    }
}
