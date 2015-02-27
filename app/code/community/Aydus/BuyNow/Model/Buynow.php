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
        if ($session->isLoggedIn()){
            
            $result['error'] = false;
            $result['data']['message'] = 'Customer is logged in.';
            
            return $result;
        }
            
        try {
            
            $result = $this->authenticate($username, $password);
            
            if ($result['error'] === false){
                
                $customer = $result['data']['customer'];
                $session->setCustomerAsLoggedIn($customer);
                $session->renewSession();
                
                $result['error'] = false;
                $result['data']['message'] = 'Customer is logged in.';
                
            }

        } catch (Exception $ex) {
            
            $result['error'] = true;
            $result['data'] = $ex->getMessage();
        }
                    
        return $result;
    }
    
    public function authenticate($email, $password)
    {
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId);
    
        try {
    
            $customer->authenticate($email, $password);
            
            $result['error'] = false;
            $result['data']['customer'] = $customer;
            $result['data']['email'] = $customer->getEmail();
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
                
        try {
            
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
            
                    $result['error'] = false;
                    $result['data']['message'] = 'Product was added to cart.';
                    $result['data']['checkout'] = true;
            
                } else {
            
                    $result['error'] = false;
                    $result['data']['message'] = 'Product is already in the cart.';
                    $result['data']['checkout'] = false;
                }
            
            } else {
            
                $result['error'] = true;
                $result['data'] = 'Product could not be loaded.';
            }      
                  
        } catch (Exception $ex){
            
            $result['error'] = true;
            $result['data'] = $ex->getMessage();
            
        }
        
        return $result;
    }
    
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }
       
    public function setBillingAddress($customerAddressId)
    {
        $result = array('error'=>true);
        
        try {
            $quote = $this->getQuote();
            $customerAddress = Mage::getModel('customer/address');
            $customerAddress->load($customerAddressId);

            $quoteAddress = Mage::getModel('sales/quote_address');
            
            if ($quote->getBillingAddress() && $quote->getBillingAddress()->getId()) {

                $quoteBillingAddressId = $quote->getBillingAddress()->getId();
                $quoteAddress->load($quoteBillingAddressId);
            } else {
                
                $quoteAddress->setQuote($quote);
            }

            $quoteAddress->importCustomerAddress($customerAddress);
            $quote->setBillingAddress($quoteAddress);  
            $quote->save();  
              
            $result['error'] = false;
            $result['data'] = 'Billing address has been updated.';
            
        } catch (Exception $ex) {
            
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }

        
        return $result;
    }
    
    public function setShippingAddress($customerAddressId)
    {
        $result = array('error'=>true);
        
        try {
            $quote = $this->getQuote();
            $customerAddress = Mage::getModel('customer/address');
            $customerAddress->load($customerAddressId);

            $quoteAddress = Mage::getModel('sales/quote_address');
            
            if ($quote->getShippingAddress() && $quote->getShippingAddress()->getId()) {

                $quoteShippingAddressId = $quote->getShippingAddress()->getId();
                $quoteAddress->load($quoteShippingAddressId);
            } else {
                
                $quoteAddress->setQuote($quote);
            }
            
            $quoteAddress->importCustomerAddress($customerAddress);
            if ($quote->getBillingAddress() && $quote->getBillingAddress()->getCustomerAddressId() == $customerAddressId){
                $quoteAddress->setSameAsBilling(1);
            } else {
                $quoteAddress->setSameAsBilling(0);
            }
            
            $quoteAddress->setCollectShippingRates(true);
            $quote->setShippingAddress($quoteAddress);  
            $quote->save();  
              
            $result['error'] = false;
            $result['data']['message'] = 'Shipping address has been updated.';
            
        } catch (Exception $ex) {
            
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }

        return $result;
    }    
    
    public function setShippingMethod($shippingMethod)
    {
        $result = array('error'=>true);
        
        try {
            $quote = $this->getQuote();
            $quote->getShippingAddress()->setShippingMethod($shippingMethod);
            $quote->save();
        
            $result['error'] = false;
            $result['data']['message'] = 'Shipping method has been updated.';
        
        } catch (Exception $ex) {
        
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }
        
        return $result;        
    }
    
    public function checkout($data)
    {
        $result = array('error'=>true);
        
        try {
        
            $store = Mage::app()->getStore();
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $quote = Mage::getSingleton('checkout/session')->getQuote();  
                                  
            $quote->setCustomerEmail($customer->getEmail());
            
            $billingAddressId = ($data['billing_address_id']) ? $data['billing_address_id'] : $customer->getDefaultBilling();
            
            //set billing addresses
            $customerBillingAddress = Mage::getModel('customer/address')->load($billingAddressId);
            $billingAddress = $quote->getBillingAddress();
            if (!$billingAddress->getCustomerAddressId()){
                $billingAddress->setQuote($quote);
                $quote->setBillingAddress($billingAddress);
            }
            $billingAddress->importCustomerAddress($customerBillingAddress);
                        
            //set shipping addresses
            if (!$quote->isVirtual()){
                
                $shippingAddressId = ($data['shipping_address_id']) ? $data['shipping_address_id'] : $customer->getDefaultShipping();
                $customerShippingAddress = Mage::getModel('customer/address')->load($shippingAddressId);
                $shippingAddress = $quote->getShippingAddress();
                if (!$shippingAddress->getCustomerAddressId()){
                    $shippingAddress->setQuote($quote);
                    $quote->setShippingAddress($shippingAddress);                
                }
                $shippingAddress->importCustomerAddress($customerShippingAddress);

                //set shipping method
                $quoteItems = $quote->getAllVisibleItems();
                foreach ($quoteItems as $quoteItem){
                    if (!$quoteItem->getParentItem()){
                        $shippingAddress->addItem($quoteItem);
                    }
                }            
                $shippingMethod = $data['shipping_method'];
                $shippingAddress->setShippingMethod($shippingMethod);
                $shippingAddress->setCollectShippingRates(true);
                $quote->setShippingAddress($shippingAddress);                
            }

            //set payment method
            $data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
            | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
            | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
            | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
            | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
                        
            $paymentData = $data['payment'];
            $payment = $quote->getPayment();
            $payment->importData($paymentData);
            $payment->setQuote($quote);
            $quote->setPayment($payment);
            
            $quote->reserveOrderId();
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $quote->save();
            
            //convert the quote to order
            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();

            $order = $service->getOrder();
            $profiles = $service->getRecurringPaymentProfiles();
            
            $convertedAt = date('Y-m-d H:i:s');
            $quote->setIsActive(0)->setConvertedAt($convertedAt)->save();
            
            Mage::dispatchEvent(
            'checkout_submit_all_after',
            array('order' => $order, 'quote' => $quote, 'recurring_profiles' => $profiles)
            );            
                                        
            $result['error'] = false;
            $result['data']['increment_id'] = $order->getIncrementId();
             
        } catch(Exception $e){
        
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }        
        
        return $result;
    }
}