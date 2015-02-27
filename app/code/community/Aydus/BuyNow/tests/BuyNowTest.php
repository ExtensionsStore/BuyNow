<?php

/**
 * Fixtures required
 * 
 * 1. Set test customer account credentials into properties
 * 2. Set product data to add to cart
 * 3. Billing agreement payment method is required. Make sure test customer has billing agreement
 *
 * @category    Aydus
 * @package     Aydus_BuyNow
 * @author      Aydus <davidt@aydus.com>
 */

include('bootstrap.php');

class BuyNowTest extends PHPUnit_Framework_TestCase {
    
    protected $_model;
    protected $_baseUrl;
    
    /**
     * Parameters
     */
    protected $_email = 'davidt@aydus.com';
    protected $_password = 'testing123';
    protected $_productId = 394;
    protected $_qty = 1;
    
    public function setUp() 
    {
        $this->_model = Mage::getModel('aydus_buynow/buynow');
        $this->_baseUrl = Mage::getBaseUrl();
    }
    
    /**
     * Make sure customer account exists
     */
    public function testLogin() 
    {
        $result = $this->_model->authenticate($this->_email, $this->_password);
        
        $noError = $result['error'] === false;
                
        $this->assertTrue($noError);
        
        $this->assertEquals($this->_email, $result['data']['email']);
    }
    
    /**
     * Make sure product id and qty is set
     */
    public function testAddToCart()
    {
        $noError = !$result['error'];
        
        $data = array(
                'product' => $this->_productId,
                'qty' => $this->_qty,
        );
        
        $result = $this->_model->addToCart($data);
        $noError = $result['error'] === false;
                
        $this->assertTrue($noError); 
        
        $quote = $this->_model->getQuote();
        
        $items = $quote->getAllItems();
        
        foreach ($items as $item){
            
            $productId = $item->getProductId();
            $qty = $item->getQty();
            
            if ($productId == $this->_productId){
                break;
            }
        }
        
        $this->assertEquals($productId, $this->_productId);
        
        $this->assertEquals($qty, $this->_qty);
        
    }
    
    /**
     * 
     */
    public function testCheckout()
    {
        
    }
    

}
