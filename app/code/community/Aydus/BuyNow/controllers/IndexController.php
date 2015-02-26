<?php

/**
 * BuyNow controller
 *
 * @category   Aydus
 * @package    Aydus_BuyNow
 * @author     Aydus Consulting <davidt@aydus.com>
 */
class Aydus_BuyNow_IndexController extends Mage_Core_Controller_Front_Action {

    protected function _getModel() {
        return Mage::getSingleton('aydus_buynow/buynow');
    }

    public function loginAction() {
        $result = array();

        $request = $this->getRequest();

        $login = $request->getParam('login');

        if ($login && $login['username'] && $login['password']) {

            $username = $login['username'];
            $password = $login['password'];

            $result = $this->_getModel()->login($username, $password);

            if (!$result['error']) {

                $header = $this->_getHeader();
                $checkoutFormHtml = $this->_getCheckoutForm();

                $result['data']['header'] = $header;
                $result['data']['checkout'] = $checkoutFormHtml;
            }
        } else {

            $result['error'] = true;
            $result['data'] = Mage::helper('aydus_buynow')->__('Parameters missing.');
        }

        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }

    public function addtocartAction() {
        $result = array();

        if ($this->_validateFormKey()) {

            $request = $this->getRequest();
            $params = $request->getParams();

            try {

                $result = $this->_getModel()->addToCart($params);

                if (!$result['error']) {

                    $header = $this->_getHeader();
                    $checkoutFormHtml = $this->_getCheckoutForm();

                    $result['data']['header'] = $header;
                    
                    if ($result['data']['checkout']){
                        $result['data']['checkout'] = $checkoutFormHtml;
                    }
                }
                
            } catch (Exception $ex) {

                $result['error'] = true;
                $result['data'] = $ex->getMessage();
            }
        } else {

            $result['error'] = true;
            $result['data'] = 'Invalid form';
        }

        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }

    public function checkoutAction() {
        $result = array();

        if ($this->_validateFormKey()) {

            $request = $this->getRequest();
            $params = $request->getParams();

            try {

                $result = $this->_getModel()->checkout($params);

                if (!$result['error']) {

                    $header = $this->_getHeader();
                    $success = $this->_getSuccess();

                    $result['data']['header'] = $header;
                    $result['data']['success'] = $success;
                }
            } catch (Exception $ex) {

                $result['error'] = true;
                $result['data'] = $ex->getMessage();
            }
        } else {

            $result['error'] = true;
            $result['data'] = 'Invalid form';
        }

        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }

    /**
     * Get header
     * 
     * @return array
     */
    protected function _getHeader() {

        $cartHeader = $this->getLayout()->createBlock('checkout/cart_sidebar');
        $cartHeader->addItemRender('simple', 'checkout/cart_item_renderer', 'checkout/cart/sidebar/default.phtml');
        $cartHeader->addItemRender('grouped', 'checkout/cart_item_renderer_grouped', 'checkout/cart/sidebar/default.phtml');
        $cartHeader->addItemRender('configurable', 'checkout/cart_item_renderer_configurable', 'checkout/cart/sidebar/default.phtml');
        if (Mage::getSingleton('core/design_package')->getPackageName() == 'rwd') {
            $cartHeader->setTemplate('checkout/cart/minicart/items.phtml');
        } else {
            $cartHeader->setTemplate('checkout/cart/cartheader.phtml');
        }

        $cart = Mage::getSingleton('checkout/cart');
        $count = (int) $cart->getQuote()->getItemsQty();
        $addClass = '';
        $removeClass = '';

        if ($count == 1) {
            $topLinkCart = Mage::helper('checkout')->__('My Cart (%s item)', $count);
            $removeClass = 'no-count';
        } elseif ($count > 0) {
            $topLinkCart = Mage::helper('checkout')->__('My Cart (%s items)', $count);
            $removeClass = 'no-count';
        } else {
            $topLinkCart = Mage::helper('checkout')->__('My Cart');
            $addClass = 'no-count';
        }

        $data = array(
            '.top-link-cart' => array(
                'html' => $topLinkCart,
            ),
            '.skip-cart .count' => array(
                'html' => $count,
            ),
            '.skip-cart' => array(
                'removeClass' => $removeClass,
                'addClass' => $addClass,
            ),
            '#header-cart' => array(
                'html' => $cartHeader->toHtml(),
            ),
            '.top-cart' => array(
                'html' => $cartHeader->toHtml(),
            ),
            'a[title="' . Mage::helper('customer')->__('Register') . '"]' => array(
                'parent' => true,
                'remove' => true,
            ),
            'a[title="' . Mage::helper('customer')->__('Log In') . '"]' => array(
                'parent' => true,
                'html' => '<a href="' . Mage::helper('customer')->getLogoutUrl() . '" title="' . Mage::helper('customer')->__('Log Out') . '">' . Mage::helper('customer')->__('Log Out') . '</a>'
            ),
        );

        return $data;
    }

    /**
     * Get checkout html if customer has billing agreement
     * 
     * @return string|boolean
     */
    public function _getCheckoutForm() {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $collection = Mage::getModel('sales/billing_agreement')->getAvailableCustomerBillingAgreements($customer->getId());

        if ($collection->getSize() > 0) {

            $checkoutForm = $this->getLayout()->createBlock('aydus_buynow/form');
            $checkoutForm->setTemplate('aydus/buynow/checkout.phtml');
            $checkoutForm->setNameInLayout('buynow.checkout');
            $shippingMethods = $this->getLayout()->createBlock('aydus_buynow/form');
            $shippingMethods->setTemplate('aydus/buynow/shipping/methods.phtml');
            $checkoutForm->setChild('buynow.shipping_method',$shippingMethods);
                        
            return $checkoutForm->toHtml();
        }

        return false;
    }
    
    public function changeBillingAddressAction()
    {
        $result = array();
        
        $customerAddressId = (int)$this->getRequest()->getParam('billing_address_id');
        
        if ($customerAddressId){
            
            $result = $this->_getModel()->setBillingAddress($customerAddressId);
            
        } else {
            
            $result['error'] = true;
            $result['data'] = 'No billing address id.';
        }
        
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));        
    }
    
    public function shippingMethodsAction()
    {
        $result = array();
        
        $customerAddressId = (int)$this->getRequest()->getParam('shipping_address_id');
        
        if ($customerAddressId){
        
            $result = $this->_getModel()->setShippingAddress($customerAddressId);
            
            if (!$result['error']){
                
                $shippingMethods = $this->getLayout()->createBlock('aydus_buynow/form');
                $shippingMethods->setTemplate('aydus/buynow/shipping/methods.phtml');
                                                
                $result['error'] = false;
                $result['data']['html'] = $shippingMethods->toHtml();
            }
        
        } else {
        
            $result['error'] = true;
            $result['data'] = 'No billing address id.';
        }        

        
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));
        
    }

    public function _getSuccess() {
        $success = $this->getLayout()->createBlock('checkout/onepage_success');
        $success->setTemplate('checkout/success.phtml');

        return $success->toHtml();
    }

}
