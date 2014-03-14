<?php

class Ecube_JcubeConnect_Model_Sync {
    const BASKET_RETREIVE = true;
    const BASKET_SEND = true;

    /**
     * Helper object
     * @var Ecube_JcubeConnect_Helper_Data
     */
    protected $_helper;

    /**
     * Transport object
     * @var Ecube_JcubeConnect_Model_Sync_Transport
     */
    protected $_transport;

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
     * Load transport object
     * @return Ecube_JcubeConnect_Model_Sync_Transport
     */
    protected function getTransport() {
        if (!isset($this->_transport))
            $this->_transport = Mage::getSingleton('jcubeconnect/sync_transport');
        return $this->_transport;
    }

    /**
     * Get checkout session
     */
    protected function getSession() {
        return Mage::getSingleton('checkout/session');
    }

    /* Not used */
    protected function hasQuote() {
        $session = $this->getSession();
        return ($session->getQuote() !== null);
    }

    /* Not used */
    protected function getQuote() {
        $session = $this->getSession();
        return $session->getQuote();
    }

    /**
     * Get Magento cart object
     * Cart object handles most of the quote updates
     */
    protected function getCart() {
        return Mage::getSingleton('checkout/cart');
    }

    /**
     * Load product model
     * @param  int $productId
     * @return Mage_Catalog_Model_Product
     */
    protected function initProduct($productId) {
        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($productId);
        if ($product->getId())
            return $product;
        return false;
    }

    /**
     * Retreive basket from jCube (requestBasket)
     */
    protected function retrieveBasketFromJCube() {
        $this->initTransport();

        $result = $this->getTransport()->send($this->helper()->getConfigData('cartsync/api_url_getbasket'));
        if ($result[0] != 200) {
            $this->helper()->log('Failed to retreive basket from jCube: ' . print_r($result, true), Zend_Log::ERR);
            return false;
        }
        $data = json_decode($result[1], true);
        $this->getTransport()->convertReceivedData($data);

        if ($this->helper()->getLogTransport()) {
            $this->helper()->log(print_r($this->getTransport()->toTransportArray(true), true));
        }

        return true;
    }

    /**
     * Process basket retreived from jCube
     */
    protected function processJcubeBasket() {
        $cart = $this->getCart();
        //$quote = $cart->getQuote();
        $quote = $this->getSession()->getQuote();
        $jCubeItems = $this->getTransport()->getCartItems();
        $quoteChanged = false;

        $this->helper()->log('jCube basket item count: ' . count($jCubeItems));

        $removed = 0;
        $updated = 0;
        $added = 0;
        $quoteProductIds = array();

        /**
         * Update / remove items
         */
        if ($quote) {
            $quoteItems = $quote->getAllItems();
            // Loop through existing quote items
            // Only act on 'simple' products
            foreach ($quoteItems as $item) {
                if ($item->getProductType() != 'simple')
                    continue;

                $quoteProductIds[] = $item->getProductId();

                // is Magento quote item still in jCube baset?
                if (isset($jCubeItems[$item->getProductId()])) {
                    // yes, check quantity
                    // parent needed for configurable products
                    $curQty = $item->getQty();
                    $parent = $item->getParentItem();
                    if ($parent)
                        $curQty = $parent->getQty();

                    $newQty = $jCubeItems[$item->getProductId()]['quantity'];
                    if ($newQty <> $curQty) {
                        $this->helper()->log('Update item qty in Magento basket: ' . $item->getProductId() . ' from ' . $curQty . ' to ' . $newQty);
                        if ($parent) {
                            $parent->setQty($newQty);
                            $parent->save();
                        }
                        else {
                            $item->setQty($newQty);
                            $item->save();
                        }
                        $quoteChanged = true;
                        $updated++;
                    }
                }
                else {
                    // no, remove item from quote
                    $this->helper()->log('Remove item ' . $item->getId() . ' from Magento basket: ' . $item->getProductId());
                    $quote->removeItem($item->getId());
                    $quoteChanged = true;
                    $removed++;
                }
            }
        }

        /**
         * Add products loop
         */
        $saveCart = false;
        foreach ($jCubeItems as $item) {
            $productId = $item['magentoProductId'];
            $this->helper()->log('Found jCube product: ' . $productId);
            if (!in_array($productId, $quoteProductIds)) {
                // Add product to cart
                if ($product = $this->initProduct($productId)) {
                    $addParams = array('qty' => $item['quantity']);

                    $parent = false;
                    if ($item['parentId']) {
                        $parent = $this->initProduct($item['parentId']);
                        //list($parentId) = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                        if (!$parent) {
                            $this->helper()->log('Failed to load parent ' . $item['parentId'] . ' of child product ' . $productId, Zend_Log::WARN);
                        }
                    }
                    if ($parent && isset($item['super_attribute'])) {
                        $addParams['super_attribute'] = $item['super_attribute'];
                        $cart->addProduct($parent, $addParams);
                    }
                    else {
                        $cart->addProduct($product, $addParams);
                    }

                    $this->helper()->log('Added item to Magento basket: ' . $productId);
                    
                    $saveCart = true;
                    $added++;
                }
                else {
                    $this->helper()->log('Product ' . $productId . ' not found', Zend_Log::WARN);
                }
            }
        }
        if ($saveCart) {
            $cart->save();
            $this->getSession()->setCartWasUpdated(true);
        }

        if ($removed || $updated || $added)
            $this->helper()->log('Cart update: removed ' . $removed . ', updated ' . $updated . ', added ' . $added);
        else
            $this->helper()->log('Cart is up to date');

        if ($quoteChanged) {
            $quote->save();
        }
    }

    /**
     * Load quote items into transport object
     * @return boolean false if no quote found
     */
    protected function prepareBasketForSend() {
        $this->getTransport()->setCartItems(array());
        $this->getTransport()->setQuoteId(0);

        $cart = $this->getCart();
        //if ($quote = $cart->getQuote()) {
        if ($quote = $this->getSession()->getQuote()) {
            $this->getTransport()->setQuoteId($quote->getId());

            $items = $quote->getAllItems();
            $cartItems = array();
            foreach ($items as $item) {
                if ($item->getProductType() != 'simple')
                    continue;

                $qty = $item->getQty();

                $parentProductId = 0;
                if ($item->getParentItemId()) {
                    //$parentItem = $quote->getItemById($item->getParentItemId());
                    $parentItem = $item->getParentItem();
                    if ($parentItem) {
                        $parentProductId = $parentItem->getProductId();
                        $qty = $parentItem->getQty();
                    }
                    else
                        $this->helper()->log('Failed to load parent product item row ' . $item->getParentItemId(), Zend_Log::WARN);
                }
                /*$cartItems[(int) $item->getProductId()] = array(
                    'magentoProductId' => (int) $item->getProductId(),
                    'quantity' => (int) $qty,
                    'parentId' => (int) $parentProductId,
                );*/
                $cartItems[] = array(
                    'magentoProductId' => (int) $item->getProductId(),
                    'quantity' => (int) $qty,
                    'parentId' => (int) $parentProductId,
                );
            }
            $this->getTransport()->setCartItems($cartItems);
            return true;
        }

        return false;
    }

    /**
     * Fill transport object with defaults
     * @return Ecube_JcubeConnect_Model_Sync_Transport
     */
    public function initTransport() {
        $transport = $this->getTransport();
        $transport->setJcubeSessionId($this->helper()->getJcubeCookie());
        $transport->setMagentoSessionId($this->helper()->getMagentoCookie());

        $transport->setCustomerId(0);
        $transport->setCustomerName('');
        $transport->setCustomerEmail('');
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $transport->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId());
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $transport->setCustomerName($customer->getName());
            $transport->setCustomerEmail($customer->getEmail());
        }

        $transport->setSessionData($this->getSession()->getData('_session_validator_data'));

        $transport->setSendQuote(false);
        return $transport;
    }

    /**
     * Retreives and processes basket from jCube
     * Called from observer
     */
    public function retreiveBasket() {
        if (!$this->helper()->isCartRetreiveEnabled())
            return;

        $this->helper()->log('<< Retreive basket from jCube');
        if ($this->retrieveBasketFromJCube())
            $this->processJcubeBasket();
        return;
    }

    /**
     * Load basket and send to jCube
     * @param  string $state Send empty baske if $state == 'logout'
     */
    public function sendBasket($state = '') {
        if (!$this->helper()->isCartSendEnabled())
            return;

        $this->initTransport();

        // always send empty basket on logout
        if ($state == 'logout') {
            $this->getTransport()->setCartItems(array());
            $this->getTransport()->setQuoteId(0);
        }
        else {
            $this->prepareBasketForSend();
        }
        $this->getTransport()->setSendQuote(true);

        $this->helper()->log('>> Send basket to jCube');

        if ($this->helper()->getLogTransport())
            $this->helper()->log(print_r($this->getTransport()->toTransportArray(true), true));
        $result = $this->getTransport()->send($this->helper()->getConfigData('cartsync/api_url_setbasket'));

        if ($result[0] != 200) {
            $this->helper()->log('Failed to send basket to jCube: ' . print_r($result[0], true), Zend_Log::ERR);
        }
    }
}
