<?php

/**
 * BuyNow model
 *
 * @category   Aydus
 * @package    Aydus_BuyNow
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_BuyNow_Model_Buynow extends Mage_Core_Model_Abstract
{

    public function login($username, $password)
    {
        $result = array('error'=>true);
        
        $session = Mage::getSingleton('customer/session');
        
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId);
            
        try {
            
            $customer->authenticate($username, $password);

            $session->setCustomerAsLoggedIn($customer);
            $session->renewSession();
            
            $result['error'] = false;
            $result['data']['message'] = 'Customer is logged in.';
            

        } catch (Exception $ex) {
            
            $result['error'] = true;
            $result['data'] = $ex->getMessage();
        }
                    
        return $result;
    }
    
    public function addToCart($params)
    {
        $result = array('error'=>true);
                
        if (isset($params['qty'])) {
            $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            $params['qty'] = $filter->filter($params['qty']);
        }
        
        $productId = (int) $params['product'];
        
        $product = Mage::getModel('catalog/product')
        ->setStoreId(Mage::app()->getStore()->getId())
        ->load($productId);
        
        if ($product && $product->getId()) {
        
            $cart = Mage::getSingleton('checkout/cart');
            $productIds = $cart->getProductIds();
        
            if (!in_array($productId, $productIds)){
        
                $cart->addProduct($product, $params);
                
                $related = $params['related_product'];
                if (!empty($related)) {
                    $cart->addProductsByIds(explode(',', $related));
                }
        
                $cart->save();
        
                Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        
                Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => Mage::app()->getRequest(), 'response' => Mage::app()->getResponse())
                );
                
                $result['error'] = false;
                $result['data']['message'] = 'Product was added to cart.';
        
            } else {
        
                $result['error'] = false;
                $result['data']['message'] = 'Product is already in the cart.';
            }
        
        } else {
        
            $result['error'] = true;
            $result['data'] = 'Product could not be loaded.';
        }
        
        return $result;
    }
    
}