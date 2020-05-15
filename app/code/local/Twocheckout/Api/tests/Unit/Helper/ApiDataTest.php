<?php

// this file will simply require all the stuff needed for
// magento to load all of its file properly
require_once dirname(__DIR__) . '../../bootstrap.php';

use PHPUnit\Framework\TestCase;

class ApiDataTest extends TestCase
{
    protected $testClass;
    protected $testCurrencyCodes = [
        'ALL',
        'AFN',
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

    protected function setUp()
    : void
    {
        $this->testClass = new Twocheckout_Api_Helper_Data();
    }

    /**
     * keys order is not important
     */
    public function testAvailableTcoCurrency()
    {
        $this->assertEqualsCanonicalizing($this->testCurrencyCodes, $this->testClass->getSupportedCurrency());
    }

}
