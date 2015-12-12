<?php

class PwaprestaPwaorderModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;

	public function __construct()
	{
		parent::__construct();

		$this->context = Context::getContext();
	}

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		if (Tools::isSubmit('action'))
		{
			switch(Tools::getValue('action'))
			{
				case 'pwa_order':
					$this->pwa_order($_GET);
					break;
			}
		}
	}

	public function pwa_order($data)
	{
		$prefix    = _DB_PREFIX_;
		
		$pwa_order_status = $data['amznPmtsPaymentStatus'];
		$pwa_order_id = $data['amznPmtsOrderIds'];
		
		if(isset($data['refresh']) && $data['refresh'] != '')
		$refresh = $data['refresh'];
		else
		$refresh = 'yes';
		
		if(isset($data['CartId']) && $data['CartId'] > 0)
		$CartId = $data['CartId'];
		else
		$CartId = 0;
		
		$tablename = $prefix.'pwa_orders';
		$sql  = 'SELECT * from `'.$tablename.'` where `amazon_order_id` = "'.$pwa_order_id.'" ';
		$result = Db::getInstance()->ExecuteS($sql);
		
		if(empty($result))
		{
			$tablename = $prefix.'orders';
			$date      = date('Y-m-d H:i:s');
		
			$sql  = 'INSERT into `'.$tablename.'`  (`current_state` , `id_cart` ,  `payment` , `module` , `date_add` ) VALUES( 99, "'.$CartId.'", "Pay with Amazon", "pwapresta", "'.$date.'" )' ;
			Db::getInstance()->Execute($sql);
			
			$order_id = Db::getInstance()->Insert_ID();
			
			$tablename = $prefix.'pwa_orders';
			$sql  = 'INSERT into `'.$tablename.'`  (`prestashop_order_id` , `amazon_order_id` ) VALUES( "'.$order_id.'", "'.$pwa_order_id.'" )' ;
			Db::getInstance()->Execute($sql);
	    }
	    else
	    {
			$order_id = $result[0]['prestashop_order_id'];
		}
		
		$products = $this->context->cart->getProducts();
		foreach ($products as $product) {
			
		  if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice')  
			$address_id = (int)$this->id_address_invoice;    
		  else    
			$address_id = (int)$product['id_address_delivery']; // Get delivery address of the product from the cart    
		 
		  if (!Address::addressExists($address_id))    
			$address_id = null;

		  $virtual_context = Context::getContext()->cloneContext();
		  $virtual_context->cart = $this->context->cart;

		  $product_price = Product::getPriceStatic(  
				(int)$product['id_product'],   
				false,   
				(int)$product['id_product_attribute'],    
				2,
				null,  
				false, 
				true,  
				$product['quantity'],    
				false,    
				((int)$this->context->cart->id_customer ? (int)$this->context->cart->id_customer : null),    
				(int)$this->context->cart->id,    
				((int)$address_id ? (int)$address_id : null),    
				$null,    
				true,    
				true,    
				$virtual_context    
			  );  
          
          
			$tablename = $prefix.'pwa_order_products';
			$sql       = 'INSERT into `'.$tablename.'`  (`id_cart` , `id_product` , `id_product_attribute` , `quantity` , `amount` , `amount_excl` , `sku` , `title` ) VALUES( "'.$this->context->cart->id.'", "'.$product["id_product"].'",  "'.$product["id_product_attribute"].'",  "'.$product["quantity"].'", "'.$product["price_wt"].'", "'.$product_price.'", "'.$product["reference"].'", "'.$product["name"].'" )' ;
			Db::getInstance()->Execute($sql);
			
			//$this->context->cart->deleteProduct($product["id_product"]);
		}
		
		$this->context->smarty->assign(array(
			'order_id' => $order_id,
			'pwa_order_id' => $pwa_order_id,
			'pwa_order_status' => $pwa_order_status,
			'refresh' => $refresh
		));
		
		$this->setTemplate('pwa_order.tpl'); 
	}

}
