<?php

class Twocheckout_Tco_Model_Observer extends Mage_Core_Block_Abstract
{

    /**
     * @var string
     */
    private $apiUrl = 'https://api.2checkout.com/rest/6.0/';

    /**
     * @param \Varien_Object $payment
     *
     * @throws \Mage_Core_Exception
     */
    public function issue_creditmemo_refund(Varien_Object $payment)
    {
        $refund = Mage::getStoreConfig('payment/tco/refund');

        if ($refund == '1') {
            $order = $payment->getCreditmemo()->getOrder();
            $refNo = $order->getData('ext_order_id');
            $merchantId = Mage::getStoreConfig('payment/tco/sid');
            $secretKey = Mage::getStoreConfig('payment/tco/secret_key');
            /** @var $tcoHelper Twocheckout_Tco_Helper_Data */
            $tcoHelper = Mage::helper('tco');
            $headers = $tcoHelper->generateHeaders($merchantId, $secretKey);

            // while magento 1 does have a http client of its own
            // it seems to be very convoluted and error prone
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,
              sprintf('%sorders/%s/', $this->apiUrl, $refNo));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);

            $result = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Mage::throwException(Mage::helper('core')
                  ->__('Unable to get proper response from 2checkout API'));
            }

            if (!isset($result['NetDiscountedPrice']) || !isset($result['PaymentDetails']['Currency'])) {
                Mage::throwException(Mage::helper('core')
                  ->__('Required information missing from response'));
            }

            // check for different currency code
            if(strtolower($result['PaymentDetails']['Currency']) !== strtolower($order->getBaseCurrencyCode())) {
                Mage::throwException(Mage::helper('core')
                  ->__(sprintf('Order was place with currency "%s" while the currency in 2checkout is "%s". An online refund is not possible. Please contact customer support.',
                  $order->getBaseCurrencyCode(),
                  $result['PaymentDetails']['Currency']
                  )));
            }
            if ($result['NetDiscountedPrice'] != $order->getTotalRefunded()) {
                Mage::throwException(Mage::helper('core')
                  ->__(sprintf('Partial refunds are not supported: Amount able to be refunded from 2checkout is "%s" but you are trying to refund "%s"',
                    $result['NetDiscountedPrice'],
                    $order->getTotalRefunded())));
            }

            $args = [
              'amount' => (float)$result['NetDiscountedPrice'],
              'reason' => 'Other',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL,
              sprintf('%sorders/%s/refund/', $this->apiUrl, $refNo));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
            }
            if (isset($error_msg)) {
                Mage::throwException(Mage::helper('core')
                  ->__('Unable to get proper response from 2checkout API'));
            }

            if (true != $result) {
                Mage::throwException(Mage::helper('core')
                  ->__('There was an error when trying to refund the order on the 2checkout API. Please try again later.'));
            }

            $order->addStatusHistoryComment($result);
            $order->save();
        }

    }

    /**
     * @param \Varien_Object $observer
     *
     * @return $this
     */
    public function output_tco_redirect(Varien_Object $observer)
    {
        if (isset($_POST['payment']['method']) && $_POST['payment']['method'] == "tco") {
            $controller = $observer->getEvent()->getData('controller_action');
            $result = Mage::helper('core')->jsonDecode(
              $controller->getResponse()->getBody('default'),
              Zend_Json::TYPE_ARRAY
            );

            if (Mage::getStoreConfig('payment/tco/inline') == '1') {
                if (empty($result['error'])) {
                    $controller->loadLayout('checkout_onepage_review');
                    $html = $controller->getLayout()
                      ->createBlock('tco/inline')
                      ->toHtml();
                    $result['update_section'] = [
                      'name' => 'tcoiframe',
                      'html' => $html,
                    ];
                    $result['redirect'] = false;
                    $result['success'] = false;
                    $controller->getResponse()->clearHeader('Location');
                    $controller->getResponse()->setBody(Mage::helper('core')
                      ->jsonEncode($result));
                }
            } else {
                $js = '<script>
                    document.getElementById("review-please-wait").style["display"] = "block";
                    if ($$("a.top-link-cart").length) {
                        $$("a.top-link-cart")[0].href = "' . Mage::getUrl('tco/response',
                    ['_secure' => true]) . '";
                    }
                    if ($$("p.f-left").length !== 0) {
                        $$("p.f-left")[0].style["display"] = "none";
                    }
                    function formSubmit() {
                        $("tcosubmit").click();
                    }
                    var checkoutOrderBtn = $$("button.btn-checkout");
                    checkoutOrderBtn[0].removeAttribute("onclick");
                    checkoutOrderBtn[0].observe("click", formSubmit);
                    formSubmit();
                </script>';

                if (empty($result['error'])) {
                    $controller->loadLayout('checkout_onepage_review');
                    $html = $controller->getLayout()
                      ->createBlock('tco/redirect')
                      ->toHtml();
                    $html .= $js;
                    $result['update_section'] = [
                      'name' => 'tcoiframe',
                      'html' => $html,
                    ];
                    $result['redirect'] = false;
                    $result['success'] = false;
                    $controller->getResponse()->clearHeader('Location');
                    $controller->getResponse()->setBody(Mage::helper('core')
                      ->jsonEncode($result));
                }
            }

        }

        return $this;
    }

}
