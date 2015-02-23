<?php

/**
 * BuyNow controller
 *
 * @category   Aydus
 * @package    Aydus_BuyNow
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_BuyNow_IndexController extends Mage_Core_Controller_Front_Action
{

    public function loginAction()
    {
        $result = array();
        
        $request = $this->getRequest();
        
        $login = $request->getParam('login');
        
        if ($login && $login['username'] && $login['password']){
            
            $username = $login['username'];
            $password = $login['password'];
            $session = Mage::getSingleton('customer/session');

            $websiteId = Mage::app()->getStore()->getWebsiteId();
            $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId);
            
            try {
                
                $customer->authenticate($username, $password);

                $session->setCustomerAsLoggedIn($customer);
                $session->renewSession();
               
                $header = $this->_getHeader();
                $checkoutFormHtml = $this->_getCheckoutForm();

                $result['error'] = false;
                $result['data'] = array(
                    'header' => $header,
                    'checkout' => $checkoutFormHtml,
                );

            } catch (Exception $ex) {
                
                $result['error'] = true;
                $result['data'] = $ex->getMessage();
            }

        } else {
            
            $result['error'] = true;
            $result['data'] = Mage::helper('aydus_buynow')->__('Parameters missing.');
        }

        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(json_encode($result));
        
    }
    
    public function addtocartAction()
    {
        if ($this->_validateFormKey()) {
        
            $result = array();

            $request = $this->getRequest();
            $params = $request->getParams();

            try {
                
                if (isset($params['qty'])) {
                    $filter = new Zend_Filter_LocalizedToNormalized(
                        array('locale' => Mage::app()->getLocale()->getLocaleCode())
                    );
                    $params['qty'] = $filter->filter($params['qty']);
                }
                
                $productId = (int) $this->getRequest()->getParam('product');
                $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($productId);

                if ($product && $product->getId()) {

                    $related = $this->getRequest()->getParam('related_product');

                    $cart = Mage::getSingleton('checkout/cart');
                    $cart->addProduct($product, $params);      
                    if (!empty($related)) {
                        $cart->addProductsByIds(explode(',', $related));
                    }

                    $cart->save();
                    
                    Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                    
                    Mage::dispatchEvent('checkout_cart_add_product_complete',
                        array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
                    );           
                    
                    $header = $this->_getHeader();
                    $checkoutFormHtml = $this->_getCheckoutForm();

                    $result['error'] = false;
                    $result['data'] = array(
                        'message' => 'Product was added to cart.',
                        'header' => $header,
                        'checkout' => $checkoutFormHtml,
                    );

                } else {

                    $result['error'] = true;
                    $result['data'] = 'Product could not be loaded.';
                }

            } catch (Exception $ex) {
                
                $result['error'] = true;
                $result['data'] = $ex->getMessage();
            }
            
        } else {
            
            $result['error'] = true;
            $result['data'] = 'Invalid form';
        }
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(json_encode($result));       
    }
    
    public function checkoutAction() 
    {
        $result = array();
        
        $request = $this->getRequest();
        
        $payment = $request->getParam('payment');
        
        if ($payment && $payment['ba_agreement_id']){
           
            try {
               

                $result['error'] = false;
                $result['data'] = '';

            } catch (Exception $e) {
                
                $result['error'] = true;
                $result['data'] = $e->getMessage();
            }

        } else {
            
            $result['error'] = true;
            $result['data'] = Mage::helper('aydus_buynow')->__('Parameters missing.');
        }
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
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
        if (Mage::getSingleton('core/design_package')->getPackageName() == 'rwd'){
            $cartHeader->setTemplate('checkout/cart/minicart/items.phtml');
        } else {
            $cartHeader->setTemplate('checkout/cart/cartheader.phtml');
        }
        
        $cart = Mage::getSingleton('checkout/cart');
        $count = (int)$cart->getQuote()->getItemsQty();
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
                'a[title="'.Mage::helper('customer')->__('Register').'"]' => array(
                        'parent' => true,
                        'remove' => true,
                ),            
                'a[title="'.Mage::helper('customer')->__('Log In').'"]' => array(
                        'parent' => true,
                        'html' => '<a href="'.Mage::helper('customer')->getLogoutUrl().'" title="'.Mage::helper('customer')->__('Log Out').'">'.Mage::helper('customer')->__('Log Out').'</a>'
                ),
        );
        
        return $data;
    }    
    
    public function _getCheckoutForm()
    {
        $checkoutForm = $this->getLayout()->createBlock('aydus_buynow/form');
        $checkoutForm->setTemplate('aydus/buynow/checkout.phtml');
        
        return $checkoutForm->toHtml();
    }
    
}
