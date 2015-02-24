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

            return $this->__('Select your shipping and billing.');
            
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

            $select->addOption('', Mage::helper('checkout')->__('New Address'));

            return $select->getHtml();
        }
        return '';
    }    
    
    //'shipping_method', 'shipping_method', 'shipping'
    
    public function getShippingMethodsHtmlSelect($_methods = null, $fieldId = 'shipping_method', $fieldName = 'shipping_method', $fieldClass = 'shipping'){
        
        if (!$_methods){
            $_methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
        }
        
        $_shippingHtml = '<select name="' . $fieldName . '" id="' . $fieldId . '" class="' . $fieldClass . '">';
        foreach($_methods as $_carrierCode => $_carrier){
            if($_method = $_carrier->getAllowedMethods())  {
                if(!$_title = Mage::getStoreConfig('carriers/' . $_carrierCode . ' /title')) {
                    $_title = $_carrierCode;
                }
                $_shippingHtml .= '<optgroup label="' . $_title . '">';
                foreach($_method as $_mcode => $_m){
                    $_code = $_carrierCode . '_' . $_mcode;
                    $_shippingHtml .= '<option value="' . $_code . '">' . $_m . '</option>';
                }
                $_shippingHtml .= '</optgroup>';
            }
        }
        $_shippingHtml .= '</select>';
        
        return $_shippingHtml;
    }
    
   /* public function getShippingMethodsHtmlSelect()
    {
        $select = $this->getLayout()->createBlock('core/html_select')
        ->setName($type.'_address_id')
        ->setId($type.'-address-select')
        ->setClass('required-entry address-select')
        ->setValue($addressId)
        ->setOptions($options);
        
        $select->addOption('', Mage::helper('checkout')->__('New Address'));
        
        return $select->getHtml();        
    }*/
    
    public function hasBillingAgreement()
    {
        $collection = Mage::getModel('sales/billing_agreement')->getAvailableCustomerBillingAgreements(
                $this->getCustomer()->getId()
        );

        return $collection->getSize();
    }
    
    public function getBillingAgreementsHtmlSelect() 
    {
        $options = array();
        $collection = Mage::getModel('sales/billing_agreement')->getAvailableCustomerBillingAgreements(
                $this->getCustomer()->getId()
        );

        foreach ($collection as $item) {
            $options[] = array('value'=>$item->getId(), 'label' => $item->getReferenceId());
        }
        
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName('payment[ba_agreement_id]')
            ->setId('ba_agreement_id')
            ->setClass('required-entry')
            ->setValue(1)
            ->setOptions($options);

        return $select->getHtml();        
    }
}