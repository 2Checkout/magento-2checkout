<?php

class Twocheckout_Tco_ResponseController extends Mage_Core_Controller_Front_Action
{

    /**
     * @throws \Exception
     */
    public function indexAction()
    {
        $params = Mage::app()->getRequest()->getParams();
        if (empty($params)) {
            throw new Exception('Unable to finish order. Please contact a system administrator');
        }

        $signature = Mage::helper('tco')
          ->generateSignature(
            $params,
            Mage::getStoreConfig('payment/tco/secret_word'),
            true
          );
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($params['refno']);
        Mage::getSingleton('checkout/session')
          ->getQuote()
          ->setIsActive(false)
          ->save();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());

        if ($signature == $params['signature']) {
            $order->sendNewOrderEmail();
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)
              ->save();
            // magento 1 has some specific keys it uses for external orders
            // you can add some keys yourself but it's easier to just use
            // the ones that are already available
            $order->setData('ext_order_id', $params['refno']);
        } else {
            $order->addStatusHistoryComment(number_format($order->getGrandTotal(),
              2, '.', ''));
            $order->addStatusHistoryComment('Hash did not match, check secret word.');
        }

        $this->_redirect('checkout/onepage/success');
        $order->save();
    }

}
