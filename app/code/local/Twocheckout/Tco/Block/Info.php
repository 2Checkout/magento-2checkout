<?php

class Twocheckout_Tco_Block_Info extends Mage_Payment_Block_Info
{

    /**
     * Magento 1 construct alias
     *
     * This function is not written wrongly, it's a magento specific function
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('tco/info.phtml');
    }

    /**
     * @return string
     * @throws \Mage_Core_Exception
     */
    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }

}
