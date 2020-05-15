<?php


class Twocheckout_Api_Redirect3DSecureController extends Mage_Core_Controller_Front_Action
{

    /**
     * @var Twocheckout_TwocheckoutApi
     */
    protected $_tcoApi;

    /**
     * Twocheckout_Api_Redirect3DSecureController constructor.
     * @param Zend_Controller_Request_Abstract  $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array                             $invokeArgs
     * @throws Mage_Core_Model_Store_Exception
     */
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = []
    ) {
        parent::__construct($request, $response, $invokeArgs);
        $storeId = Mage::app()->getStore()->getId();
        $this->_tcoApi = new Twocheckout_TwocheckoutApi(
            Mage::getStoreConfig('payment/twocheckout/seller_id', $storeId),
            Mage::getStoreConfig('payment/twocheckout/secret_key', $storeId)
        );
    }

    /**
     * redirected here after 3DS executes
     * @throws Mage_Core_Exception
     */
    public function successAction()
    {
        $refNo = $this->getRequest()->getParam('REFNO');
        if ($refNo) {
            $apiResponse = $this->_tcoApi->call('orders/' . $refNo, [], 'GET');
            if (isset($apiResponse['RefNo']) && isset($apiResponse['ExternalReference'])) {
                try {
                    $orderModel = Mage::getModel('sales/order');
                    $order = $orderModel->loadByIncrementId($apiResponse['ExternalReference']);
                    Mage::helper('twocheckout')->updateOrder($order, $apiResponse);
                } catch (Exception $e) {
                    Mage::throwException('Something went wrong!');
                }

                $this->_redirect('checkout/onepage/success');
            }
        }
    }

    /**
     * if the 3DSecure is canceled we redirect it to /cart
     * and restore the order with the cart items
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
            $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
            $quote->setIsActive(true)->save();
        }
        $this->_redirect('checkout/cart');
    }


    /**
     * redirects user to success/cancel or 3DS page
     * @return Zend_Controller_Response_Abstract
     */
    public function indexAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $url = $session->getData("twocheckout_3ds_redirect_url");
        $session->unsetData('twocheckout_3ds_redirect_url');

        return Mage::app()->getFrontController()->getResponse()->setRedirect($url);
    }
}
