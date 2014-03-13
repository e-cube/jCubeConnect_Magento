<?php

class Ecube_JcubeConnect_Helper_Data extends Mage_Core_Helper_Abstract {
    const XML_PATH_BASE = 'jcubeconnect';
    const JCUBE_COOKIE = 'jcSessionId';
    const MAGENTO_COOKIE = 'frontend';

    protected $_debugEnabled;
    protected $_logForce;
    protected $_logTransport;
    protected $_mageCookie;
    protected $_cartSyncEnabled;
    protected $_cartRetreive;
    protected $_cartSend;

    public function getConfigData($key = null) {
        $path = self::XML_PATH_BASE;

        if (!is_null($key))
            $path.= '/' . $key;
        return Mage::getStoreConfig($path);
    }

    public function getFrameUrl() {
        return $this->getConfigData('frame/url');
    }

    public function log($message, $level = null) {
        if ($this->debugEnabled()) {
            $message = $this->getMagentoCookie() . ' - ' . $message;
            Mage::log($message, $level, self::XML_PATH_BASE . '.log', $this->_logForce);
        }
    }

    public function debugEnabled() {
        if (!isset($this->_debugEnabled)) {
            $this->_debugEnabled = $this->getConfigData('advanced/debug') == 1;
            $this->_logForce = $this->getConfigData('advanced/log_force') == 1;
        }
        return $this->_debugEnabled;
    }

    public function getJcubeCookie() {
        return Mage::getModel('core/cookie')->get(self::JCUBE_COOKIE);
    }

    public function getMagentoCookie() {
        if (!isset($this->_mageCookie))
            $this->_mageCookie = Mage::getSingleton('core/session')->getSessionId();
        return $this->_mageCookie;
    }

    public function isCartSyncEnabled() {
        if (!isset($this->_cartSyncEnabled))
            $this->_cartSyncEnabled = $this->getConfigData('cartsync/enabled') == 1;
        return $this->_cartSyncEnabled;
    }

    public function isCartRetreiveEnabled() {
        if (!isset($this->_cartRetreive))
            $this->_cartRetreive = $this->getConfigData('cartsync/retreive') == 1;
        return $this->_cartRetreive;
    }

    public function isCartSendEnabled() {
        if (!isset($this->_cartSend))
            $this->_cartSend = $this->getConfigData('cartsync/send') == 1;
        return $this->_cartSend;
    }

    public function getLogTransport() {
        if (!isset($this->_logTransport))
            $this->_logTransport = $this->getConfigData('advanced/log_transport');
        return $this->_logTransport;
    }
}
