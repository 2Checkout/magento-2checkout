<?php

class Twocheckout_Tco_Block_Form extends Mage_Payment_Block_Form
{

    /**
     * Magento 1 construct alias
     *
     * This function is not written wrongly, it's a magento specific function
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('tco/form.phtml');
    }

}
