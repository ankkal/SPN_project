<?php

require_once('config.inc.php');


/**
 * MWS Report API Class
 *
 * @class Get_Report_Request_List
 * @version	1.0.0
 * 
 * Update order details 
 */
class Get_Report_Request_List  extends  PwaprestaPwamwsModuleFrontController {
	
	public $serviceUrl = MWS_ENDPOINT_URL;


	/**
	 * Constructor for the list order class.
	 */
	public function __construct() {
		$this->includes();
	}

	/**
	 * Include required core files and classes.
	 */
	public function includes() {
		require_once('MarketplaceWebService/Client.php');
		require_once('MarketplaceWebService/Exception.php');
		require_once('MarketplaceWebService/Model/GetReportRequestListRequest.php');	
	}
	
	function init_create_orders()
	{
		$prefix = _DB_PREFIX_;
		
		$config = array (
		  'ServiceURL' => $this->serviceUrl,
		  'ProxyHost' => null,
		  'ProxyPort' => -1,
		  'MaxErrorRetry' => 3,
		);
		
	    $service = new MarketplaceWebService_Client(
		 AWS_ACCESS_KEY_ID, 
		 AWS_SECRET_ACCESS_KEY, 
		 $config,
		 APPLICATION_NAME,
		 APPLICATION_VERSION);
	 
		$request = new MarketplaceWebService_Model_GetReportRequestListRequest();
		$request->setMerchant(MERCHANT_ID);
		$request->setReportTypeList(array('0'=>'_GET_ORDERS_DATA_'));
		$request->setReportProcessingStatusList(array('0'=>'_DONE_'));
		$request->setMaxCount(20);
		
		$sql = 'select * from `'. $prefix .'mws_report_cron` order by id desc limit 0 , 1 ';
		$last_request_date = Db::getInstance()->ExecuteS($sql);
		
		if(!empty($last_request_date)) {
		  	$time = $last_request_date[0]['created_before'];
		}
		else{
			$dateTime = new DateTime('-3 day', new DateTimeZone('UTC'));
			$time = $dateTime->format(DATE_ISO8601);  
		}
		$request->setRequestedFromDate($time);
		
		$this->invokeGetReportRequestList($service, $request);
	}
	                                                          

  function invokeGetReportRequestList(MarketplaceWebService_Interface $service, $request) 
  {
	  $prefix = _DB_PREFIX_;
      try {
              $response = $service->getReportRequestList($request);
              if ($response->isSetGetReportRequestListResult()) { 
				 	
                    $getReportRequestListResult = $response->getGetReportRequestListResult();
                    $reportRequestInfoList = $getReportRequestListResult->getReportRequestInfoList();
                    
                    foreach ($reportRequestInfoList as $reportRequestInfo) {
							
						  if( ($reportRequestInfo->isSetReportType() && $reportRequestInfo->getReportType() == '_GET_ORDERS_DATA_') && ($reportRequestInfo->isSetReportProcessingStatus() && $reportRequestInfo->getReportProcessingStatus() == '_DONE_') )
						  	{
							  if ($reportRequestInfo->isSetReportRequestId()) 
							  {
								  $ReportRequestId = $reportRequestInfo->getReportRequestId();
							  }
							  
							  if ($reportRequestInfo->isSetGeneratedReportId()) 
							  {
								 $GeneratedReportId = $reportRequestInfo->getGeneratedReportId();
								 if($GeneratedReportId == '' && $ReportRequestId != '') {
									 $GeneratedReportId = $this->get_report_list_api($ReportRequestId);
									 $data = $this->get_report_api($GeneratedReportId);
									 
								 }else{ 
									 $data = $this->get_report_api($GeneratedReportId);
								 }
								 
								 $xml = simplexml_load_string($data);
								
								 // Check and dump MWS Report API Response

								 if( Configuration::get('PWAPRESTA_PWAPRESTA_MWS_REPORT_DUMP') == '1'){

									$dir = Configuration::get('PWAPRESTA_PWAPRESTA_MWS_REPORT_DUMP_URL');
									if (!file_exists($dir) && !is_dir($dir)) {
										mkdir($dir, 0777);
									} 

									$filename = $dir.$GeneratedReportId.'_mws_report';
									$myfile = fopen($filename, "w");
									fwrite($myfile, $data);
									fclose($myfile);
								 }
								 
								 
								 foreach($xml->Message as $orderdetail) {
										$AmazonOrderID = (string)$orderdetail->OrderReport->AmazonOrderID;
										
										$sql = 'select * from `'.$prefix.'pwa_orders` where amazon_order_id = "'.$AmazonOrderID.'" ';
										$results = Db::getInstance()->ExecuteS($sql);
		
										if(empty($results)) 
										{
											$tablename = $prefix.'orders';
											$date      = date('Y-m-d H:i:s');
											$sql  = 'INSERT into `'.$tablename.'`  (`current_state` , `payment` , `module` , `date_add` ) VALUES( 99, "Pay with Amazon", "pwapresta", "'.$date.'" )' ;
											Db::getInstance()->Execute($sql);
											$order_id = Db::getInstance()->Insert_ID();
				
											$tablename = $prefix.'pwa_orders';
											$sql  = 'INSERT into `'.$tablename.'`  (`prestashop_order_id` , `amazon_order_id` ) VALUES( "'.$order_id.'", "'.$AmazonOrderID.'" )' ;
											Db::getInstance()->Execute($sql);
											
											$this->update_order_detail($order_id , $orderdetail);
										}
										else 
										{
											$order_id = $results[0]['prestashop_order_id'];
											$this->update_order_detail($order_id , $orderdetail);
										}
								 }
								
							  }
						  }
                    }
                    
                     $dateTime = new DateTime('now', new DateTimeZone('UTC'));
					 $time = $dateTime->format(DATE_ISO8601); 
					 $sql = 'INSERT into `'.$prefix .'mws_report_cron` (`created_before`) VALUES("'.$time.'") ';
					 Db::getInstance()->Execute($sql);
                } 
               
     } catch (MarketplaceWebService_Exception $ex) {
        $message  =  'MWS Report API : Caught Exception : '.$ex->getMessage(). "\n";
		$message .= "Response Status Code: " . $ex->getStatusCode() . "\n";
		$message .= "Error Code: " . $ex->getErrorCode() . "\n";
		$message .= "Error Type: " . $ex->getErrorType() . "\n";

		$param['message'] = $message;
		$obj = new Pwapresta();
		$obj->generate_log($param);
     }
 }
 
 
	public function update_order_detail($order_id , $orderdetail) {
		$prefix    = _DB_PREFIX_;
		$tablename = $prefix.'pwa_orders';
			
		$sql = 'select * from `'.$tablename.'` where prestashop_order_id = '.$order_id.' ';
		$results = Db::getInstance()->ExecuteS($sql);	
		$non_received = (int)$results[0]['_non_received'];
		if(!$non_received) 
		{	
			$id = $results[0]['id'];
			$sql  = 'UPDATE `'.$tablename.'` set `_non_received` = 1 where `id` = '.$id.' ';
			Db::getInstance()->Execute($sql);

			$this->update_cart_by_xml($order_id,$orderdetail);	
		}	
	}
	
	
	public function update_cart_by_xml($order_id,$orderdetail){
		foreach($orderdetail->OrderReport->Item as $item) {
				foreach($item->CustomizationInfo as $info) {
				
				$info_type = (string)$info->Type;	
				if($info_type == 'url') {
					$info_array = explode(',',$info->Data);
					$customerId_array = explode('=',$info_array[0]);
					$ClientRequestId = $customerId_array[1];
					break;
				}
			}
		}

		if(strlen($ClientRequestId) < 9)
	    {
			$this->update_cart_by_site_xml($order_id,$orderdetail);
		}
		else
		{
			$this->update_cart_by_junglee_xml($order_id,$orderdetail);
		}

	}


	public function update_cart_by_site_xml($order_id,$orderdetail)
	{
		$prefix    = _DB_PREFIX_;
	  	$tablename = $prefix.'orders';
      	$total_amount = 0;
      	$total_principal = 0;
      	$wrapping_fees = 0;
      	$wrapping_fees_tax = 0;
      	$wrapping_fees_promo = 0;
      	$shipping_amount = 0;
      	$total_promo = 0;
      	$ClientRequestId = 0;
      	$flag = 0;
		$AmazonOrderID = (string)$orderdetail->OrderReport->AmazonOrderID;
		
			
		foreach($orderdetail->OrderReport->Item as $item) {
			$SKU = (string)$item->SKU;
			$Title = (string)$item->Title;
			$Quantity = (int)$item->Quantity;
			$Principal_Promotions = 0;
      		$Shipping_Promotions = 0;
    		if($SKU == 'NO-SKU')
    		{
    			$flag = 1;
    		}
    			
			foreach($item->ItemPrice->Component as $amount_type) {
				
				$item_charge_type = (string)$amount_type->Type;	
				
				if($item_charge_type == 'Principal') {
					if($SKU == 'wrapping_fee')
					{
						$wrapping_fees = abs((float)$amount_type->Amount);
					}
						$Principal = abs((float)$amount_type->Amount);
				}
				
				if($item_charge_type == 'Shipping') {
					$Shipping = abs((float)$amount_type->Amount);
				}
				
				if($item_charge_type == 'Tax') {
					if($SKU == 'wrapping_fee')
					{
						$wrapping_fees_tax = abs((float)$amount_type->Amount);
					}
					$Tax = abs((float)$amount_type->Amount);
				}
				
				if($item_charge_type == 'ShippingTax') {
					$ShippingTax = abs((float)$amount_type->Amount);
				}
			}
			
			if( !empty($item->Promotion) ) {
				foreach($item->Promotion as $promotions){
					foreach($promotions->Component as $promotion_amount_type) {
						
						$promotion_type = (string)$promotion_amount_type->Type;
						
						if($promotion_type == 'Shipping') {
							$Shipping_Promotions += abs((float)$promotion_amount_type->Amount);
						}
						
						if($promotion_type == 'Principal') {
							if($SKU == 'wrapping_fee')
							{
								$wrapping_fees_promo = abs((float)$amount_type->Amount);
							}
								$Principal_Promotions += abs((float)$promotion_amount_type->Amount);
						}
					}
				}
			}
			
			$total_principal += $Principal;
			
			$total_amount += ($Principal - $Principal_Promotions) + ($Shipping - $Shipping_Promotions) ;
        	
			$shipping_amount += $Shipping + $Shipping_Promotions;
			
			$total_promo += $Principal_Promotions + $Shipping_Promotions;

			foreach($item->CustomizationInfo as $info) {
				
				$info_type = (string)$info->Type;	
				if($info_type == 'url') {
					$info_array = explode(',',$info->Data);
					$customerId_array = explode('=',$info_array[0]);
					$ClientRequestId = $customerId_array[1];
				}
			}
		}
		
		$total_principal = $total_principal - $wrapping_fees;
		$wrapping_fees = $wrapping_fees + $wrapping_fees_tax - $wrapping_fees_promo;
		
	    $ShippingServiceLevel = (string)$orderdetail->OrderReport->FulfillmentData->FulfillmentServiceLevel;
      	$sql  = 'UPDATE `'.$prefix.'pwa_orders` set `shipping_service` = "'.$ShippingServiceLevel.'", `order_type` = "site"  where `prestashop_order_id` = "'.$order_id.'" ';
	  	Db::getInstance()->Execute($sql);

	  	$id_cart = $ClientRequestId;
	    $this->context = Context::getContext();
	    $this->context->cart = new Cart($id_cart);

	    

		if((int)$this->context->cart->id_customer == 0)
		{
			$email = (string)$orderdetail->OrderReport->BillingData->BuyerEmailAddress;
		    $sql = 'SELECT * from `'.$prefix.'customer` where email = "'.$email.'" ';
		  	$results = Db::getInstance()->ExecuteS($sql);
		  	if(empty($results))
		  	{	
				$cust_name = (string)$orderdetail->OrderReport->BillingData->BuyerName;
				$name_arr = explode(' ',$cust_name);
				if(count($name_arr) > 1)
				{
					$firstname = '';
					for($i=0;$i<count($name_arr)-2;$i++)
					{
						$firstname = $firstname.' '.$name_arr[$i];
					}
					$lastname = $name_arr[count($name_arr)-1];
				}
				else
				{
					$firstname = $cust_name;
					$lastname = '.';
				}
				
		  		$password = Tools::passwdGen();
				$customer = new Customer();
				$customer->firstname = trim($firstname);
				$customer->lastname = $lastname;
				$customer->email = (string)$orderdetail->OrderReport->BillingData->BuyerEmailAddress;
				$customer->passwd = md5($password);
				$customer->active = 1;
				
				if (Configuration::get('PS_GUEST_CHECKOUT_ENABLED'))
				$customer->is_guest = 1;
				else
				$customer->is_guest = 0;
				
				$customer->add();
				$customer_id = $customer->id;
				
				if ( Configuration::get('PS_CUSTOMER_CREATION_EMAIL') && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED') )
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
			
			$id_country = Country::getByIso((string)$orderdetail->OrderReport->FulfillmentData->Address->CountryCode);
			if($id_country == 0 || $id_country == '')
			{
				$id_country = 110;
			}
			
			$name = (string)$orderdetail->OrderReport->FulfillmentData->Address->Name;
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
			$address->address1 = (string)$orderdetail->OrderReport->FulfillmentData->Address->AddressFieldOne;
			$address->address2 = (string)$orderdetail->OrderReport->FulfillmentData->Address->AddressFieldTwo;
			$address->postcode = (string)$orderdetail->OrderReport->FulfillmentData->Address->PostalCode;
			$address->phone_mobile = (string)$orderdetail->OrderReport->FulfillmentData->Address->PhoneNumber;
			$address->city = (string)$orderdetail->OrderReport->FulfillmentData->Address->City.' '.(string)$orderdetail->OrderReport->FulfillmentData->Address->StateOrRegion;
			$address->active = 1;
			$address->add();
			$address_id = $address->id;
			
			$cart = new Cart($id_cart);
			$cart->id_customer = $customer_id;
			$cart->id_address_delivery = $address_id;
			$cart->id_address_invoice  = $address_id;
			$cart->update();
		}
		
		

		$this->context->cart = new Cart($id_cart);
	  	$this->context->customer = new Customer($this->context->cart->id_customer);
	  	$id_order_state = 2;

	  	// The tax cart is loaded before the customer so re-cache the tax calculation method
	  	//$this->context->cart->setTaxCalculationMethod();
	  	
	  	$this->context->language = new Language($this->context->cart->id_lang);
	  	$this->context->shop = (isset($shop) ? $shop : new Shop($this->context->cart->id_shop));
	  
	  	$id_currency = isset($currency_special) ? (int)$currency_special : (int)$this->context->cart->id_currency;
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
			  
			  `gift` = '.$gift.',
			  `gift_message` = "'.$gift_message.'",
			  
			  `invoice_date` = "0000-00-00 00:00:00",
			  `delivery_date` = "0000-00-00 00:00:00",
			  
			  `total_wrapping_tax_incl` = '.$wrapping_fees.',
			  `total_wrapping_tax_excl` = '.$wrapping_fees.',
			  `total_wrapping` = '.$wrapping_fees.'
			  
			  where `id_order` = '.$order_id.'' ;
				
				
				//`round_mode` = '.Configuration::get('PS_PRICE_ROUND_MODE').',
				
		Db::getInstance()->Execute($sql);

		$sql = 'SELECT sku,count(sku) as sku_count FROM `'.$prefix.'pwa_order_products` where id_cart = "'.$ClientRequestId.'" group by sku order by sku_count DESC limit 0,1';
		$result = Db::getInstance()->ExecuteS($sql);

		$acknowledge_arr = array();
		$i = 0;
		if((!empty($result) && $result[0]['sku_count'] > 1) || $flag)
		{
			$sql = 'SELECT * FROM `'.$prefix.'pwa_order_products` where id_cart = "'.$ClientRequestId.'" order by sku DESC';
			$order_prod = Db::getInstance()->ExecuteS($sql);
			foreach ($order_prod as $order_item) {
				$id_product = $order_item['id_product'];
				$id_product_attribute = $order_item['id_product_attribute'];
				$SKU = $order_item['sku'];
				$Title = $order_item['title'];
				$Quantity = $order_item['quantity'];
				$Amount = (float)$order_item['amount'];
				$Amount_tax_excl = (float)$order_item['amount_excl'];

				foreach($orderdetail->OrderReport->Item as $item) {
					$SKU_XML = (string)$item->SKU;
					$AmazonOrderItemCode = (string)$item->AmazonOrderItemCode;
					if($SKU_XML == $SKU)
					{
						if($i > 0 && in_array($AmazonOrderItemCode,$acknowledge_arr['items'][($i-1)]))
						{
							break;
						}
						else
						{
							$acknowledge_arr['items'][$i]['AmazonOrderItemCode'] = $AmazonOrderItemCode;
							$acknowledge_arr['items'][$i]['product_id'] = $id_product;
							$i++;
							break;
						}
					}
					elseif($SKU_XML == 'NO-SKU' && $SKU == '')
                    {
                            $acknowledge_arr['items'][$i]['AmazonOrderItemCode'] = $AmazonOrderItemCode;
                            $acknowledge_arr['items'][$i]['product_id'] = $id_product;
                            $i++;
                            break;
                    }
				}
				
				$this->update_order_products($order_id,$id_product,$id_product_attribute,$SKU,$Title,$Quantity,$Amount,$Amount_tax_excl);
			}

		}
		else
		{
			foreach($orderdetail->OrderReport->Item as $item) {
				$SKU = (string)$item->SKU;
				if($SKU == 'wrapping_fee')
				{
					continue;
				}
				$AmazonOrderItemCode = (string)$item->AmazonOrderItemCode;
				$Title = (string)$item->Title;
				$Quantity = (int)$item->Quantity;
				foreach($item->ItemPrice->Component as $amount_type) {
					
					$item_charge_type = (string)$amount_type->Type;	
					
					if($item_charge_type == 'Principal') {
						$Amount = (float)$amount_type->Amount;
					}
				}
				$Amount = $Amount/$Quantity;
				$Amount = round($Amount,3);
				$sql = 'SELECT id_product,id_product_attribute,amount_excl FROM `'.$prefix.'pwa_order_products` where id_cart = "'.$ClientRequestId.'" and sku = "'.$SKU.'"';
				$product_detail = Db::getInstance()->ExecuteS($sql);
				$id_product = isset($product_detail[0]['id_product']) ? $product_detail[0]['id_product'] : 0;
				$id_product_attribute = isset($product_detail[0]['id_product_attribute']) ? $product_detail[0]['id_product_attribute'] : 0;
				

				$Amount_tax_excl = (isset($product_detail[0]['amount_excl']) && (float)$product_detail[0]['amount_excl'] > 0) ? (float)$product_detail[0]['amount_excl'] : $Amount;
				$acknowledge_arr['items'][$i]['AmazonOrderItemCode'] = $AmazonOrderItemCode;
				$acknowledge_arr['items'][$i]['product_id'] = $id_product;
				$i++;
				$this->update_order_products($order_id,$id_product,$id_product_attribute,$SKU,$Title,$Quantity,$Amount,$Amount_tax_excl);
			}
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
			$order_carrier->id_carrier = (int)$id_carrier;
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
		
		if($total_amount == 0)
		{
			$date = date('Y-m-d H:i:s');
			$sql  = 'INSERT into `'.$prefix.'order_payment` set 	order_reference = "'.$reference.'",   `id_currency` = "'.$this->context->currency->id.'",  `amount` = "0.0", `payment_method` = "Pay With Amazon", `conversion_rate` = "'.$this->context->currency->conversion_rate.'", `date_add` = "'.$date.'" '; 
			Db::getInstance()->Execute($sql); 
		}
		
		// Acknowledge the order in seller central using MWS FEED API
		$acknowledge_arr['MerchantOrderID'] = (int)$order->id;
		$obj = new Pwapresta();
		$obj->pwa_acknowledge_feed($acknowledge_arr);
		 
		$history = new OrderHistory();
		$history->id_order = $order->id;
		$history->changeIdOrderState((int)$id_order_state, $order->id, true);
		$history->addWithemail(true , array());
	}

	
	public function update_cart_by_junglee_xml($order_id,$orderdetail)
	{
		$prefix    = _DB_PREFIX_;
	  	$tablename = $prefix.'orders';
      	$total_amount = 0;
      	$total_principal = 0;
      	$shipping_amount = 0;
      	$total_promo = 0;
      	$ClientRequestId = 0;
		$AmazonOrderID = (string)$orderdetail->OrderReport->AmazonOrderID;

		foreach($orderdetail->OrderReport->Item as $item) {
			$SKU = (string)$item->SKU;	
			$Title = (string)$item->Title;
			$Quantity = (int)$item->Quantity;
			$Principal_Promotions = 0;
      		$Shipping_Promotions = 0;
    		
			foreach($item->ItemPrice->Component as $amount_type) {
				
				$item_charge_type = (string)$amount_type->Type;	
				
				if($item_charge_type == 'Principal') {
					$Principal = abs((float)$amount_type->Amount);
				}
				
				if($item_charge_type == 'Shipping') {
					$Shipping = abs((float)$amount_type->Amount);
				}
				
				if($item_charge_type == 'Tax') {
					$Tax = abs((float)$amount_type->Amount);
				}
				
				if($item_charge_type == 'ShippingTax') {
					$ShippingTax = abs((float)$amount_type->Amount);
				}
			}
			
			if( !empty($item->Promotion) ) {
				foreach($item->Promotion as $promotions){
					foreach($promotions->Component as $promotion_amount_type) {
						
						$promotion_type = (string)$promotion_amount_type->Type;
						
						if($promotion_type == 'Shipping') {
							$Shipping_Promotions += abs((float)$promotion_amount_type->Amount);
						}
						
						if($promotion_type == 'Principal') {
							$Principal_Promotions += abs((float)$promotion_amount_type->Amount);
						}
					}
				}
			}
			
			$total_principal += $Principal;
			
			$total_amount += ($Principal - $Principal_Promotions) + ($Shipping - $Shipping_Promotions) ;
        	
			$shipping_amount += $Shipping + $Shipping_Promotions;
			
			$total_promo += $Principal_Promotions + $Shipping_Promotions;

			foreach($item->CustomizationInfo as $info) {
				
				$info_type = (string)$info->Type;	
				if($info_type == 'url') {
					$info_array = explode(',',$info->Data);
					$customerId_array = explode('=',$info_array[0]);
					$ClientRequestId = $customerId_array[1];
				}
			}
		}
		
	    $ShippingServiceLevel = (string)$orderdetail->OrderReport->FulfillmentData->FulfillmentServiceLevel;
      	$sql  = 'UPDATE `'.$prefix.'pwa_orders` set `shipping_service` = "'.$ShippingServiceLevel.'" , `order_type` = "junglee" where `prestashop_order_id` = "'.$order_id.'" ';
	  	Db::getInstance()->Execute($sql);

	  	
	    $cust_name = (string)$orderdetail->OrderReport->BillingData->BuyerName;
		$name_arr = explode(' ',$cust_name);
		if(count($name_arr) > 1)
		{
			$firstname = '';
			for($i=0;$i<count($name_arr)-2;$i++)
			{
				$firstname = $firstname.' '.$name_arr[$i];
			}
			$lastname = $name_arr[count($name_arr)-1];
		}
		else
		{
			$firstname = $cust_name;
			$lastname = ' ';
		}



		$email = (string)$orderdetail->OrderReport->BillingData->BuyerEmailAddress;
	    $sql = 'SELECT * from `'.$prefix.'customer` where email = "'.$email.'" ';
	  	$results = Db::getInstance()->ExecuteS($sql);
	  	if(empty($results))
	  	{	
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
			
			if ( Configuration::get('PS_CUSTOMER_CREATION_EMAIL') && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED') )
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
		
		$id_country = Country::getByIso((string)$orderdetail->OrderReport->FulfillmentData->Address->CountryCode);
		if($id_country == 0 || $id_country == '')
		{
			$id_country = 110;
		}
		
	 	$address = new Address();
		$address->id_country = $id_country;
		$address->id_state = 0;
		$address->id_customer = $customer_id;
		$address->alias = 'My Address';
		$address->firstname = trim($firstname);
		$address->lastname = $lastname;
		$address->address1 = (string)$orderdetail->OrderReport->FulfillmentData->Address->AddressFieldOne;
		$address->address2 = (string)$orderdetail->OrderReport->FulfillmentData->Address->AddressFieldTwo;
		$address->postcode = (string)$orderdetail->OrderReport->FulfillmentData->Address->PostalCode;
		$address->phone_mobile = (string)$orderdetail->OrderReport->FulfillmentData->Address->PhoneNumber;
		$address->city = (string)$orderdetail->OrderReport->FulfillmentData->Address->City.' '.(string)$orderdetail->OrderReport->FulfillmentData->Address->StateOrRegion;
		$address->active = 1;
		$address->add();
		$address_id = $address->id;
		
		$id_order_state = 2;
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
			  where `id_order` = '.$order_id.'' ;
			  
			  // `round_mode` = '.Configuration::get('PS_PRICE_ROUND_MODE').',

		Db::getInstance()->Execute($sql);
		$i = 0;
		foreach($orderdetail->OrderReport->Item as $item) {
				$id_product = (string)$item->SKU;
				$product = new Product((int)$product_id);
				$SKU = $product->reference;
				$AmazonOrderItemCode = (string)$item->AmazonOrderItemCode;
				$Title = (string)$item->Title;
				$Quantity = (int)$item->Quantity;
				foreach($item->ItemPrice->Component as $amount_type) {
					
					$item_charge_type = (string)$amount_type->Type;	
					
					if($item_charge_type == 'Principal') {
						$Amount = (float)$amount_type->Amount;
					}
				}
				$Amount = $Amount/$Quantity;
				$Amount = round($Amount,3);
				
				$acknowledge_arr['items'][$i]['AmazonOrderItemCode'] = $AmazonOrderItemCode;
				$acknowledge_arr['items'][$i]['product_id'] = $id_product;
				$i++;
				
				$sql = 'INSERT into `'.$prefix.'order_detail` set
							`id_order` = '.$order_id.',
							`product_id` = '.$id_product.',
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
						where `id_product` = '.$id_product.' and
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
						where `id_product` = '.$id_product.'
						';
				Db::getInstance()->Execute($sql);
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
			$order_carrier->id_carrier = (int)$id_carrier;
			$order_carrier->weight = '0';
			$order_carrier->shipping_cost_tax_excl = (float)$shipping_amount;
			$order_carrier->shipping_cost_tax_incl = (float)$shipping_amount;
			$order_carrier->add();
		}

		
		// Acknowledge the order in seller central using MWS FEED API
		$acknowledge_arr['MerchantOrderID'] = (int)$order->id;
		$obj = new Pwapresta();
		$obj->pwa_acknowledge_feed($acknowledge_arr);
		 
		$history = new OrderHistory();
		$history->id_order = $order->id;
		$history->changeIdOrderState((int)$id_order_state, $order->id, true);
		$history->addWithemail(true , array());
	}

	
	public function update_order_products($order_id,$id_product,$id_product_attribute,$SKU,$Title,$Quantity,$Amount,$Amount_tax_excl)
	{
		$prefix    = _DB_PREFIX_;
		$sql = 'SELECT id_order_detail FROM `'.$prefix.'order_detail` where `id_order` = '.$order_id.' AND
							`product_id` = "'.$id_product.'" AND
							`product_attribute_id` = "'.$id_product_attribute.'"';
		$results = Db::getInstance()->ExecuteS($sql);

		$id_lang = (int)$this->context->cart->id_lang;
		$sql = 'select att.id_attribute,attr_grp.name,att.id_attribute_group,att_val.name as value from '.$prefix.'product_attribute_combination as att_comb left join '.$prefix.'attribute as att on att_comb.id_attribute = att.id_attribute left join '.$prefix.'attribute_group_lang as attr_grp on attr_grp.id_attribute_group = att.id_attribute_group left join '.$prefix.'attribute_lang as att_val on att_val.id_attribute = att.id_attribute where att_comb.id_product_attribute = "'.$id_product_attribute.'" and att_val.id_lang = "'.$id_lang.'" and attr_grp.id_lang = "'.$id_lang.'"';
		$product_attr = Db::getInstance()->ExecuteS($sql);
		$Title .= ' - ';
		foreach ($product_attr as $attr) {
			$Title .= $attr['name'].' : '.$attr['value'].', ';
		}
		$Title = trim($Title);
		$Title = substr($Title, 0, -1);

		if(empty($results))
		{
			$sql = 'INSERT into `'.$prefix.'order_detail` set
							`id_order` = '.$order_id.',
							`product_id` = '.$id_product.',
							`product_attribute_id` = '.$id_product_attribute.',
							`product_name` = "'.$Title.'",
							`product_quantity` = '.$Quantity.',
							`product_quantity_in_stock` = '.$Quantity.',
							`product_price` = '.$Amount.',
							`product_reference` = "'.$SKU.'",
							`total_price_tax_incl` = '.$Amount*$Quantity.',
							`total_price_tax_excl` = '.$Amount_tax_excl*$Quantity.',
							`unit_price_tax_incl` = '.$Amount.',
							`unit_price_tax_excl` = '.$Amount_tax_excl.',
							`original_product_price` = '.$Amount.'
							';
		}
		else
		{
			$sql = 'UPDATE `'.$prefix.'order_detail` set
							`product_name` = "'.$Title.'",
							`product_quantity` = '.$Quantity.',
							`product_quantity_in_stock` = '.$Quantity.',
							`product_price` = '.$Amount.',
							`product_reference` = "'.$SKU.'",
							`total_price_tax_incl` = '.$Amount*$Quantity.',
							`total_price_tax_excl` = '.$Amount_tax_excl*$Quantity.',
							`unit_price_tax_incl` = '.$Amount.',
							`unit_price_tax_excl` = '.$Amount_tax_excl.',
							`original_product_price` = '.$Amount.' where 
							`id_order` = "'.$order_id.'" AND
							`product_id` = "'.$id_product.'" AND  
							`product_attribute_id` = "'.$id_product_attribute.'"
							';
		}
		Db::getInstance()->Execute($sql);

		if (Pack::isPack($id_product))
				{
					$product = new Product((int)$id_product);
					
					if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2)
					{
						$products_pack = Pack::getItems($id_product, (int)Configuration::get('PS_LANG_DEFAULT'));
						foreach ($products_pack as $product_pack)
						{	
							$sql = 'UPDATE `'.$prefix.'stock_available` set
							`quantity` = `quantity` - '.$product_pack->pack_quantity*$Quantity.'
							where `id_product` = '.$product_pack->id.' and
							`id_product_attribute` = 0
									';
							Db::getInstance()->Execute($sql);
							
							if($product_pack->id_pack_product_attribute > 0)
							{
								$sql = 'UPDATE `'.$prefix.'stock_available` set
										`quantity` = `quantity` - '.$product_pack->pack_quantity*$Quantity.'
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
							`quantity` = `quantity` - '.$Quantity.'
							where `id_product` = '.$id_product.' and
							`id_product_attribute` = 0
							';
						Db::getInstance()->Execute($sql);
						
						if($id_product_attribute > 0)
						{
							$sql = 'UPDATE `'.$prefix.'stock_available` set
									`quantity` = `quantity` - '.$Quantity.'
									where `id_product` = '.$id_product.' and
									`id_product_attribute` = '.$id_product_attribute.'
									';
							Db::getInstance()->Execute($sql);
					    }
						$date = date('Y-m-d');
						$sql = 'UPDATE `'.$prefix.'product_sale` set
								`quantity` = `quantity` + '.$Quantity.',
								`sale_nbr` = `sale_nbr` + '.$Quantity.',
								`date_upd` = '.$date.'
								where `id_product` = '.$id_product.'
								';
						Db::getInstance()->Execute($sql);	
					}
				}
				else
				{
					$sql = 'UPDATE `'.$prefix.'stock_available` set
							`quantity` = `quantity` - '.$Quantity.'
							where `id_product` = '.$id_product.' and
							`id_product_attribute` = 0
							';
					Db::getInstance()->Execute($sql);
					
					if($id_product_attribute > 0)
					{
						$sql = 'UPDATE `'.$prefix.'stock_available` set
								`quantity` = `quantity` - '.$Quantity.'
								where `id_product` = '.$id_product.' and
								`id_product_attribute` = '.$id_product_attribute.'
								';
						Db::getInstance()->Execute($sql);
					}
					$date = date('Y-m-d');
					$sql = 'UPDATE `'.$prefix.'product_sale` set
							`quantity` = `quantity` + '.$Quantity.',
							`sale_nbr` = `sale_nbr` + '.$Quantity.',
							`date_upd` = '.$date.'
							where `id_product` = '.$id_product.'
							';
					Db::getInstance()->Execute($sql);
				}
	} 

}
 ?>
