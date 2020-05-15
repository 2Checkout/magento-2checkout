<?php

class Twocheckout_Tco_Block_Iframe extends Mage_Core_Block_Template
{

    protected $_params = [];

    /**
     * Magento 1 construct alias
     *
     * This function is not written wrongly, it's a magento specific function
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('tco/iframe.phtml');
    }

    /**
     * @param $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

}
