<?php

class Ecube_JcubeConnect_Model_Observer_Sync {
    /**
     * Helper object
     * @var Ecube_JcubeConnect_Helper_Data
     */
    protected $_helper;

    /**
     * Flag enabled
     * @var boolean
     */
    protected $_enabled;

    /**
     * Load helper object
     * @return Ecube_JcubeConnect_Helper_Data
     */
    protected function helper() {
        if (!isset($this->_helper))
            $this->_helper = Mage::helper('jcubeconnect');
        return $this->_helper;
    }

    /**
     * Check if cart sync is enabled in config
     * @return boolean
     */
    protected function isEnabled() {
        if (!isset($this->_enabled))
            $this->_enabled = $this->helper()->isCartSyncEnabled();
        return $this->_enabled;
    }

    /*
     * Event functions
     */
    public function retrieveBasketFromJcube($observer) {
        if (!$this->isEnabled())
            return;

        try {
            if ($this->helper()->isFrontend()) {
                $front = $observer->getData('controller_action');
                $requestUri = $front->getRequest()->getRequestUri();
                if (!preg_match($this->helper()->getConfigData('cartsync/no_retreive_on_routes'), $requestUri, $matches)) {
                    $this->helper()->log('retrieveBasketFromJcube match: ' . $requestUri);
                    $sync = Mage::getSingleton('jcubeconnect/sync');
                    $sync->retreiveBasket($requestUri);
                }
            }
        }
        catch (Exception $e) {}
    }

    public function sendBasketToJcube($observer) {
        if (!$this->isEnabled())
            return;

        try {
            if ($this->helper()->isFrontend()) {
                $requestUri = $observer->getFront()->getRequest()->getRequestUri();
                if (!preg_match($this->helper()->getConfigData('cartsync/no_send_on_routes'), $requestUri, $matches)) {
                    $this->helper()->log('sendBasketToJcube ' . $requestUri);
                    $sync = Mage::getSingleton('jcubeconnect/sync');
                    $sync->sendBasket($requestUri);
                }
            }
        }
        catch (Exception $e) {}
    }

}
