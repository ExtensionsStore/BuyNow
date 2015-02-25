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
    
    /**
     * Return Sales Quote Address model (shipping address)
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getAddress()
    {
        if (is_null($this->_address)) {
            if ($this->isCustomerLoggedIn()) {
                $this->_address = $this->getQuote()->getShippingAddress();
            } else {
                $this->_address = Mage::getModel('sales/quote_address');
            }
        }

        return $this->_address;
    }    
    
    public function getAddressesHtmlSelect($type)
    {
        if ($this->isCustomerLoggedIn()) {
            $options = array();
            $options[] = array('value'=>'', 'label' => $this->__('-- Select '.ucfirst($type).' Address --'));
            
            foreach ($this->getCustomer()->getAddresses() as $address) {
                $options[] = array(
                    'value' => $address->getId(),
                    'label' => $address->format('oneline')
                );
            }

            $addressId = $this->getAddress()->getCustomerAddressId();
            if (empty($addressId)) {
                if ($type=='billing') {
                    $address = $this->getCustomer()->getPrimaryBillingAddress();
                } else {
                    $address = $this->getCustomer()->getPrimaryShippingAddress();
                }
                if ($address) {
                    $addressId = $address->getId();
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
        return '';
    }    
        
    public function getShippingMethodsHtmlSelect(){
        
        $quote = $this->getQuote();
        $store = $quote->getStore();
        $address = $quote->getShippingAddress();
        $address->collectShippingRates()->save();
        
        $groups = $address->getGroupedAllShippingRates();
        
        $options[] = array('value'=>'', 'label' => $this->__('-- Select Shipping Method --'));
        $taxHelper = $this->helper('tax');
        $includingTax = $taxHelper->displayShippingPriceIncludingTax();
                
        foreach ($groups as $carrier=>$rates){
            
            foreach ($rates as $rate ){
                $options[$carrier]['label'] = $rate->getCarrierTitle();
                $price =  $taxHelper->getShippingPrice($rate->getPrice(), $includingTax, $address);
                $value = $rate->getCode();
                $label = $rate->getMethodTitle() . ' - ' . $store->convertPrice($price, true, false);
                $options[$carrier]['value'][] = array('value'=> $value, 'label' => $label );
            }
        }
        
        $select = $this->getLayout()->createBlock('core/html_select')
        ->setName('shipping_method')
        ->setId('shipping_method_select')
        ->setClass('required-entry')
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
        $collection = $this->getBillingAgreements();
        $options[] = array('value'=>'', 'label' => $this->__('-- Select Billing Agreement --'));
        
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
            //->setValue(1)
            ->setOptions($options);

        return $select->getHtml();        
    }
}