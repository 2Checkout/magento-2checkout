<?php

class Twocheckout_Tco_Block_Redirect extends Mage_Core_Block_Abstract
{

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $tco = Mage::getModel('tco/checkout');

        $form = new Varien_Data_Form();
        $form->setAction($tco->getUrl())
          ->setId('tcopay')
          ->setName('tcopay')
          ->setMethod('GET')
          ->setUseContainer(true);
        foreach ($tco->getFormFields() as $field => $value) {
            $form->addField($field, 'hidden', ['name' => $field, 'value' => $value]);
        }
        $form->addField('tcosubmit', 'submit', []);

        $html = '<style> #tcosubmit {display:none;} </style>';
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("tcopay").submit();</script>';

        return $html;
    }

}
