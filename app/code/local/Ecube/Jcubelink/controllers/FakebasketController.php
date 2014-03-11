<?php

class Ecube_Jcubelink_FakebasketController extends Mage_Core_Controller_Front_Action {
    /**
     * Read basket from file and find parent product if needed
     * The parent product is normally provided by jCube but not sent by Magento
     * 
     * @return string JSON encoded string
     */
    protected function getBasket() {
        $basket = file_get_contents(Mage::getBaseDir('var') . DS . 'fakebasket.json');

        $basket = json_decode($basket, true);
        foreach ($basket['basketLines'] as &$item) {
            $item['super_attribute'] = array();
            // for products that have a parent we need to load super_attribute
            if ($item['parentId'] > 0) {
                $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($item['magentoProductId']);
                $parent = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($item['parentId']);
                if ($product) {
                    $attributes = $parent->getTypeInstance(true)->getConfigurableAttributes($parent);
                    foreach ($attributes as $attribute) {
                        $attId = $attribute->getProductAttribute()->getId();
                        $attCode = $attribute->getProductAttribute()->getAttributeCode();
                        $item['super_attribute'][$attId] = $product->getData($attCode);
                    }
                }
            }
        }

        return json_encode($basket);
    }

    /**
     * Store basket data into file
     * @param string $data JSON encoded string
     */
    protected function setBasket($data) {
        file_put_contents(Mage::getBaseDir('var') . DS . 'fakebasket.json', $data);
        file_put_contents(Mage::getBaseDir('var') . DS . 'fakebasket-' . date('Y_m_d_H_i_s') . '-' . uniqid() . '.json', $data);
    }

    /**
     * Update quantity for debug purposes
     * @param  boolean $increment
     */
    protected function updateQuantity($increment = true) {
        $basket = json_decode($this->getBasket(), true);
        if (isset($basket['basketLines'])) {
            foreach ($basket['basketLines'] as &$item) {
                if ($increment)
                    ++$item['quantity'];
                else
                    --$item['quantity'];
            }
        }
        $this->setBasket(json_encode($basket));
    }

    /**
     * Controller index action
     */
    public function indexAction() {
        echo '<h1>jCube cart contents</h1>';
        echo '<pre>';
        echo htmlspecialchars(print_r(json_decode($this->getBasket(), true), true));
        echo '</pre>';

        echo '<hr>';

        echo '<p>';
        echo '<a href="/jcubelink/fakebasket/?' . time() . '">Refresh</a> | ';
        echo '<a href="/jcubelink/fakebasket/clear">Empty cart</a> | ';
        echo '<a href="/jcubelink/fakebasket/qtyInc">Increment quantity</a> | ';
        echo '<a href="/jcubelink/fakebasket/qtyDec">Decrement quantity</a>';
        echo '<br /><br />';
        echo '<a href="/jcubelink/fakebasket/dummy">Load dummy cart</a>';
        echo '</p>';
    }

    /**
     * Controller clear action
     */
    public function clearAction() {
        unlink(Mage::getBaseDir('var') . DS . 'fakebasket.json');
        $this->_redirect('*/*/');
    }

    public function qtyIncAction() {
        $this->updateQuantity(true);
        $this->_redirect('*/*/');
    }

    public function qtyDecAction() {
        $this->updateQuantity(false);
        $this->_redirect('*/*/');
    }

    /**
     * Load dummy cart action
     */
    public function dummyAction() {
        // load dummy basket
        $jCubeBasketInfo = array(
           'jCubeSessionId' => '123dummy',
           'basketLines' => array(
               4 => array(
                    'magentoProductId' => 4,
                    'quantity' => 10,
                    'parentId' => 3,
                    'super_attribute' => array(
                        122 => 6,
                    ),
               ),
               12 => array(
                    'magentoProductId' => 12,
                    'quantity' => 1,
                    'parentId' => 8,
                    'super_attribute' => array(
                        80 => 11,
                        122 => 3,
                    ),
                ),
               // [product] => 8 [related_product] => [super_attribute] => Array ( [80] => 11 [122] => 3 ) [qty] => 1 ) 
           ),
        );
        $this->setBasket(json_encode($jCubeBasketInfo));
        $this->_redirect('*/*/');
    }

    /*
     * Send jCube basket to Magento
     */
    public function getBasketAction() {
        $this->loadLayout(false);
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody($this->getBasket());
        $this->renderLayout();
    }

    /*
     * Get Magento basket
     */
    public function setBasketAction() {
        $body = file_get_contents('php://input');

        $this->setBasket($body);
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'application/json')
            ->setBody('OK');

        $this->renderLayout();
    }
}

