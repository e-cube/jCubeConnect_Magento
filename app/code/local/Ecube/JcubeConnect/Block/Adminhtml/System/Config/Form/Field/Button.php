<?php

class Ecube_JcubeConnect_Block_Adminhtml_System_Config_Form_Field_Button extends Mage_Adminhtml_Block_System_Config_Form_Field {
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $elementOriginalData = $element->getOriginalData();
        $caption = '-';
        if (isset($elementOriginalData['caption'])) {
            $caption = $elementOriginalData['caption'];
        }

        $action = 'dashboard';
        if (isset($elementOriginalData['action'])) {
            $action = $elementOriginalData['action'];
        }
        $url = $this->getUrl($action);

        $css_class = 'scalable';
        if (isset($elementOriginalData['css_class'])) {
            $css_class = $elementOriginalData['css_class'];
        }

        $onclick = '';
        if (isset($elementOriginalData['action_confirm']))
            $onclick = 'if (confirm(\'' . $elementOriginalData['action_confirm'] . '\')) ';
        $onclick.= 'setLocation(\'' . $url . '\');';

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass($css_class)
                    ->setLabel($caption)
                    ->setOnClick($onclick)
                    ->toHtml();

        return $html;
    }

}
