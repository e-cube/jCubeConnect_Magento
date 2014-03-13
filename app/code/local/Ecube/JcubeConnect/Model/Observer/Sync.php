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
     * 
     * initController --- NOT USED ANYMORE
     * controllerPredispatch - retreiveBasket()
     * cartSaveAfter - sendBasket()
     * sendResponseBefore - sendBasket()
     * customerLogout - sendBasket()
     * quoteMergeAfter --- NOT USED ANYMORE
     * 
     */
    public function initController($observer) {
        if (!$this->isEnabled())
            return;

        try {
            $front = $observer->getData('front');
            $request = $front->getRequest()->getRequestUri();
            if (preg_match($this->helper()->getConfigData('cartsync/retreive_on_routes'), $request, $matches)) {
                $this->helper()->log('initController match: ' . $request);
                $sync = Mage::getSingleton('jcubeconnect/sync');
                //$sync->startFrontendSession();
                $sync->retreiveBasket();
            }
        }
        catch (Exception $e) {}
    }

    public function controllerPredispatch($observer) {
        if (!$this->isEnabled())
            return;

        try {
            $front = $observer->getData('controller_action');
            $request = $front->getRequest()->getRequestUri();
            if (preg_match($this->helper()->getConfigData('cartsync/retreive_on_routes'), $request, $matches)) {
                $this->helper()->log('initController match: ' . $request);
                $sync = Mage::getSingleton('jcubeconnect/sync');
                $sync->retreiveBasket();
            }
        }
        catch (Exception $e) {}
    }

    public function cartSaveAfter($observer) {
        if (!$this->isEnabled())
            return;

        $this->helper()->log('cartSaveAfter');

        $sync = Mage::getSingleton('jcubeconnect/sync');
        $sync->setCartSaveOccurred(true);
        $sync->sendBasket();
    }

    public function sendResponseBefore($observer) {
        if (!$this->isEnabled())
            return;

        try {
            $request = $observer->getFront()->getRequest()->getRequestUri();
            if (!preg_match($this->helper()->getConfigData('cartsync/no_send_on_routes'), $request, $matches)) {
                $sync = Mage::getSingleton('jcubeconnect/sync');
                if (!$sync->getCartSaveOccurred()) {
                    $this->helper()->log('sendResponseBefore ' . $request);
                    $sync->sendBasket();
                }
            }
        }
        catch (Exception $e) {}
    }

    public function customerLogout($observer) {
        if (!$this->isEnabled())
            return;

        try {
            $sync = Mage::getSingleton('jcubeconnect/sync');
            $this->helper()->log('custmerLogout');
            $sync->sendBasket('logout');
            $sync->setCustomerLogoutOccurred(true);
        }
        catch (Exception $e) {}
    }

    public function quoteMergeAfter($observer) {
        return;
        if (!$this->isEnabled())
            return;

        try {
            $sync = Mage::getSingleton('jcubeconnect/sync');
            $this->helper()->log('quoteMergeAfter ' . $request);
            $sync->sendBasket();
        }
        catch (Exception $e) {}
    }
}
