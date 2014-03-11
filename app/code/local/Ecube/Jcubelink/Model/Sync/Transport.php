<?php

/*
 * Sync transport data storage
 */
class Ecube_Jcubelink_Model_Sync_Transport extends Varien_Object {
    /*
     * Set some defaults
     */
    protected function _construct() {
        $this->setData('customer_id', 0);
        $this->setData('quote_id', 0);
        $this->setData('cart_items', array());
        $this->setData('send_quote', false);
    }

    public function convertReceivedData($data) {
        $this->setJcubeSessionId($data['jCubeSessionId']);
        $this->setMagentoSessionId($data['magentoSessionId']);
        $this->setCustomerId($data['customerId']);
        $this->setQuoteId($data['quoteId']);
        $this->setCartItems($data['basketLines']);
        return $this;
    }

    public function toTransportArray($debug = false) {
        $res = array(
            'jCubeSessionId' => (string) $this->getJcubeSessionId(),
            'magentoSessionId' => (string) $this->getMagentoSessionId(),
            'customerId' => (int) $this->getCustomerId(),
            'customerName' => (string) $this->getCustomerName(),
            'customerEmail' => (string) $this->getCustomerEmail(),
        );
        if ($debug || $this->getSendQuote()) {
            $res['quoteId'] = (int) $this->getQuoteId();
            $res['basketLines'] = $this->getCartItems();
        }
        $res['visitorData'] = $this->getSessionData();
        return $res;
    }

    public function send($url) {
        $api = Mage::getModel('jcubelink/sync_transport_api');
        $result = $api->sendRequest(
            $url,
            array('Content-Type: application/json'),
            json_encode($this->toTransportArray())
        );
        return $result;
    }
}
