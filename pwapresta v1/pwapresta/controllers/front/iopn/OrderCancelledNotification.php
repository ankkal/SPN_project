<?php

class OrderCancelledNotification {

  
	public function __construct() {
		  
	}
	
    public function cancel_order($data,$iopn_record_id) {
		
		$prefix    = _DB_PREFIX_;
		
		$xml = simplexml_load_string($data);
		
		$NotificationReferenceId = $xml->NotificationReferenceId;
		$OrderChannel = $xml->ProcessedOrder->OrderChannel;
		$AmazonOrderID = (string)$xml->ProcessedOrder->AmazonOrderID;
		$OrderDate = $xml->ProcessedOrder->OrderDate;
		
		$param['NotificationReferenceId'] = $NotificationReferenceId;
		$param['AmazonOrderID'] = $AmazonOrderID;
		$param['iopn_record_id'] = $iopn_record_id;
		
		$sql = 'select * from `'.$prefix.'pwa_orders` where amazon_order_id = "'.$AmazonOrderID.'" ';
		$results = Db::getInstance()->ExecuteS($sql);
		
		if(empty($results)) 
		{
			$sql = 'select * from `'.$prefix.'pwa_iopn_records` where amazon_order_id = "'.$AmazonOrderID.'" and notification_reference_id = "'.$NotificationReferenceId.'" and status = "Rejected" ';
			$records = Db::getInstance()->ExecuteS($sql);
			
			if(!empty($records)) 
			{
				$param['Status'] = 'Accepted';
				$this->update_request($param);
				
				$tablename = $prefix.'orders';
				$date      = date('Y-m-d H:i:s');
			
				$sql  = 'INSERT into `'.$tablename.'`  (`current_state` , `payment` , `module` , `date_add` ) VALUES( 99, "Pay with Amazon", "pwapresta", "'.$date.'" )' ;
				Db::getInstance()->Execute($sql);
				
				$order_id = Db::getInstance()->Insert_ID();
				
				$tablename = $prefix.'pwa_orders';
				$sql  = 'INSERT into `'.$tablename.'`  (`prestashop_order_id` , `amazon_order_id` ) VALUES( "'.$order_id.'", "'.$AmazonOrderID.'" )' ;
				Db::getInstance()->Execute($sql);
			
				$this->update_order_detail($order_id , $data);
			}
			else 
			{	
				$param['Status'] = 'Rejected';
				$this->update_request($param);
		
				header('HTTP/1.1 503 SERVICE_UNAVAILABLE');
				exit;
			}
		} 
		else 
		{	
			$param['Status'] = 'Accepted';
			$this->update_request($param);
			
			$order_id = $results[0]['prestashop_order_id'];
			$this->update_order_detail($order_id , $data);
		}
	}
	
	
	public function update_order_detail($order_id , $data) {
			
			$prefix    = _DB_PREFIX_;
			$tablename = $prefix.'pwa_orders';
			
			$sql = 'select * from `'.$tablename.'` where prestashop_order_id = '.$order_id.' ';
			$results = Db::getInstance()->ExecuteS($sql);
			
			$non_received = $results[0]['_non_received'];
			if($non_received == 0)
			{
				// Check and generate IOPN dump file 
				$iopn_dump = Configuration::get('PWAPRESTA_PWAPRESTA_IOPN_DUMP_CHECK');
				if( $iopn_dump == '1' ){
					$dir = Configuration::get('PWAPRESTA_PWAPRESTA_IOPN_DUMP_URL');
					if ( !file_exists($dir) && !is_dir($dir) ) {
						mkdir($dir, 0777);
					}

					$filename = $dir.$order_id.'_iopn_non';
				 	$myfile = fopen($filename, "w");
				 	fwrite($myfile, $data);
				 	fclose($myfile);
				}
				
				$id = $results[0]['id'];
				$sql  = 'UPDATE `'.$tablename.'` set `_non_received` = 1 where `id` = '.$id.' ';
				Db::getInstance()->Execute($sql);
				
				$this->update_cart_by_xml($order_id,$data);
			}
			
			$tablename = $prefix.'orders';
			$sql  = 'SELECT * from  `'.$tablename.'` where `id_order` = "'.$order_id.'" ';
			$result = Db::getInstance()->ExecuteS($sql);
				
			if($result[0]['current_state'] != 6)
			{
				$this->update_stock($order_id,$data);
				
				$tablename = $prefix.'orders';
				$sql  = 'UPDATE `'.$tablename.'` set `current_state` = 6 where `id_order` = "'.$order_id.'"';
				Db::getInstance()->Execute($sql);
				
				// Set the order status
				$history = new OrderHistory();
				$history->id_order = $order_id;
				$history->changeIdOrderState(6, $order_id, true);
				$history->addWithemail(true , array());
			}
			 
			// Respond to the Request
			header('HTTP/1.1 200 OK');
	
	}
	
	
	public function update_cart_by_xml($order_id,$data){
		
		$xml = simplexml_load_string($data);
		
		$ClientRequestId = 0;
		foreach($xml->ProcessedOrder->ProcessedOrderItems->ProcessedOrderItem as $item) {
			$ClientRequestId = $item->ClientRequestId;
			break;
	    }
	    
	    if(strlen($ClientRequestId) < 9)
	    {
			$this->update_cart_by_site_xml($order_id,$data);
		}
		else
		{
			$this->update_cart_by_junglee_xml($order_id,$data);
		}
	}
	
	
	/*
	 * Update the Stock by using Notification XML Data
	 */
	public function update_stock($order_id,$data){
		  $xml = simplexml_load_string($data);
		  $prefix    = _DB_PREFIX_;
		  
		  foreach($xml->ProcessedOrder->ProcessedOrderItems->ProcessedOrderItem as $item) {
					
				$SKU = (string)$item->SKU;  
				if($SKU == 'wrapping_fee')
				{
					//Nothing
				}
				else
				{
					$product_id = (int)$item->ItemCustomData->Item_product_id;
					$product_attribute_id = (int)$item->ItemCustomData->Item_attr_product_id;
					$Quantity = (int)$item->Quantity; 
					
					if (Pack::isPack($product_id))
					{
						$product = new Product((int)$product_id);
						
						if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2)
						{
							$products_pack = Pack::getItems($id_product, (int)Configuration::get('PS_LANG_DEFAULT'));
							foreach ($products_pack as $product_pack)
							{
								
								$sql = 'UPDATE `'.$prefix.'stock_available` set
								`quantity` = `quantity` + '.$product_pack->pack_quantity*$Quantity.'
								where `id_product` = '.$product_pack->id.' and
								`id_product_attribute` = 0
										';
								Db::getInstance()->Execute($sql);
								
								if($product_pack->id_pack_product_attribute > 0)
								{
									$sql = 'UPDATE `'.$prefix.'stock_available` set
											`quantity` = `quantity` + '.$product_pack->pack_quantity*$Quantity.'
											where `id_product` = '.$product_pack->id.' and
											`id_product_attribute` = '.$product_pack->id_pack_product_attribute.'
											';
									Db::getInstance()->Execute($sql);
								}
						
							}
						}
						
						if ($product->pack_stock_type == 0 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 0 || Configuration::get('PS_PACK_STOCK_TYPE') == 2)))
						{
							$sql = 'UPDATE `'.$prefix.'stock_available` set
								`quantity` = `quantity` + '.$Quantity.'
								where `id_product` = '.$product_id.' and
								`id_product_attribute` = 0
								';
							Db::getInstance()->Execute($sql);
							
							if($product_attribute_id > 0)
							{
								$sql = 'UPDATE `'.$prefix.'stock_available` set
										`quantity` = `quantity` + '.$Quantity.'
										where `id_product` = '.$product_id.' and
										`id_product_attribute` = '.$product_attribute_id.'
										';
								Db::getInstance()->Execute($sql);
							}
							
							$date = date('Y-m-d');
							$sql = 'UPDATE `'.$prefix.'product_sale` set
									`quantity` = `quantity` - '.$Quantity.',
									`sale_nbr` = `sale_nbr` - '.$Quantity.',
									`date_upd` = '.$date.'
									where `id_product` = '.$product_id.'
									';
							Db::getInstance()->Execute($sql);	
						}
					}
					else
					{
						$sql = 'UPDATE `'.$prefix.'stock_available` set
								`quantity` = `quantity` + '.$Quantity.'
								where `id_product` = '.$product_id.' and
								`id_product_attribute` = 0
								';
						Db::getInstance()->Execute($sql);
						
						if($product_attribute_id > 0)
						{
							$sql = 'UPDATE `'.$prefix.'stock_available` set
									`quantity` = `quantity` + '.$Quantity.'
									where `id_product` = '.$product_id.' and
									`id_product_attribute` = '.$product_attribute_id.'
									';
							Db::getInstance()->Execute($sql);
						}
						
						$date = date('Y-m-d');
						$sql = 'UPDATE `'.$prefix.'product_sale` set
								`quantity` = `quantity` - '.$Quantity.',
								`sale_nbr` = `sale_nbr` - '.$Quantity.',
								`date_upd` = '.$date.'
								where `id_product` = '.$product_id.'
								';
						Db::getInstance()->Execute($sql);
					}
			    }
			  $i++;
		 }
	}
	
	
	/*
	 * Update the order detail by using Notification XML Data
	 */
	public function update_cart_by_site_xml($order_id,$data){
 
     $xml = simplexml_load_string($data);
       
      $prefix    = _DB_PREFIX_;
	  $tablename = $prefix.'orders';
			
      $total_amount = 0;
      $total_principal = 0;
      $shipping_amount = 0;
      $total_promo = 0;
      $ClientRequestId = 0;
      $WrappingAmount = 0;
     
      $gift = 0;
	  $gift_message = '&nbsp;';
	  foreach($xml->ProcessedOrder->ProcessedOrderItems->ProcessedOrderItem as $item) {
			
			$SKU = (string)$item->SKU;  
			
			if($SKU == 'wrapping_fee')
			{
				$WrappingAmount = (float)$item->Price->Amount;
				
				$gift = 1;
				$gift_message = (string)$item->Description;
			}
			
			$Title = (string)$item->Title;  
			$Amount = (float)$item->Price->Amount;
			$ClientRequestId = (int)$item->ClientRequestId;
			$other_promo = 0;
			foreach($item->ItemCharges->Component as $amount_type) {
				  $item_charge_type = (string)$amount_type->Type; 
				  if($item_charge_type == 'Principal') {
					$principal = (string)$amount_type->Charge->Amount;
				  }
				  if($item_charge_type == 'Shipping') {
					$Shipping = (string)$amount_type->Charge->Amount;
				  }
				  if($item_charge_type == 'PrincipalPromo') {
					$principal_promo = (string)$amount_type->Charge->Amount;
				  }
				  if($item_charge_type == 'ShippingPromo') {
					$shipping_promo = (string)$amount_type->Charge->Amount;
				  }
				  if($item_charge_type == 'OtherPromo') {
					$other_promo = (string)$amount_type->Charge->Amount;
				  }
			}
				
			$CurrencyCode = (string)$item->Price->CurrencyCode; 
			$Quantity = (int)$item->Quantity; 
			
			
			/*
			 * Total Item Charge = (Principal - PrincipalPromo) + (Shipping - ShippingPromo) + Tax + ShippingTax
			 */
			$total_principal += $principal;
			
			$total_amount += ($principal - $principal_promo) + ($Shipping - $shipping_promo) ;
			
			$shipping_amount += $Shipping;

			$total_promo += $principal_promo + $shipping_promo + $other_promo;
		   
	  }
	  $total_principal = $total_principal - $WrappingAmount;
	  

      $ShippingServiceLevel = (string)$xml->ProcessedOrder->ShippingServiceLevel;
     
      $sql  = 'UPDATE `'.$prefix.'pwa_orders` set `shipping_service` = "'.$ShippingServiceLevel.'" , `order_type` = "site" where `prestashop_order_id` = "'.$order_id.'" ';
	  Db::getInstance()->Execute($sql);
      
      $id_cart = $ClientRequestId;
      
    
      $this->context = Context::getContext();
      $this->context->cart = new Cart($id_cart);
	  if((int)$this->context->cart->id_customer == 0)
	  {
		  $email = (string)$xml->ProcessedOrder->BuyerInfo->BuyerEmailAddress;
		  
		  $sql = 'SELECT * from `'.$prefix.'customer` where email = "'.$email.'" ';
		  $results = Db::getInstance()->ExecuteS($sql);
		  if(empty($results))
		  {
			$name = (string)$xml->ProcessedOrder->BuyerInfo->BuyerName;
			$name_arr = explode(' ',$name);
			if(count($name_arr) > 1)
			{
				$firstname = '';
				for($i=0;$i<=count($name_arr)-2;$i++)
				{
					$firstname = $firstname.' '.$name_arr[$i];
				}
				$lastname = $name_arr[count($name_arr)-1];
			}
			else
			{
				$firstname = $name;
				$lastname = '.';
			}
		  
			$password = Tools::passwdGen();
			$customer = new Customer();
			$customer->firstname = trim($firstname);
			$customer->lastname = $lastname;
			$customer->email = (string)$xml->ProcessedOrder->BuyerInfo->BuyerEmailAddress;
			$customer->passwd = md5($password);
			$customer->active = 1;
			
			if (Configuration::get('PS_GUEST_CHECKOUT_ENABLED'))
			$customer->is_guest = 1;
			else
			$customer->is_guest = 0;
			
			$customer->add();
			$customer_id = $customer->id;
			
			if (Configuration::get('PS_CUSTOMER_CREATION_EMAIL') && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED'))
			{
				Mail::Send(
					$this->context->language->id,
					'account',
					Mail::l('Welcome!'),
					array(
						'{firstname}' => $customer->firstname,
						'{lastname}' => $customer->lastname,
						'{email}' => $customer->email,
						'{passwd}' => $password
					),
					$customer->email,
					$customer->firstname.' '.$customer->lastname
				);
			}
		 }
		 else
		 {
			$customer_id = $results[0]['id_customer'];
		 }
			
			$id_country = Country::getByIso((string)$xml->ProcessedOrder->ShippingAddress->CountryCode);
			if($id_country == 0 || $id_country == '')
			{
				$id_country = 110;
			}
			
			$name = (string)$xml->ProcessedOrder->ShippingAddress->Name;
			$name_arr = explode(' ',$name);
			if(count($name_arr) > 1)
			{
				$firstname = '';
				for($i=0;$i<=count($name_arr)-2;$i++)
				{
					$firstname = $firstname.' '.$name_arr[$i];
				}
				$lastname = $name_arr[count($name_arr)-1];
			}
			else
			{
				$firstname = $name;
				$lastname = '.';
			}
			
			$address = new Address();
			$address->id_country = $id_country;
			$address->id_state = 0;
			$address->id_customer = $customer_id;
			$address->alias = 'My Address';
			$address->firstname = trim($firstname);
			$address->lastname = $lastname;
			$address->address1 = (string)$xml->ProcessedOrder->ShippingAddress->AddressFieldOne;
			$address->address2 = (string)$xml->ProcessedOrder->ShippingAddress->AddressFieldTwo;
			$address->postcode = (string)$xml->ProcessedOrder->ShippingAddress->PostalCode;
			$address->city = (string)$xml->ProcessedOrder->ShippingAddress->City.' '.(string)$xml->ProcessedOrder->ShippingAddress->State;
			$address->active = 1;
			$address->add();
			$address_id = $address->id;
			
			$cart = new Cart($id_cart);
			$cart->id_customer = $customer_id;
			$cart->id_address_delivery = $address_id;
			$cart->id_address_invoice  = $address_id;
			//$cart->id_guest = $customer_id;
			$cart->update();
	  }
	  
	  $this->context->cart = new Cart($id_cart);
	  $this->context->customer = new Customer($this->context->cart->id_customer);
	  $id_order_state = 99;
	 
	  // The tax cart is loaded before the customer so re-cache the tax calculation method
	  //$this->context->cart->setTaxCalculationMethod();

	  $this->context->language = new Language($this->context->cart->id_lang);
	  $this->context->shop = ($shop ? $shop : new Shop($this->context->cart->id_shop));
	  
	  $id_currency = $currency_special ? (int)$currency_special : (int)$this->context->cart->id_currency;
	  $this->context->currency = new Currency($id_currency, null, $this->context->shop->id);
	 	
	  $reference = Order::generateReference();

	  $cart_rules = $this->context->cart->getCartRules();
	 	 
	 
			$order = new Order();
			$order->id = $order_id;
			$order->id_customer = (int)$this->context->cart->id_customer;
			$order->id_address_invoice = (int)$this->context->cart->id_address_invoice;
			$order->id_shop = (int)$this->context->shop->id;
			$order->id_shop_group = (int)$this->context->shop->id_shop_group;
			
			$carrier = null;
			if ((int)$this->context->cart->id_carrier)
			{
				$carrier = 'yes';
				$id_carrier = (int)$this->context->cart->id_carrier;
			}
			else
			{
				$sql = 'SELECT id_carrier from  `'.$prefix.'carrier` where `active` = 1 and `deleted` = 0 limit 0,1';
				$result = Db::getInstance()->ExecuteS($sql);
				$id_carrier = $result[0]['id_carrier'];
			}
	
			
			 $sql  = 'UPDATE `'.$tablename.'` set 
			  `id_customer` = '.(int)$this->context->cart->id_customer.',
			  `id_carrier` = '.$id_carrier.',
			  `id_address_invoice` = '.(int)$this->context->cart->id_address_invoice.',
			  `id_address_delivery` = '.(int)$this->context->cart->id_address_delivery.',
			  `id_currency` = '.$this->context->currency->id.',
			  `id_lang` = '.(int)$this->context->cart->id_lang.',
			  `id_cart` = '.(int)$this->context->cart->id.',
			  `reference` = "'.$reference.'",
			  `id_shop` = '.(int)$this->context->shop->id.',
			  `id_shop_group` = '.(int)$this->context->shop->id_shop_group.',
			  `secure_key` = "'.$this->context->cart->secure_key.'",
			  `conversion_rate` = '.$this->context->currency->conversion_rate.',
			  
			  `total_paid` = '.$total_amount.',
			  `total_paid_tax_incl` = '.$total_amount.',
			  `total_paid_tax_excl` = '.$total_amount.',
			  `total_paid_real` = 0,
			 
			  `total_shipping` = '.$shipping_amount.',
			  `total_shipping_tax_incl` = '.$shipping_amount.',
			  `total_shipping_tax_excl` = '.$shipping_amount.',
			  
			  `total_discounts` = '.(float)$total_promo.',
			  `total_discounts_tax_incl` = '.(float)$total_promo.',
			  `total_discounts_tax_excl` = '.(float)$total_promo.',
			  
			  `total_products` = '.$total_principal.',
			  `total_products_wt` = '.$total_principal.',
			  
			  `total_wrapping_tax_incl` = '.$WrappingAmount.',
			  `total_wrapping_tax_excl` = '.$WrappingAmount.',
			  `total_wrapping` = '.$WrappingAmount.',
			 
			  `gift` = '.$gift.',
			  `gift_message` = "'.$gift_message.'",
			  
			  `invoice_date` = "0000-00-00 00:00:00",
			  `delivery_date` = "0000-00-00 00:00:00"
			  where `id_order` = '.$order_id.' ' ;
			  
			  // `round_mode` = '.Configuration::get('PS_PRICE_ROUND_MODE').', 
			  //`carrier_tax_rate` = '.$carrier->getTaxesRate(new Address($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')})).' , 
			  
			  Db::getInstance()->Execute($sql);
			 
			   
			  $acknowledge_arr = array();
			  $i = 0;
			  foreach($xml->ProcessedOrder->ProcessedOrderItems->ProcessedOrderItem as $item) {
					
				$SKU = (string)$item->SKU;  
				if($SKU == 'wrapping_fee')
				{
					//Nothing
				}
				else
				{
					$AmazonOrderItemCode = (string)$item->AmazonOrderItemCode;
					$Title = (string)$item->Title;  
					$Amount = (float)$item->Price->Amount;
					$product_id = (int)$item->ItemCustomData->Item_product_id;
					$product_attribute_id = (int)$item->ItemCustomData->Item_attr_product_id;
					$Item_price_excl_tax = $item->ItemCustomData->Item_price_excl_tax;
					$A_Name = '';
					foreach($item->ItemCustomData->Item_attribute as $attribute){
						if($A_Name == ''){
							$A_Name = $attribute->Attribute_name.' : '.$attribute->Attribute_val;
						}else{
							$A_Name = $A_Name.', '.$attribute->Attribute_name.' : '.$attribute->Attribute_val;
						}
					}
					
					$acknowledge_arr['items'][$i]['AmazonOrderItemCode'] = $AmazonOrderItemCode;
					$acknowledge_arr['items'][$i]['product_id'] = $product_id;
					
					
					$Title = $Title.' - '.$A_Name;
					$CurrencyCode = (string)$item->Price->CurrencyCode; 
					$Quantity = (int)$item->Quantity; 
					$other_promo = 0;
					foreach($item->ItemCharges->Component as $amount_type) {
						  $item_charge_type = (string)$amount_type->Type; 
						  if($item_charge_type == 'Principal') {
							$principal = (string)$amount_type->Charge->Amount;
						  }
						  if($item_charge_type == 'Shipping') {
							$Shipping = (string)$amount_type->Charge->Amount;
						  }
						  if($item_charge_type == 'PrincipalPromo') {
							$principal_promo = (string)$amount_type->Charge->Amount;
						  }
						  if($item_charge_type == 'ShippingPromo') {
							$shipping_promo = (string)$amount_type->Charge->Amount;
						  }
						  if($item_charge_type == 'OtherPromo') {
							$other_promo = (string)$amount_type->Charge->Amount;
						  }
					}
					
					 $sql = 'INSERT into `'.$prefix.'order_detail` set
							`id_order` = '.$order_id.',
							`product_id` = '.$product_id.',
							`product_attribute_id` = '.$product_attribute_id.',
							`product_name` = "'.$Title.'",
							`product_quantity` = '.$Quantity.',
							`product_quantity_in_stock` = '.$Quantity.',
							`product_price` = '.$Amount.',
							`product_reference` = "'.$SKU.'",
							`total_price_tax_incl` = '.$Amount*$Quantity.',
							`total_price_tax_excl` = '.$Item_price_excl_tax*$Quantity.',
							`unit_price_tax_incl` = '.$Amount.',
							`unit_price_tax_excl` = '.$Item_price_excl_tax.',
							`original_product_price` = '.$Amount.'
							';
				Db::getInstance()->Execute($sql);
				
				if (Pack::isPack($product_id))
				{
					$product = new Product((int)$product_id);
					
					if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2)
					{
						$products_pack = Pack::getItems($product_id, (int)Configuration::get('PS_LANG_DEFAULT'));
						foreach ($products_pack as $product_pack)
						{
							
							$sql = 'UPDATE `'.$prefix.'stock_available` set
							`quantity` = `quantity` - '.$product_pack->pack_quantity*$Quantity.'
							where `id_product` = '.$product_pack->id.' and
							`id_product_attribute` = 0
									';
							Db::getInstance()->Execute($sql);
							
							$sql = 'UPDATE `'.$prefix.'stock_available` set
									`quantity` = `quantity` - '.$product_pack->pack_quantity*$Quantity.'
									where `id_product` = '.$product_pack->id.' and
									`id_product_attribute` = '.$product_pack->id_pack_product_attribute.'
									';
							Db::getInstance()->Execute($sql);
					
						}
					}
					
					if ($product->pack_stock_type == 0 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 0 || Configuration::get('PS_PACK_STOCK_TYPE') == 2)))
					{
						$sql = 'UPDATE `'.$prefix.'stock_available` set
							`quantity` = `quantity` - '.$Quantity.'
							where `id_product` = '.$product_id.' and
							`id_product_attribute` = 0
							';
						Db::getInstance()->Execute($sql);
						
						if($product_attribute_id > 0)
						{
							$sql = 'UPDATE `'.$prefix.'stock_available` set
									`quantity` = `quantity` - '.$Quantity.'
									where `id_product` = '.$product_id.' and
									`id_product_attribute` = '.$product_attribute_id.'
									';
							Db::getInstance()->Execute($sql);
						}
						
						$date = date('Y-m-d');
						$sql = 'UPDATE `'.$prefix.'product_sale` set
								`quantity` = `quantity` + '.$Quantity.',
								`sale_nbr` = `sale_nbr` + '.$Quantity.',
								`date_upd` = '.$date.'
								where `id_product` = '.$product_id.'
								';
						Db::getInstance()->Execute($sql);	
					}
				}
				else
				{
					$sql = 'UPDATE `'.$prefix.'stock_available` set
							`quantity` = `quantity` - '.$Quantity.'
							where `id_product` = '.$product_id.' and
							`id_product_attribute` = 0
							';
					Db::getInstance()->Execute($sql);
					
					if($product_attribute_id > 0)
					{
						$sql = 'UPDATE `'.$prefix.'stock_available` set
								`quantity` = `quantity` - '.$Quantity.'
								where `id_product` = '.$product_id.' and
								`id_product_attribute` = '.$product_attribute_id.'
								';
						Db::getInstance()->Execute($sql);
					}
					
					
					$date = date('Y-m-d');
					$sql = 'UPDATE `'.$prefix.'product_sale` set
							`quantity` = `quantity` + '.$Quantity.',
							`sale_nbr` = `sale_nbr` + '.$Quantity.',
							`date_upd` = '.$date.'
							where `id_product` = '.$product_id.'
							';
					Db::getInstance()->Execute($sql);
				}
			  }
			  $i++;
			}
			
			 // Adding an entry in order_carrier table
			 if (!is_null($carrier))
			 {
				$order_carrier = new OrderCarrier();
				$order_carrier->id_order = (int)$order->id;
				$order_carrier->id_carrier = (int)$id_carrier;
				$order_carrier->weight = '0';
				$order_carrier->shipping_cost_tax_excl = (float)$shipping_amount;
				$order_carrier->shipping_cost_tax_incl = (float)$shipping_amount;
				$order_carrier->add();
			 }
			 else
			 {
				$order_carrier = new OrderCarrier();
				$order_carrier->id_order = (int)$order->id;
				$order_carrier->id_carrier = $id_carrier;
				$order_carrier->weight = '0';
				$order_carrier->shipping_cost_tax_excl = (float)$shipping_amount;
				$order_carrier->shipping_cost_tax_incl = (float)$shipping_amount;
				$order_carrier->add();
			 }
			
			 foreach ($cart_rules as $cart_rule)
			 {
				 $values['tax_incl'] = $cart_rule['value_real'];
				 $values['tax_excl'] = $cart_rule['value_tax_exc'];
					
				 $order->addCartRule($cart_rule['obj']->id, $cart_rule['obj']->name,  $values, 0, $cart_rule['obj']->free_shipping);
			 }
			
			 // Set the order status
			 $history = new OrderHistory();
			 $history->id_order = (int)$order->id;
			 $history->changeIdOrderState((int)$id_order_state, $order->id, true);
			 $history->addWithemail(true , array() , $this->context);
			 
			 if($total_amount == 0)
			 {
				$date = date('Y-m-d H:i:s');
				$sql  = 'INSERT into `'.$prefix.'order_payment` set 	order_reference = "'.$reference.'",   `id_currency` = "'.$this->context->currency->id.'",  `amount` = "0.0", `payment_method` = "Pay With Amazon", `conversion_rate` = "'.$this->context->currency->conversion_rate.'", `date_add` = "'.$date.'" '; 
				Db::getInstance()->Execute($sql); 
			 }
			
			 $acknowledge_arr['MerchantOrderID'] = (int)$order->id; 
  }
	
	
	/*
	 * Update the order detail by using Notification XML Data
	 */
	public function update_cart_by_junglee_xml($order_id,$data){
 
      $xml = simplexml_load_string($data);
       
      $prefix    = _DB_PREFIX_;
	  $tablename = $prefix.'orders';
			
      $total_amount = 0;
      $total_principal = 0;
      $shipping_amount = 0;
      $total_promo = 0;
     
	  foreach($xml->ProcessedOrder->ProcessedOrderItems->ProcessedOrderItem as $item) {
			
			$product_id = (string)$item->SKU;  
			$product = new Product((int)$product_id);
			$SKU = $product->reference;
			$Title = (string)$item->Title;  
			$Amount = (float)$item->Price->Amount;
			$other_promo = 0;
			foreach($item->ItemCharges->Component as $amount_type) {
				  $item_charge_type = (string)$amount_type->Type; 
				  if($item_charge_type == 'Principal') {
					$principal = (string)$amount_type->Charge->Amount;
				  }
				  if($item_charge_type == 'Shipping') {
					$Shipping = (string)$amount_type->Charge->Amount;
				  }
				  if($item_charge_type == 'PrincipalPromo') {
					$principal_promo = (string)$amount_type->Charge->Amount;
				  }
				  if($item_charge_type == 'ShippingPromo') {
					$shipping_promo = (string)$amount_type->Charge->Amount;
				  }
				  if($item_charge_type == 'OtherPromo') {
					$other_promo = (string)$amount_type->Charge->Amount;
				  }
			}
			$CurrencyCode = (string)$item->Price->CurrencyCode; 
			$Quantity = (int)$item->Quantity; 
			
			
			$total_principal += $principal;
			
			$total_amount += ($principal - $principal_promo) + ($Shipping - $shipping_promo) ;
			
			$shipping_amount += $Shipping;

			$total_promo += $principal_promo + $shipping_promo + $other_promo;   
	  }
	  

      $ShippingServiceLevel = (string)$xml->ProcessedOrder->ShippingServiceLevel;
     
      $sql  = 'UPDATE `'.$prefix.'pwa_orders` set `shipping_service` = "'.$ShippingServiceLevel.'" , `order_type` = "junglee" where `prestashop_order_id` = "'.$order_id.'" ';
	  Db::getInstance()->Execute($sql);
      
      
		  $email = (string)$xml->ProcessedOrder->BuyerInfo->BuyerEmailAddress;
		  
		  $sql = 'SELECT * from `'.$prefix.'customer` where email = "'.$email.'" ';
		  $results = Db::getInstance()->ExecuteS($sql);
		  if(empty($results))
		  {
			
			$name = (string)$xml->ProcessedOrder->BuyerInfo->BuyerName;
			$name_arr = explode(' ',$name);
			if(count($name_arr) > 1)
			{
				$firstname = '';
				for($i=0;$i<=count($name_arr)-2;$i++)
				{
					$firstname = $firstname.' '.$name_arr[$i];
				}
				$lastname = $name_arr[count($name_arr)-1];
			}
			else
			{
				$firstname = $name;
				$lastname = '.';
			}
			
			$password = Tools::passwdGen();
			$customer = new Customer();
			$customer->firstname = trim($firstname);
			$customer->lastname = $lastname;
			$customer->email = (string)$xml->ProcessedOrder->BuyerInfo->BuyerEmailAddress;
			$customer->passwd = md5($password);
			$customer->active = 1;
			
			if (Configuration::get('PS_GUEST_CHECKOUT_ENABLED'))
			$customer->is_guest = 1;
			else
			$customer->is_guest = 0;
			
			$customer->add();
			$customer_id = $customer->id;
			
			if (Configuration::get('PS_CUSTOMER_CREATION_EMAIL') && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED'))
			{
				Mail::Send(
					$this->context->language->id,
					'account',
					Mail::l('Welcome!'),
					array(
						'{firstname}' => $customer->firstname,
						'{lastname}' => $customer->lastname,
						'{email}' => $customer->email,
						'{passwd}' => $password
					),
					$customer->email,
					$customer->firstname.' '.$customer->lastname
				);
			}
		 }
		 else
		 {
			$customer_id = $results[0]['id_customer'];
		 }
			
			$id_country = Country::getByIso((string)$xml->ProcessedOrder->ShippingAddress->CountryCode);
			if($id_country == 0 || $id_country == '')
			{
				$id_country = 110;
			}
			
			$name = (string)$xml->ProcessedOrder->ShippingAddress->Name;
			$name_arr = explode(' ',$name);
			if(count($name_arr) > 1)
			{
				$firstname = '';
				for($i=0;$i<=count($name_arr)-2;$i++)
				{
					$firstname = $firstname.' '.$name_arr[$i];
				}
				$lastname = $name_arr[count($name_arr)-1];
			}
			else
			{
				$firstname = $name;
				$lastname = '.';
			}
			
			$address = new Address();
			$address->id_country = $id_country;
			$address->id_state = 0;
			$address->id_customer = $customer_id;
			$address->alias = 'My Address';
			$address->firstname = trim($firstname);
			$address->lastname = $lastname;
			$address->address1 = (string)$xml->ProcessedOrder->ShippingAddress->AddressFieldOne;
			$address->address2 = (string)$xml->ProcessedOrder->ShippingAddress->AddressFieldTwo;
			$address->postcode = (string)$xml->ProcessedOrder->ShippingAddress->PostalCode;
			$address->city = (string)$xml->ProcessedOrder->ShippingAddress->City.' '.(string)$xml->ProcessedOrder->ShippingAddress->State;
			$address->active = 1;
			$address->add();
			$address_id = $address->id;
			
			
	  //$id_order_state = Configuration::get('PS_OS_PREPARATION');
	  $id_order_state = 99;
	 
	  $reference = Order::generateReference();

			$order = new Order();
			$order->id = $order_id;
			$order->id_customer = (int)$customer_id;
			$order->id_address_invoice = (int)$address_id;
			
			
			$carrier = null;
			$sql = 'SELECT id_carrier from  `'.$prefix.'carrier` where `active` = 1 and `deleted` = 0 limit 0,1';
			$result = Db::getInstance()->ExecuteS($sql);
			$id_carrier = $result[0]['id_carrier'];
			
			$sql = 'SELECT id_currency from  `'.$prefix.'currency` where `active` = 1 and `deleted` = 0 and `iso_code` = "INR" limit 0,1';
			$result = Db::getInstance()->ExecuteS($sql);
			$currency_id = $result[0]['id_currency'];
			
			 $sql  = 'UPDATE `'.$tablename.'` set 
			  `id_customer` = '.(int)$customer_id.',
			  `id_carrier` = '.$id_carrier.',
			  `id_address_invoice` = '.(int)$address_id.',
			  `id_address_delivery` = '.(int)$address_id.',
			  `id_currency` = '.$currency_id.',
			  `reference` = "'.$reference.'",
			  `secure_key` = "'.md5(uniqid()).'",
			  
			  `total_paid` = '.$total_amount.',
			  `total_paid_tax_incl` = '.$total_amount.',
			  `total_paid_tax_excl` = '.$total_amount.',
			  `total_paid_real` = 0,
			 
			  `total_shipping` = '.$shipping_amount.',
			  `total_shipping_tax_incl` = '.$shipping_amount.',
			  `total_shipping_tax_excl` = '.$shipping_amount.',
			  
			  `total_discounts` = '.(float)$total_promo.',
			  `total_discounts_tax_incl` = '.(float)$total_promo.',
			  `total_discounts_tax_excl` = '.(float)$total_promo.',
			  
			  `total_products` = '.$total_principal.',
			  `total_products_wt` = '.$total_principal.',
			  
			  `invoice_date` = "0000-00-00 00:00:00",
			  `delivery_date` = "0000-00-00 00:00:00"
			  where `id_order` = '.$order_id.' ' ;
			  
			  //`round_mode` = '.Configuration::get('PS_PRICE_ROUND_MODE').',
			  /*`total_wrapping_tax_incl` = '.$WrappingAmount.',
			  `total_wrapping_tax_excl` = '.$WrappingAmount.',
			  `total_wrapping` = '.$WrappingAmount.',*/
			 
			  Db::getInstance()->Execute($sql);
			 
			  $acknowledge_arr = array();
			  $i = 0;
			  foreach($xml->ProcessedOrder->ProcessedOrderItems->ProcessedOrderItem as $item) {
					
					$product_id = (string)$item->SKU;  
					$product = new Product((int)$product_id);
				    $SKU = $product->reference;
					$AmazonOrderItemCode = (string)$item->AmazonOrderItemCode;
					$Title = (string)$item->Title;  
					$Amount = (float)$item->Price->Amount;
					
					$acknowledge_arr['items'][$i]['AmazonOrderItemCode'] = $AmazonOrderItemCode;
					$acknowledge_arr['items'][$i]['product_id'] = $product_id;
					
					$CurrencyCode = (string)$item->Price->CurrencyCode; 
					$Quantity = (int)$item->Quantity; 
					$other_promo = 0;
					foreach($item->ItemCharges->Component as $amount_type) {
						  $item_charge_type = (string)$amount_type->Type; 
						  if($item_charge_type == 'Principal') {
							$principal = (string)$amount_type->Charge->Amount;
						  }
						  if($item_charge_type == 'Shipping') {
							$Shipping = (string)$amount_type->Charge->Amount;
						  }
						  if($item_charge_type == 'PrincipalPromo') {
							$principal_promo = (string)$amount_type->Charge->Amount;
						  }
						  if($item_charge_type == 'ShippingPromo') {
							$shipping_promo = (string)$amount_type->Charge->Amount;
						  }
						  if($item_charge_type == 'OtherPromo') {
							$other_promo = (string)$amount_type->Charge->Amount;
						  }
					}
					
					$sql = 'INSERT into `'.$prefix.'order_detail` set
							`id_order` = '.$order_id.',
							`product_id` = '.$product_id.',
							`product_name` = "'.$Title.'",
							`product_quantity` = '.$Quantity.',
							`product_quantity_in_stock` = '.$Quantity.',
							`product_price` = '.$Amount.',
							`product_reference` = "'.$SKU.'",
							`total_price_tax_incl` = '.$Amount*$Quantity.',
							`total_price_tax_excl` = '.$Amount*$Quantity.',
							`unit_price_tax_incl` = '.$Amount.',
							`unit_price_tax_excl` = '.$Amount.',
							`original_product_price` = '.$Amount.'
							';
							
				Db::getInstance()->Execute($sql);
				
				$sql = 'UPDATE `'.$prefix.'stock_available` set
						`quantity` = `quantity` - '.$Quantity.'
						where `id_product` = '.$product_id.' and
						`id_product_attribute` = 0
						';
				Db::getInstance()->Execute($sql);
				
				/*$sql = 'UPDATE `'.$prefix.'stock_available` set
						`quantity` = `quantity` - '.$Quantity.'
						where `id_product` = '.$product_id.' and
						`id_product_attribute` = '.$product_attribute_id.'
						';
				Db::getInstance()->Execute($sql);*/
				
				$date = date('Y-m-d');
				$sql = 'UPDATE `'.$prefix.'product_sale` set
						`quantity` = `quantity` + '.$Quantity.',
						`sale_nbr` = `sale_nbr` + '.$Quantity.',
						`date_upd` = '.$date.'
						where `id_product` = '.$product_id.'
						';
				Db::getInstance()->Execute($sql);
					
			    $i++;
			}
			
			 // Adding an entry in order_carrier table
			 if (!is_null($carrier))
			 {
				$order_carrier = new OrderCarrier();
				$order_carrier->id_order = (int)$order->id;
				$order_carrier->id_carrier = (int)$id_carrier;
				$order_carrier->weight = '0';
				$order_carrier->shipping_cost_tax_excl = (float)$shipping_amount;
				$order_carrier->shipping_cost_tax_incl = (float)$shipping_amount;
				$order_carrier->add();
			 }
			 else
			 {
				$order_carrier = new OrderCarrier();
				$order_carrier->id_order = (int)$order->id;
				$order_carrier->id_carrier = $id_carrier;
				$order_carrier->weight = '0';
				$order_carrier->shipping_cost_tax_excl = (float)$shipping_amount;
				$order_carrier->shipping_cost_tax_incl = (float)$shipping_amount;
				$order_carrier->add();
			 }
			
			
			 // Set the order status
			 $history = new OrderHistory();
			 $history->id_order = (int)$order->id;
			 $history->changeIdOrderState((int)$id_order_state, $order->id, true);
			 $history->addWithemail(true , array());
			
			 $acknowledge_arr['MerchantOrderID'] = (int)$order->id; 
  }
  
	
	public function update_request($param) {
		$prefix    = _DB_PREFIX_;
		$tablename = $prefix.'pwa_iopn_records';
		
		$sql  = 'UPDATE `'.$tablename.'`  set `notification_reference_id` =  "'.$param['NotificationReferenceId'].'" , `amazon_order_id` = "'.$param['AmazonOrderID'].'" ,  `status` = "'.$param['Status'].'"  where `id` = "'.$param['iopn_record_id'].'" ' ;
		Db::getInstance()->Execute($sql);
	}
	

}

?>
