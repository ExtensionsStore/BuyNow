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

            return $this->__('Select your shipping address and billing agreement.');
            
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
                //->setExtraParams('onchange="'.$type.'.newAddress(!this.value)"')
                ->setValue($addressId)
                ->setOptions($options);

            $select->addOption('', Mage::helper('checkout')->__('New Address'));

            return $select->getHtml();
        }
        return '';
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