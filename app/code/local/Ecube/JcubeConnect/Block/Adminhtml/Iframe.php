<?php

class Ecube_JcubeConnect_Block_Adminhtml_Iframe extends Mage_Core_Block_Abstract {
    protected $_helper;

    protected function _toHtml() {
        $this->_helper = Mage::helper('jcubeconnect');

        $url = $this->_helper->getFrameUrl();

        $url.= '?';
        $params = array(
            'customer_id' => $this->_helper->getConfigData('customer/id'),
            'customer_key' => $this->_helper->getConfigData('customer/key'),
        );
        $url.= http_build_query($params);

        $frameWidth = $this->_helper->getConfigData('frame/width');
        $frameHeight = $this->_helper->getConfigData('frame/height');

        return '<iframe src="'.$url.'" width="'.$frameWidth.'" height="'.$frameHeight.'" id="jcubeconnectframe" marginheight="0" frameborder="0"></iframe>';
    }
}
