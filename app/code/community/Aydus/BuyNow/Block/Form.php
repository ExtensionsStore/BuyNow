<?php

/**
 * BuyNow form
 *
 * @category   Aydus
 * @package    Aydus_BuyNow
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_BuyNow_Block_Form extends Mage_Checkout_Block_Onepage_Abstract
{
    public function getLoginUrl()
    {
        return $this->getUrl('buynow/index/login');
    }
    
    public function getCheckoutUrl()
    {
        return $this->getUrl('buynow/index/checkout');
    }    
    
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }
    
    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }    
    
    public function getFormKeyHtml()
    {
        if ($this->getBlockHtml('formkey')){
            return $this->getBlockHtml('formkey');
        } else {
            return '<input type="hidden" name="form_key" value="'.Mage::getSingleton('core/session')->getFormKey().'"/>';
        }
    }
    
    /**
     * Retrieve checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }

    /**
     * Retrieve sales quote model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if (empty($this->_quote)) {
            $this->_quote = $this->getCheckout()->getQuote();
        }
        return $this->_quote;
    }    
    
    public function isShippingRequired()
    {
        return !$this->getQuote()->isVirtual();
    }
    
    public function getCheckoutFormInstructions()
    {
        if ($this->isShippingRequired()){

            return $this->__('Select your shipping and billing agreement.');
            
        } else {
            
            return $this->__('Select billing agreement.');
        }
    }
        
    public function getAddressesHtmlSelect($type)
    {
        $quote = $this->getQuote();
        $addressId = null;
        $options = array();
        $options[] = array('value'=>'', 'label' => $this->__('-- Select '.ucfirst($type).' Address --'));
        
        foreach ($this->getCustomer()->getAddresses() as $address) {
            $options[] = array(
                'value' => $address->getId(),
                'label' => $address->format('oneline')
            );
        }
                
        if ($type=='billing') {
            
            if ($quote->getBillingAddress() && $quote->getBillingAddress()->getCustomerAddressId()){
                
                $addressId = $quote->getBillingAddress()->getCustomerAddressId();
            } else {
                $addressId = $this->getCustomer()->getPrimaryBillingAddress()->getId();
            }
            
        } else {
            if ($quote->getShippingAddress() && $quote->getShippingAddress()->getCustomerAddressId()){
                $addressId = $quote->getShippingAddress()->getCustomerAddressId();
            } else {
                $addressId = $this->getCustomer()->getPrimaryShippingAddress()->getId();
            }           
        }

        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName($type.'_address_id')
            ->setId($type.'-address-select')
            ->setClass('required-entry address-select')
            ->setValue($addressId)
            ->setOptions($options);

        return $select->getHtml();
        
    }    
        
    public function getShippingMethodsHtmlSelect(){
        
        $quote = $this->getQuote();
        $quoteId = $quote->getId();
        
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getCustomerAddressId()){
            $customerAddress = $this->getCustomer()->getPrimaryShippingAddress();
            $shippingAddress->importCustomerAddress($customerAddress);
        }
        $shippingAddress->setCollectShippingRates(true)->save();
        $shippingAddress->collectShippingRates();
        
        $groups = $shippingAddress->getGroupedAllShippingRates();
        
        $options[] = array('value'=>'', 'label' => $this->__('-- Select Shipping Method --'));
        
        $store = $quote->getStore();
        $taxHelper = $this->helper('tax');
        $includingTax = $taxHelper->displayShippingPriceIncludingTax();
                
        foreach ($groups as $carrier=>$rates){
            
            foreach ($rates as $rate ){
                $options[$carrier]['label'] = $rate->getCarrierTitle();
                $price =  $taxHelper->getShippingPrice($rate->getPrice(), $includingTax, $shippingAddress);
                $value = $rate->getCode();
                $label = $rate->getMethodTitle() . ' - ' . $store->convertPrice($price, true, false);
                $options[$carrier]['value'][] = array('value'=> $value, 'label' => $label );
            }
        }
        
        $select = $this->getLayout()->createBlock('core/html_select')
        ->setName('shipping_method')
        ->setId('shipping_method_select')
        ->setClass('required-entry')
        ->setValue($shippingAddress->getShippingMethod())
        ->setOptions($options);
                
        return $select->getHtml();   
    }
    
    public function getPaymentMethod()
    {
        if ($this->getBillingAgreements()->getSize() == 1){
            $collection = $this->getBillingAgreements();
            $item = $collection->getFirstItem();
            return $item->getMethodCode();
        }
    }
    
    public function getBillingAgreements()
    {
        $collection = Mage::getModel('sales/billing_agreement')->getAvailableCustomerBillingAgreements(
                $this->getCustomer()->getId()
        );

        return $collection;
    }
    
    public function hasBillingAgreement()
    {
        return $this->getBillingAgreements()->getSize();
    }
    
    public function getBillingAgreementsHtmlSelect() 
    {
        $quote = $this->getQuote();
        $collection = $this->getBillingAgreements();
        $options[] = array('value'=>'', 'label' => $this->__('-- Select Billing Agreement --'));
        
        $payment = $quote->getPayment();
        $method = $payment->getMethod();
        $additionalInformation = $payment->getAdditionalInformation();
        if (is_numeric(strpos($method,'_billing_agreement')) && is_array($additionalInformation)){
            $value = $additionalInformation['ba_agreement_id'];
        }
        
        if (!$value){
            $item = $collection->getFirstItem();
            $value = $item->getId();
        }
        
        foreach ($collection as $item) {
            $value = $item->getId();
            $methodCode = $item->getMethodCode();
            $method = str_replace('_',' ',$methodCode);
            $label = ucwords($method).' - '.$item->getReferenceId();
            $options[] = array('value'=>$value, 'label' => $label, 'params'=>array('method'=>$methodCode));
        }
        
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName('payment[ba_agreement_id]')
            ->setId('ba_agreement_id')
            ->setClass('required-entry')
            ->setValue($value)
            ->setOptions($options);

        return $select->getHtml();        
    }
}