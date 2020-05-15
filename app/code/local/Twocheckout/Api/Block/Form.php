<?php


class Twocheckout_Api_Block_Form extends Mage_Payment_Block_Form
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('twocheckout/form.phtml');
    }

}
