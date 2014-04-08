<?php

/*
 * Sync transport data storage
 */
class Ecube_JcubeConnect_Model_Sync_Transport extends Varien_Object {

    public function convertReceivedData($data) {
        if (array_key_exists('jCubeSessionId', $data)) {
            $this->setJcubeSessionId($data['jCubeSessionId']);
        }
        if (array_key_exists('basketLines', $data)) {
            $this->setCartItems($data['basketLines']);
        }
        else {
            $this->setCartItems(array());
        }
        return $this;
    }

    public function toTransportArray($debug = false) {
        $res = array(
            'jCubeSessionId' => (string) $this->getJcubeSessionId(),
            'magentoSessionId' => (string) $this->getMagentoSessionId(),
            'requestUri' => (string) $this->getRequestUri(),
            'customerId' => (int) $this->getCustomerId(),
            'customerName' => (string) $this->getCustomerName(),
            'customerEmail' => (string) $this->getCustomerEmail(),
        );
        if ($debug || $this->getSendQuote()) {
            $res['quoteId'] = (int) $this->getQuoteId();
            $res['basketLines'] = $this->getCartItems();
        }
        $res['visitorData'] = array(
            'remote_addr' => (string) Mage::helper('core/http')->getRemoteAddr(),
            'http_user_agent' => (string) Mage::helper('core/http')->getHttpUserAgent(),
        );
        return $res;
    }

    public function send($url, $max_timeout_seconds) {
        $api = Mage::getModel('jcubeconnect/sync_transport_api');
        $result = $api->sendRequest(
            $url,
            array('Content-Type: application/json'),
            json_encode($this->toTransportArray()),
            $max_timeout_seconds
        );
        return $result;
    }
}
