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
            $customerAddress = Mage::getModel('customer/address')->load($billingAddressId);
            $billingAddress = $quote->getBillingAddress();
            if (!$billingAddress->getId()){
                $billingAddress = Mage::getModel('sales/quote_address');
            }
            $billingAddress->setQuote($quote);
            $billingAddress->setData($customerAddress->getData());
            $billingAddress->save();
            $quote->setBillingAddress($billingAddress);
            
            //set shipping addresses
            if (!$quote->isVirtual()){
                $shippingAddress = Mage::getModel('sales/quote_address')->load($customer->getDefaultShipping());
                $shippingAddress->setQuote($quote);

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
                $shippingAddress->save();
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
            $payment->save();
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