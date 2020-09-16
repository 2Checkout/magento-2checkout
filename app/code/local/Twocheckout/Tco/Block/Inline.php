<?php

class Twocheckout_Tco_Block_Inline extends Mage_Core_Block_Abstract
{

	/**
	 * @return string
	 */
	protected function _toHtml()
	{
		$tco = Mage::getModel('tco/checkout');

		$formFields = $tco->getInlineFormFields();

		return '<script> 
  (function (document, src, libName, config) {
      var script             = document.createElement(\'script\');
      script.src             = src;
      script.async           = true;
      var firstScriptElement = document.getElementsByTagName(\'script\')[0];
      script.onload          = function () {
          for (var namespace in config) {
              if (config.hasOwnProperty(namespace)) {
                  window[libName].setup.setConfig(namespace, config[namespace]);
              }
          }
            window[libName].register();
            TwoCoInlineCart.setup.setMerchant(' . $tco->getMerchantId() . ');
            TwoCoInlineCart.setup.setMode("' . $formFields['mode'] . '");
            TwoCoInlineCart.register();
           
            
            TwoCoInlineCart.cart.setCurrency("' . $formFields['currency'] . '");
            TwoCoInlineCart.cart.setLanguage("' . $formFields['language'] . '");
            TwoCoInlineCart.cart.setReturnMethod(' . $formFields['return-method'] . ');
            TwoCoInlineCart.cart.setTest("' . $formFields['test'] . '");
            TwoCoInlineCart.cart.setOrderExternalRef("' . $formFields['order-ext-ref'] . '");
            TwoCoInlineCart.cart.setExternalCustomerReference("' . $formFields['customer-ext-ref'] . '");
            TwoCoInlineCart.cart.setSource("' . $formFields['src'] . '");
            TwoCoInlineCart.cart.setAutoAdvance(true);
            
            TwoCoInlineCart.products.removeAll();
            TwoCoInlineCart.products.addMany(' . $formFields['products'] . '); 
            TwoCoInlineCart.billing.setData(' . $formFields['billing_address'] . ');
            TwoCoInlineCart.shipping.setData(' . $formFields['shipping_address'] . ');
            TwoCoInlineCart.cart.setSignature("' . $formFields['signature'] . '");
            TwoCoInlineCart.cart.checkout();
            
      };
      firstScriptElement.parentNode.insertBefore(script, firstScriptElement);
  })(document, 
  \'https://secure.2checkout.com/checkout/client/twoCoInlineCart.js\', 
  \'TwoCoInlineCart\',
  {"app":{"merchant":"' . $tco->getMerchantId() . '"},"cart":{"host":"https:\/\/secure.2checkout.com"}}
  );
</script>';
	}

}
