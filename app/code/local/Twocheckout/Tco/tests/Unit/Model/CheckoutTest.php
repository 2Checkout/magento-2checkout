<?php
// this file will simply require all the stuff needed for
// magento to load all of its file properly
require_once dirname(__DIR__) . '../../bootstrap.php';

use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{

    private $requiredFormFieldBuyLinkKeys = [
      'name',
      'phone',
      'country',
      'state',
      'email',
      'address',
      'city',
      'ship-name',
      'ship-country',
      'ship-state',
      'ship-city',
      'ship-email',
      'ship-address',
      'zip',
      'prod',
      'price',
      'qty',
      'type',
      'tangible',
      'src',
      'return-url',
      'return-type',
      'expiration',
      'order-ext-ref',
      'item-ext-ref',
      'customer-ext-ref',
      'currency',
      'language',
      'test',
      'merchant',
      'dynamic',
      'phone',
      'signature',
    ];

    private $street2Fields = [
      'ship-address2',
      'address2',
    ];

    private $testClass;

    public function setUp(): void
    {
        $this->testClass = new Twocheckout_Tco_Model_Checkout();
        parent::setUp();
    }

    public function testgetInlineFormFields()
    {
        $this->testClass = new Twocheckout_Tco_Model_Checkout();
        $this->testClass->setCheckout($this->getSessionMock());
        // this can be any url it just has to be valid
        $this->testClass->setOrderPlaceRedirectUrl('http://magento1.local/tco/response/');

        $saleOrderMock = $this->getSaleOrderMock(
          $this->getSaleOrderMockWithIncrementId(
            $this->getBillingAddressMock()
          )
        );

        $this->testClass->setSaleOrder($saleOrderMock);
        $this->testClass->setQuote($saleOrderMock);
        $formFields = $this->testClass->getInlineFormFields();

        $this->assertJson(
          '{"name":"John Doe","phone":756852919,"country":"US","state":"Alabama","email":"test@test.com","address":"Alabama US","city":"Alabama","zip":null}',
          $formFields['billing_address']
          );

        $this->assertEquals(8, count(json_decode($formFields['billing_address'], true)));

        $this->assertJson(
          '{"ship-name":"John Doe","ship-country":"US","phone": "123456789","ship-state":"Alabama","ship-city":"Alabama","ship-email":"test@test.com","ship-address":"Alabama US","ship-address2":""}',
          $formFields['shipping_address']
        );

        $this->assertEquals(7, count(json_decode($formFields['shipping_address'], true)));

        $this->assertJson(
          '[{"type":"PRODUCT","name":"Cart_145000185","price":80,"tangible":0,"qty":1}]',
          $formFields['products']
        );

        $decodedProducts = json_decode($formFields['products'], true);
        $decodedProducts = $decodedProducts[0];

        $this->assertEquals(5, count($decodedProducts));

        $this->assertJson(
          '{"type":"redirect","url":"http:\/\/magento1.local\/tco\/response\/"}',
          $formFields['url_data']
        );

        $products = json_decode($formFields['products'], true);

        foreach($products as $product) {
            // assert if fields are of equal value but not necessarily type
            // all the bellow fields should ALWAYS have these values
            $this->assertEquals('PRODUCT', $product['type']);
            $this->assertStringStartsWith('Cart_', $product['name']);
            $this->assertEquals(false, $product['tangible']);
            $this->assertEquals(1, $product['qty']);
        }

        $this->assertEquals('MAGENTO1', $formFields['src']);
        $this->assertEquals('DYNAMIC', $formFields['mode']);
    }

    public function testgetFormFields()
    {
        $this->testClass = new Twocheckout_Tco_Model_Checkout();
        $this->testClass->setCheckout($this->getSessionMock());
        // this can be any url it just has to be valid
        $this->testClass->setOrderPlaceRedirectUrl('http://magento1.local/tco/response/');

        $saleOrderMock = $this->getSaleOrderMock(
          $this->getSaleOrderMockWithIncrementId(
            $this->getBillingAddressMock()
          )
        );

        $this->testClass->setSaleOrder($saleOrderMock);
        $this->testClass->setQuote($saleOrderMock);
        $formFields = $this->testClass->getFormFields();

        $this->assertCount(33, $formFields);

        // check if the required keys exist in the array
        foreach ($this->requiredFormFieldBuyLinkKeys as $requiredFormFieldBuyLinkKey) {
            $this->assertArrayHasKey($requiredFormFieldBuyLinkKey, $formFields);
        }
        $this->assertStringStartsWith('Cart_', $formFields['prod']);
        $this->assertIsFloat($formFields['price']);

        // assert if fields are of equal value but not necessarily type
        // all the bellow fields should ALWAYS have these values
        $this->assertEquals(1, $formFields['qty']);
        $this->assertEquals(false, $formFields['tangible']);
        $this->assertEquals(true, $formFields['dynamic']);

        // assert if fields are of equal value and type
        // all the bellow fields should ALWAYS have these values
        $this->assertSame('PRODUCT', $formFields['type']);
        $this->assertSame('MAGENTO1', $formFields['src']);
        $this->assertSame('redirect', $formFields['return-type']);

        // language should be exactly 2 characters long
        $this->assertSame(2, strlen($formFields['language']));
        $this->contains(['1', '0'], ($formFields['test']));

        // signature should always be the same length for sha256
        // we're not interested in the signature itself since that
        // is validated in another test
        $this->assertEquals(strlen('701ea766ad2abafc502392d41342b882f3d880372b30cd7b728c734f9a4d98bf'),
          strlen($formFields['signature']));

        // with street 2
        $saleOrderMock = $this->getSaleOrderMock(
          $this->getSaleOrderMockWithIncrementId(
            $this->getBillingAddressMock(true)
          )
        );

        $this->testClass->setSaleOrder($saleOrderMock);
        $this->testClass->setQuote($saleOrderMock);
        $formFields = $this->testClass->getFormFields();

        // 2 extra fields here
        $this->assertCount(34, $formFields);
        foreach (array_merge($this->requiredFormFieldBuyLinkKeys, $this->street2Fields) as $requiredFormFieldBuyLinkKey) {
            $this->assertArrayHasKey($requiredFormFieldBuyLinkKey, $formFields);
        }
    }

    private function getSessionMock()
    {
        $sessionMock = $this->getMockBuilder(Mage_Checkout_Model_Session::class)
          ->setMethods([
            'getLastRealOrderId',
          ])
          ->disableOriginalConstructor()
          ->disableOriginalClone()
          ->disableArgumentCloning()
          ->disallowMockingUnknownTypes()
          ->getMock();

        $sessionMock
          ->expects($this->any())
          ->method('getLastRealOrderId')
          ->willReturn(145000185);

        return $sessionMock;
    }

    private function getSaleOrderMock($saleOrderLoadByIncrementIdMock)
    {
        $saleOrderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
          ->disableOriginalConstructor()
          ->disableOriginalClone()
          ->disableArgumentCloning()
          ->disallowMockingUnknownTypes()
          ->getMock();

        $saleOrderMock->expects($this->any())
          ->method('loadByIncrementId')
          ->willReturn($saleOrderLoadByIncrementIdMock);

        return $saleOrderMock;

    }

    private function getBillingAddressMock($withStreet2 = false)
    {
        $billingAddress = $this->getMockBuilder(Mage_Sales_Model_Order_Address::class)
          ->disableOriginalConstructor()
          ->disableOriginalClone()
          ->disableArgumentCloning()
          ->disallowMockingUnknownTypes()
          ->setMethods([
            'getFirstName',
            'getLastname',
            'getTelephone',
            'getCountry',
            'getRegion',
            'getStreet1',
            'getCity',
            'getStreet2',
          ])
          ->getMock();

        $billingAddress
          ->expects($this->exactly(2))
          ->method('getFirstName')
          ->willReturn('John');

        $billingAddress
          ->expects($this->any())
          ->method('getLastname')
          ->willReturn('Doe');
        $billingAddress
          ->expects($this->any())
          ->method('getTelephone')
          ->willReturn(756852919);
        $billingAddress
          ->expects($this->any())
          ->method('getCountry')
          ->willReturn('US');
        $billingAddress
          ->expects($this->any())
          ->method('getRegion')
          ->willReturn('Alabama');
        $billingAddress
          ->expects($this->any())
          ->method('getStreet1')
          ->willReturn('Alabama US');
        $billingAddress
          ->expects($this->any())
          ->method('getCity')
          ->willReturn('Alabama');
        $billingAddress
          ->expects($this->any())
          ->method('getStreet2')
          ->willReturn($withStreet2 ? 'Street 2 address' : null);

        return $billingAddress;
    }

    private function getSaleOrderMockWithIncrementId(
      $billingAddress
    ) {
        $saleOrderLoadByIncrementIdMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
          ->disableOriginalConstructor()
          ->disableOriginalClone()
          ->disableArgumentCloning()
          ->disallowMockingUnknownTypes()
          ->setMethods([
            'getOrderCurrencyCode',
            'getBillingAddress',
            'getData',
            'getFullTaxInfo',
            'getCoupon_code',
            'getShippingAmount',
            'getShipping_description',
          ])
          ->getMock();

        $saleOrderLoadByIncrementIdMock
          ->expects($this->any())
          ->method('getBillingAddress')
          ->willReturn($billingAddress);

        // mock for email address taken from order not from shipping or billing
        $saleOrderLoadByIncrementIdMock
          ->expects($this->any())
          ->method('getData')
          ->with('customer_email')
          ->willReturn('test@test.com');
        $saleOrderLoadByIncrementIdMock
          ->expects($this->any())
          ->method('getOrderCurrencyCode')
          ->willReturn('USD');
        $saleOrderLoadByIncrementIdMock
          ->expects($this->any())
          ->method('getFullTaxInfo')
          ->willReturn([]);
        $saleOrderLoadByIncrementIdMock
          ->expects($this->any())
          ->method('getShipping_description')
          ->willReturn('Flat Rate - Fixed');
        $saleOrderLoadByIncrementIdMock
          ->expects($this->any())
          ->method('getCoupon_code')
          ->willReturn(null);
        $saleOrderLoadByIncrementIdMock
          ->expects($this->any())
          ->method('getShippingAmount')
          ->willReturn(80.0);

        return $saleOrderLoadByIncrementIdMock;
    }

}
