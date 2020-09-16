<?php


class Twocheckout_Api_Block_Info extends Mage_Payment_Block_Info
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('twocheckout/info.phtml');
    }

    /**
     * @return mixed
     */
    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }
  
}
