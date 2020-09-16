<?php

// this file will simply require all the stuff needed for
// magento to load all of its file properly
require_once dirname(__DIR__) . '../../bootstrap.php';
use PHPUnit\Framework\TestCase;
class DataTest extends TestCase
{
    protected $testClass;
    protected $buyLinkSecret = 'CREDENTIALS GO HERE';
    protected $expectedSignature = '3c23ab9b414317a313d1b08a5a7e9a029df4c6b8844e15600686262ad1550ec3';
    protected $expectedHmac = '69d3e406df927d664b3b90cf70eec990';
    protected $secretKey = 'CREDENTIALS GO HERE';

    protected function setUp(): void
    {
        $this->testClass = new Twocheckout_Tco_Helper_Data();
    }

    public function testGenerateHash()
    {
        $clientId = 'CREDENTIALS GO HERE - as INT';
        $clientSecret = 'CREDENTIALS GO HERE';
        $time = '2020-03-27 14:13:15';
        $hash = $this->testClass->generateHash($clientId, $clientSecret, $time);
        $this->assertSame('92e0f73da3f21005a67d96b990489b23', $hash);
    }

    public function testSignature()
    {
        // use assert same instead of assert equals here
        // same checks for the type fo the assertion too
        $this->assertSame(
          $this->expectedSignature,
          $this->testClass->generateSignature(
            $this->getSignParams(),
            $this->buyLinkSecret
          )
        );

        // incorrect signature values
        $this->assertNotSame(
          $this->expectedSignature,
          $this->testClass->generateSignature(
            $this->getIncorrectSignParams(),
            $this->buyLinkSecret
          )
        );

        $this->assertNotSame($this->expectedSignature, 'incorrect');
    }

    public function testHeaders()
    {
        $merchantId = 250111206876;

        $gmtDate = gmdate('Y-m-d H:i:s');
        $string = strlen($merchantId) . $merchantId . strlen($gmtDate) . $gmtDate;
        $hash = hash_hmac('md5', $string, $this->secretKey);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        $headers[] = sprintf('X-Avangate-Authentication: code="%s" date="%s" hash="%s"',
          $merchantId, $gmtDate, $hash);

        $this->assertSame($headers, $this->testClass->generateHeaders($merchantId, $this->secretKey));
    }

    public function testHmac()
    {
        $this->assertSame($this->expectedHmac, $this->testClass->hmac($this->secretKey, $this->getHmacData()));

        $this->assertNotSame($this->expectedHmac, $this->testClass->hmac($this->secretKey, $this->getIncorrectHmacData()));

        $this->assertNotSame($this->expectedHmac, 'incorrect');
    }

    public function testArrayExpand()
    {
        $expectedArrayExpandValue = '14Cart_145000123';

        $this->assertSame($expectedArrayExpandValue, $this->testClass->arrayExpand($this->getArrayExpandMockValue()));
        // incorrect hmac values
        $this->assertNotSame($expectedArrayExpandValue, $this->testClass->arrayExpand($this->getIncorrectArrayExpandMockValue()));

        $incorrectArrayExpandValue = 'incorrect';
        $this->assertNotSame($incorrectArrayExpandValue, $this->testClass->arrayExpand($this->getArrayExpandMockValue()));
    }

    private function getSignParams()
    {
        return json_decode('{"name":"John Doe","phone":"756852919","country":"US","state":"Alabama","email":"test@test.com","address":"Albama US","city":"Alabama","ship-name":"John Doe","ship-country":"US","ship-state":"Alabama","ship-city":"Alabama","ship-email":"test@test.com","ship-address":"Albama US","zip":"41231123","prod":"Cart_145000152","price":80,"qty":1,"type":"PRODUCT","tangible":0,"src":"MAGENTO1","return-url":"http:\/\/magento1.local\/tco\/redirect\/","return-type":"redirect","expiration":1585331229,"order-ext-ref":"145000152","item-ext-ref":"20200327124709","customer-ext-ref":"test@test.com","currency":"usd","language":"en","test":"1","merchant":"250111206876","dynamic":1}', true);
    }


    private function getIncorrectSignParams()
    {
        return json_decode('{"name":"John Doe","phone":"756852919","country":"US","state":"Alabama","email":"test@test.com","address":"Albama US","city":"Alabama","ship-name":"John Doe","ship-country":"US","ship-state":"Alabama","ship-city":"Alabama","ship-email":"test@test.com","ship-address":"Albama US","zip":"41231123","prod":"Cart_145000152","price":80,"qty":1,"type":"PRODUCT","tangible":0,"src":"MAGENTO1","return-url":"http:\/\/magento1.local\/tco\/redirect\/","return-type":"redirect","expiration":1585331229,"order-ext-ref":"145000152","item-ext-ref":"20200327124709","customer-ext-ref":"test@test.com","currency":"eur","language":"en","test":"1","merchant":"250111206876","dynamic":1}', true);
    }

    private function getHmacData()
    {
        return '10192020-03-23 03:52:38192020-03-23 03:53:2781154983591450001230187SUSPECT21Visa/MasterCard EN DB8CCVISAMC4John3Doe0000001-07Alabama7Alabama84123112324United States of America2us0013test@test.com4John3Doe01-07Alabama7Alabama84123112324United States of America2us013test@test.com1010.5.22.990190000-00-00 00:00:009GMT-07:003USD2en7REGULAR7763629914Cart_145000123014202003231052230116165.0040.0040.001140.000010040.00776362997REGULAR109B00657E96191969-12-31 07:00:00192020-03-23 03:53:260006165.006165.0040.0040.00923212845413test@test.com40.0040.0040.0040.0040.008MAGENTO1100040.00710.278304NONE0113Web6DENIED4visa41111512/23142020032223510411106154.723USD';
    }

    private function getIncorrectHmacData()
    {
        return '03:52:38192020-03-23 03:53:2781154983591450001230187SUSPECT21Visa/MasterCard EN DB8CCVISAMC4John3Doe0000001-07Alabama7Alabama84123112324United States of America2us0013test@test.com4John3Doe01-07Alabama7Alabama84123112324United States of America2us013test@test.com1010.5.22.990190000-00-00 00:00:009GMT-07:003USD2en7REGULAR7763629914Cart_145000123014202003231052230116165.0040.0040.001140.000010040.00776362997REGULAR109B00657E96191969-12-31 07:00:00192020-03-23 03:53:260006165.006165.0040.0040.00923212845413test@test.com40.0040.0040.0040.0040.008MAGENTO1100040.00710.278304NONE0113Web6DENIED4visa41111512/23142020032223510411106154.723USD';
    }

    private function getArrayExpandMockValue()
    {
        return ['Cart_145000123'];
    }

    private function getIncorrectArrayExpandMockValue()
    {
        return ['art_145000123'];
    }

}
