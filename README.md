### _[Signup free with 2Checkout and start selling!](https://www.2checkout.com/signup)_

### Integrate Magento 1 with 2Checkout
----------------------------------------

### 2Checkout Payment Module Setup

#### 2Checkout Settings

1. Sign in to your 2Checkout account.
2. Navigate to **Dashboard** → **Integrations** → **Webhooks & API section**
3. There you can find the 'Merchant Code', 'Secret key', and the 'Buy link secret word'
4. Navigate to **Dashboard** → **Integrations** → **Ipn Settings**
5. Set the IPN URL which should be https://your-site-name.com/tco/notification
        5a. Example IPN URL: http://example.com/tco/notification
6. Enable 'Triggers' in the IPN section. It’s simpler to enable all the triggers. Those who are not required will simply not be used.

#### Magento Settings

1. Download the 2Checkout payment module from https://github.com/2Checkout/magento-2checkout
        
    1a. Under the 'Releases' you can find the latest release.
        
    1b. You can also download the connector directly from the 'master' branch as it always reflects the latest release.
2. Upload the files your magento install in the correct corresponding paths:

```
    app
    │   ├── code
    │   │   └── local
    │   │       └── Twocheckout
    │   │           ├── Api
    │   │           │   ├── Block
    │   │           │   │   ├── Form.php
    │   │           │   │   └── Info.php
    │   │           │   ├── Helper
    │   │           │   │   └── Data.php
    │   │           │   ├── Model
    │   │           │   │   └── Payment.php
    │   │           │   ├── controllers
    │   │           │   │   └── Redirect3DSecureController.php
    │   │           │   ├── etc
    │   │           │   │   ├── config.xml
    │   │           │   │   └── system.xml
    │   │           │   └── tests
    │   │           │       ├── Unit
    │   │           │       │   └── Helper
    │   │           │       │       └── ApiDataTest.php
    │   │           │       ├── bootstrap.php
    │   │           │       └── phpunit.xml
    │   │           └── Tco
    │   │               ├── Block
    │   │               │   ├── Form.php
    │   │               │   ├── Iframe.php
    │   │               │   ├── Info.php
    │   │               │   ├── Inline.php
    │   │               │   └── Redirect.php
    │   │               ├── Helper
    │   │               │   └── Data.php
    │   │               ├── Model
    │   │               │   ├── Checkout.php
    │   │               │   └── Observer.php
    │   │               ├── controllers
    │   │               │   ├── NotificationController.php
    │   │               │   └── ResponseController.php
    │   │               ├── etc
    │   │               │   ├── config.xml
    │   │               │   └── system.xml
    │   │               └── tests
    │   │                   ├── Unit
    │   │                   │   ├── Helper
    │   │                   │   │   └── DataTest.php
    │   │                   │   └── Model
    │   │                   │       └── CheckoutTest.php
    │   │                   ├── bootstrap.php
    │   │                   └── phpunit.xml
    │   ├── design
    │   │   └── frontend
    │   │       └── base
    │   │           └── default
    │   │               ├── layout
    │   │               │   ├── tco.xml
    │   │               │   └── twocheckout.xml
    │   │               └── template
    │   │                   ├── tco
    │   │                   │   ├── form.phtml
    │   │                   │   ├── iframe.phtml
    │   │                   │   └── info.phtml
    │   │                   └── twocheckout
    │   │                       ├── form.phtml
    │   │                       ├── info.phtml
    │   │                       └── script.phtml
    │   └── etc
    │       └── modules
    │           ├── Twocheckout_Api.xml
    │           └── Twocheckout_Tco.xml
```

3. Sign in to your Magento 1 administration
4. Flush your Magento cache under System > Cache Management and re-index all templates under System > Index Management.
5. Navigate to System > Configuration > Payment Methods 
6. You will be notice that there are 2 payment methods available
    
    6a. 2Checkout is for the Hosted Inline and Convert Plus
    
    6b. 2Checkout Api is for the 2PayJS

_**IMPORTANT: Both '2Checkout' and '2Checkout API' must be configured but only 1 needs to be enabled. Usually they will have the same settings.**_

7. Navigate to Payment Methods under System > Configuration > Payment Methods and open 2Checkout.
    
    7a. Enter a title in the “Title” field. This is optional, the default can be left as is.
    
    7b. You can “Enable” the module if you wish to use Inline or Convert Plus
    
    7c. The field “Invoice automatically after 2Checkout fraud approval” will create an invoice automatically after the order passes fraud review.
    
    7d. The field “Invoice automatically after 2checkout marks the order as complete” will create an invoice automatically after the order passes fraud review.

    NOTE: While both of the above options can be set to “Yes” it’s recommended to have only 1 of them set to “Yes”. 
    HOWEVER at least 1(one) must be set to “Yes” in order to create an invoice AND be able to do an “Online Refund”. If both are set to “No” then an invoice will not be created. An offline refund can still be issued.
    
    7e. Enter you Merchant Code in the field “2Checkout Merchant Id”
    
    7f. Enter you BuyLink secret word in the field “Buy link secret word”
    
    7g. Enter your secret key in the field “Secret key”
    
    7h. The field “2Checkout Inline Mode” will either enable or disable Inline mode. If Inline Mode is disable then the Convert Plus mode will be active.
    
    7i. The field “2Checkout Demo Mode” can be set to “Yes” in order to do Demo orders. These sort of orders have no impact and are simply there for demonstration or debugging purposes.
    
    _Note: 2Checkout API doesn’t have any configuration other than “Title” and “Enable”. It will inherit the configuration of the 2checkout module. If you wish to use only the 2payjs(2checkout API) then simply configure the 2checkout module and disable it._

8. Don’t forget to save your changes.

Please feel free to contact 2Checkout directly with any integration questions.
