<?php

class Twocheckout_Tco_NotificationController extends Mage_Core_Controller_Front_Action
{

    // NEVER catch exceptions in this file
    // they're meant to kill the process
    // if they are caught please rethrow them

    /**
     * Ipn Constants
     *
     * Not all are used, however they should be left here
     * for future reference
     */
    const ORDER_CREATED = 'ORDER_CREATED';
    const FRAUD_STATUS_CHANGED = 'FRAUD_STATUS_CHANGED';
    const INVOICE_STATUS_CHANGED = 'INVOICE_STATUS_CHANGED';
    const REFUND_ISSUED = 'REFUND_ISSUED';
    //Order Status Values:
    const ORDER_STATUS_PENDING = 'PENDING';
    const ORDER_STATUS_PAYMENT_AUTHORIZED = 'PAYMENT_AUTHORIZED';
    const ORDER_STATUS_SUSPECT = 'SUSPECT';
    const ORDER_STATUS_INVALID = 'INVALID';
    const ORDER_STATUS_COMPLETE = 'COMPLETE';
    const ORDER_STATUS_REFUND = 'REFUND';
    const ORDER_STATUS_REVERSED = 'REVERSED';
    const ORDER_STATUS_PURCHASE_PENDING = 'PURCHASE_PENDING';
    const ORDER_STATUS_PAYMENT_RECEIVED = 'PAYMENT_RECEIVED';
    const ORDER_STATUS_CANCELED = 'CANCELED';
    const ORDER_STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    const FRAUD_STATUS_APPROVED = 'APPROVED';
    const FRAUD_STATUS_DENIED = 'DENIED';
    const FRAUD_STATUS_REVIEW = 'UNDER REVIEW';
    const FRAUD_STATUS_PENDING = 'PENDING';
    const PAYMENT_METHOD = 'tco_checkout';

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * @var Twocheckout_Tco_Helper_Data
     */
    protected $_tcoHelper;

    /**
     * @var string
     */
    protected $_secretKey;

    /**
     * Twocheckout_Tco_NotificationController constructor.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param \Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs
     */
    public function __construct(
      Zend_Controller_Request_Abstract $request,
      Zend_Controller_Response_Abstract $response,
      array $invokeArgs = []
    ) {
        parent::__construct($request, $response, $invokeArgs);

        $this->_tcoHelper = Mage::helper('tco');
        $this->_secretKey = Mage::getStoreConfig('payment/tco/secret_key');
    }

    /**
     * @return string|void
     * @throws \Mage_Core_Exception
     */
    public function indexAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        // magento 1 doesn't clean up the request at all
        // so this MAY be a security issue
        $params = $this->getRequest()->getParams();

        if (!isset($params['REFNOEXT']) && (!isset($params['REFNO']) && empty($params['REFNO']))) {
            throw new Exception(sprintf('Cannot identify order: "%s".',
              $params['REFNOEXT']));
        }

        if (!$this->isIpnResponseValid($params, $this->_secretKey)) {
            throw new Exception(sprintf('MD5 hash mismatch for 2Checkout IPN with date: "%s".',
              $params['IPN_DATE']));
        }

        $this->_order = Mage::getModel('sales/order')
          ->loadByIncrementId($params['REFNOEXT']);

        if (!$this->_order instanceof Mage_Sales_Model_Order) {
            throw new Exception(sprintf('Unable to load order with orderId %s. IPN failed.',
              $params['REFNOEXT']));
        }

        // do not wrap this in a try catch
        // it's intentionally left out so that the exceptions will bubble up
        // and kill the script if one should arise
        $this->_processFraud($params);

        if ($this->_isNotFraud($params)) {
            $this->_processOrderStatus($params);
        }

        // no need to return the response here
        // it will bubble up to the calling function
        // if new headers are added make sure to add the 3rd param
        // as true in order to replace the previous header
        // it will get replaced by default either way, but it's better to make sure
        $this->getResponse()
          ->setHeader('HTTP/1.0', 200, true)
          ->setHeader('Content-Type', 'text/html', true)
          ->setBody($this->_calculateIpnResponse(
            $params,
            $this->_secretKey
          ));
    }

    /**
     * @param $params
     *
     * @return bool
     */
    protected function _isNotFraud($params)
    {
        return (isset($params['FRAUD_STATUS']) && trim($params['FRAUD_STATUS']) === self::FRAUD_STATUS_APPROVED);
    }

    /**
     * @param $params
     * @param $secretKey
     *
     * @return bool
     */
    public function isIpnResponseValid($params, $secretKey)
    {
        $result = '';
        $receivedHash = $params['HASH'];
        foreach ($params as $key => $val) {

            if ($key != "HASH") {
                if (is_array($val)) {
                    $result .= $this->_tcoHelper->arrayExpand($val);
                } else {
                    $size = strlen(stripslashes($val));
                    $result .= $size . stripslashes($val);
                }
            }
        }

        if (isset($params['REFNO']) && !empty($params['REFNO'])) {
            $calcHash = $this->_tcoHelper->hmac($secretKey, $result);
            if ($receivedHash === $calcHash) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $ipn_params
     * @param $secret_key
     *
     * @return string
     */
    private function _calculateIpnResponse($ipn_params, $secret_key)
    {
        $resultResponse = '';
        $ipnParamsResponse = [];
        // we're assuming that these always exist, if they don't then the problem is on avangate side
        $ipnParamsResponse['IPN_PID'][0] = $ipn_params['IPN_PID'][0];
        $ipnParamsResponse['IPN_PNAME'][0] = $ipn_params['IPN_PNAME'][0];
        $ipnParamsResponse['IPN_DATE'] = $ipn_params['IPN_DATE'];
        $ipnParamsResponse['DATE'] = date('YmdHis');

        foreach ($ipnParamsResponse as $key => $val) {
            $resultResponse .= $this->_tcoHelper->arrayExpand((array)$val);
        }

        return sprintf(
          '<EPAYMENT>%s|%s</EPAYMENT>',
          $ipnParamsResponse['DATE'],
          $this->_tcoHelper->hmac($secret_key, $resultResponse)
        );
    }


    /**
     * @param $params
     *
     * @throws \Mage_Core_Exception
     */
    private function _processOrderStatus($params)
    {
        $orderStatus = $params['ORDERSTATUS'];
        if (!empty($orderStatus)) {
            switch (trim($orderStatus)) {
                case self::ORDER_STATUS_PENDING:
                    $this
                      ->_order
                      ->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                        true)
                      ->addStatusHistoryComment(sprintf('IPN ORDERSTATUS : "%s". Order placed, waiting bank authorization.',
                        "PENDING"));
                    break;

                case self::ORDER_STATUS_INVALID:
                    $this
                      ->_order
                      ->setState(Mage_Sales_Model_Order::STATE_HOLDED, Mage_Sales_Model_Order::STATE_HOLDED)
                      ->addStatusHistoryComment(sprintf('IPN ORDERSTATUS : "%s". Order is currently on hold due to invalid data. Please contact 2Checkout support.',
                        "INVALID "));
                    break;

                case self::ORDER_STATUS_PURCHASE_PENDING:
                    $this
                      ->_order
                      ->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)
                      ->addStatusHistoryComment(sprintf(
                        'IPN ORDERSTATUS : "%s". 2Checkout is waiting for the customer to make the payment.',
                        "PURCHASE_PENDING"
                      ));
                    break;

                case self::ORDER_STATUS_PENDING_APPROVAL:
                    $this
                      ->_order
                      ->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)
                      ->addStatusHistoryComment(sprintf('IPN ORDERSTATUS : "%s". 2Checkout has yet to approve this order.',
                        "PENDING_APPROVAL"));
                    break;

                case self::ORDER_STATUS_PAYMENT_AUTHORIZED:
                    $this
                      ->_order
                      ->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)
                      ->addStatusHistoryComment(sprintf('IPN ORDERSTATUS : "%s". The bank authorized the payment.',
                        "PAYMENT_AUTHORIZED"));
                    break;

                case self::ORDER_STATUS_COMPLETE:
                    if (Mage::getStoreConfig('payment/tco/invoice_on_order') == '1') {
                        $this->_processInvoice($params);
                    }

                    // if all products are virtual then mark the order as complete
                    // since the admin is unable to ship it, he's also unable to
                    // mark the order in the appropiate status
                    if($this->_areAllProductsVirtual()) {
                        $this->_order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true)
                          ->addStatusHistoryComment(sprintf('IPN ORDERSTATUS : "%s". 2Checkout marked the order as complete. The order contains only virtual products so it was marked as complete.',
                            "COMPLETE"));
                    } else {
                        $this
                          ->_order
                          ->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)
                          ->addStatusHistoryComment(sprintf('IPN ORDERSTATUS : "%s". 2Checkout marked the order as complete. You may issue an invoice if one was not already issued.',
                            "COMPLETE"));
                    }
                    break;

                case self::ORDER_STATUS_REFUND:
                    $this
                      ->_order
                      ->addStatusHistoryComment(sprintf('IPN ORDERSTATUS: "%s". 2Checkout marked the order as refunded. The status of the order will have to be changed manually.',
                        "REFUNDED"));
                    break;

                default:
                    throw new Exception('Cannot handle Ipn message type for message');
            }

            $this->_order->save();
        }
    }

    /**
     * @return bool
     */
    private function _areAllProductsVirtual()
    {
        // if any of the products bought is not virtual
        // then the admin can ship them and properly change the order status
        // in case they are we must mark the order as complete
        foreach ($this->_order->getAllItems() as $item) {
            if (!$item->getProduct()->isVirtual()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $params
     *
     * @throws \Mage_Core_Exception
     */
    private function _processInvoice($params)
    {
        // ALWAYS rethrow the exception, the process must be killed
        try {
            if ($this->_order->hasInvoices()) {
                // merchant already issued an invoice
                // nothing to do here since we need to associate
                // a transaction with the invoice in order
                // to be able to do a refund
                // NOTE: this will still mark the order in the appropriate status
                return;
            }

            if (!isset($params['REFNO'])) {
                Mage::throwException(Mage::helper('core')
                  ->__('Required parameter missing from 2checkout response, aborting!'));
            }

            if (!$this->_order->canInvoice()) {
                Mage::throwException(Mage::helper('core')
                  ->__('Cannot create an invoice.'));
            }

            $invoice = $this->_order->prepareInvoice();
            if (!$invoice->getTotalQty()) {
                Mage::throwException(Mage::helper('core')
                  ->__('Cannot create an invoice without products.'));
            }
            $payment = $this->_order->getPayment();
            $payment->setTransactionId($params['REFNO']);
            $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
            $transaction->setIsTransactionClosed(true);
            $transaction->save();

            $this->_order->getPayment()->setSkipTransactionCreation(false);
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register()->capture();
            Mage::getModel('core/resource_transaction')
              ->addObject($invoice)
              ->addObject($this->_order)
              ->save();
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());
            // rethrow exception and kill process
            throw $e;
        }
    }

    /**
     * @param $params
     *
     * @throws \Mage_Core_Exception
     */
    private function _processFraud($params)
    {

        if (isset($params['FRAUD_STATUS'])) {
            switch (trim($params['FRAUD_STATUS'])) {
                case self::FRAUD_STATUS_DENIED:
                    $this->_order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, Mage_Sales_Model_Order::STATUS_FRAUD);
                    $this->_order->addStatusHistoryComment(sprintf('IPN ORDERSTATUS : "%s". Payment is under suspicion of fraud!',
                      "DENIED"));
                    break;

                case self::FRAUD_STATUS_APPROVED:
                    if (Mage::getStoreConfig('payment/tco/invoice_on_fraud') == '1') {
                        $this->_processInvoice($params);
                    }

                    $this
                      ->_order
                      ->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                        true)
                      ->addStatusHistoryComment(sprintf('IPN ORDERSTATUS : "%s". Payment passed fraud review',
                        "PROCESSING"));
                    break;
            }

            $this->_order->save();
        }
    }

}
