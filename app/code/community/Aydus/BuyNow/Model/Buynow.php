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
    
    public function checkout($params)
    {
        $result = array('error'=>true);
        
        try {
        
            //load quote
            $quoteId = (int)$data['quote'];
            $store = Mage::getSingleton('core/store')->load($this->getStoreId());
            $quote = Mage::getModel('sales/quote')->setStore($store)->load($quoteId);
            
            if (!$quote->getIsActive()){
                
                $result['error'] = true;
                $result['data'] = 'Quote is inactive';
                
                return $result;
            }
            
            //set customer email - required
            $email = $data['email'];
            if(!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)){
                
                $result['error'] = true;
                $result['data'] = 'Email is invalid';
                
                return $result;
            }
            $quote->setCustomerEmail($email);
            
            //set addresses
            $quoteAddressCollection = Mage::getModel('sales/quote_address')->getCollection();
            $quoteAddressCollection->addFieldToFilter('quote_id',$quoteId);
            //delete existing addresses - should not have any
            if ($quoteAddressCollection->getSize()>0){
                foreach ($quoteAddressCollection as $address){
                    $address->delete();
                }
            }     
              
            //set billing addresses
            $billingAddressData = $data['billing'];
            $billingAddressData['address_type'] = 'billing';
            $billingRegion = $billingAddressData['region'];
            $billingCountry = $billingAddressData['country'];
            $billingAddressData['country_id'] = $billingCountry;
            $regionModel = Mage::getModel('directory/region');
            if (is_numeric($billingRegion)){
                $regionModel->load($billingRegion);
            } else {
                $regionModel->loadByCode($billingRegion, $billingCountry);
                if (!$regionModel->getId()){
                    $regionModel->loadByName($billingRegion, $billingCountry);
                }
            }
            $billingAddressData['region'] = $regionModel->getCode();
            $billingAddressData['region_id'] = $regionModel->getId();
            if (is_array($billingAddressData['street'])){
                $billingAddressData['street'] = implode("\n", $billingAddressData['street']);
            }       
            $billingAddress = Mage::getModel('sales/quote_address');;
            $billingAddress->setData($billingAddressData);
            $billingAddress->setQuoteId($quoteId);
            $billingAddress->setQuote($quote);
            $billingAddress->save();
            $quote->setBillingAddress($billingAddress);
            
            //set shipping addresses
            $shippingAddressData = $data['shipping'];
            $shippingAddressData['address_type'] = 'shipping';
            $shippingRegion = $shippingAddressData['region'];
            $shippingCountry = $shippingAddressData['country'];
            $shippingAddressData['country_id'] = $shippingCountry;
            $regionModel = Mage::getModel('directory/region');
            if (is_numeric($shippingRegion)){
                $regionModel->load($shippingRegion);
            } else {
                $regionModel->loadByCode($shippingRegion, $shippingCountry);
                if (!$regionModel->getId()){
                    $regionModel->loadByName($shippingRegion, $shippingCountry);
                }
            }            
                        
            $shippingAddressData['region'] = $regionModel->getCode();
            $shippingAddressData['region_id'] = $regionModel->getId();
            if (is_array($shippingAddressData['street'])){
                $shippingAddressData['street'] = implode("\n", $shippingAddressData['street']);
            }
            $shippingAddress = Mage::getModel('sales/quote_address');
            $shippingAddress->setData($shippingAddressData);
            $shippingAddress->setQuoteId($quoteId);
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
            //shipping rates are collected during quote collectTotals;
            //$shippingAddress->collectShippingRates();
            //$shippingAddress->setCollectShippingRates(false);
            $shippingAddress->save();
            $quote->setShippingAddress($shippingAddress);
            
            //set payment method
            $paymentMethod = $data['payment_method'];
            $method = $paymentMethod['method'];
            //delete existing payments - should not have any
            $quotePaymentsCollection = Mage::getModel('sales/quote_payment')->getCollection();
            $quotePaymentsCollection->addFieldToFilter('quote_id',$quoteId);
            if ($quotePaymentsCollection->getSize()>0){
                foreach ($quotePaymentsCollection as $payment){
                    $payment->delete();
                }
            }
            $payment = Mage::getModel('sales/quote_payment');
            $paymentMethodData = array(
                    'quote_id' => $quoteId,
                    'method' => $method,
                    'cc_type' => $paymentMethod['cc_type'],
                    'cc_owner' => $paymentMethod['cc_owner'],
                    'cc_number_enc' => Mage::helper('core')->encrypt($paymentMethod['cc_number']),
                    'cc_last4' => substr($paymentMethod['cc_number'], -4),
                    'cc_cid_enc' => Mage::helper('core')->encrypt($paymentMethod['cc_cid']),
                    'cc_exp_month' => $paymentMethod['cc_exp_month'],
                    'cc_exp_year' => $paymentMethod['cc_exp_year'],                   
            );
            $payment->setData($paymentMethodData);
            $payment->setQuote($quote);
            $payment->save();
            $quote->setPayment($payment);
            
            $quote->reserveOrderId();
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $quote->getShippingAddress()->setShippingMethod($shippingMethod);
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
            $result['data'] = $order->getIncrementId();
             
        } catch(Exception $e){
        
            $result['error'] = true;
            $result['data'] = $e->getMessage();
        }        
        
        
        return $result;
    }
}