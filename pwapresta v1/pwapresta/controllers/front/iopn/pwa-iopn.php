<?php

class PWA_Iopn {
	
	public function __construct()
	{
		$this->context = Context::getContext();
		$this->includes();
	}
	
	/**
	 * Include required core files used to update the orders.
	 */
	public function includes() {
		include_once( 'SignatureCalculator.php' );
		include_once( 'NewOrderNotification.php' );
		include_once( 'OrderReadyToShipNotification.php' );
		include_once( 'OrderCancelledNotification.php' );
	}

	/*
	 * Accept notifications data and parse them to update orders.
	 */
	public function notifications($param) {
		
		$prefix    = _DB_PREFIX_;
		
		try {
			
			if(isset($param['UUID']) && $param['UUID'] != '')
			$uuid 			   = urldecode($param['UUID']);
			else
			$uuid			   = '';
			
			if(isset($param['Timestamp']) && $param['Timestamp'] != '')
			$timestamp  	   = urldecode($param['Timestamp']);
			else
			$timestamp		   = '';
			
			if(isset($param['Signature']) && $param['Signature'] != '')
			$Signature  	   = str_replace(' ','+',urldecode($param['Signature']));
			else
			$Signature		   = '';
			
			if(isset($param['AWSAccessKeyId']) && $param['AWSAccessKeyId'] != '')
			$AWSAccessKeyId    = urldecode($param['AWSAccessKeyId']);
			else
			$AWSAccessKeyId    = '';
			
			
			$NotificationType  = urldecode($param['NotificationType']);
			$NotificationData  = stripslashes(urldecode($param['NotificationData']));
			
			if($uuid != '')
			{
				$sql = 'INSERT into `'.$prefix .'pwa_iopn_records` (`uuid`,`timestamp`,`notification_type`) VALUES("'.$uuid.'" , "'.$timestamp.'" , "'.$NotificationType.'") ';
				Db::getInstance()->Execute($sql);
				$iopn_record_id = Db::getInstance()->Insert_ID();
		    }
		    
		    
			// Verify that the notification request is valid by verifying the Signature
			$concatenate = $uuid.$timestamp;
			
			$secretKeyID = Configuration::get('PWAPRESTA_PWAPRESTA_SECRET_KEY');
			
			$calculator = new SignatureCalculator();
			$generatedSignature = $calculator->calculateRFC2104HMAC($concatenate, $secretKeyID);
			
			if(($Signature != '' && $Signature == $generatedSignature) || $Signature == '') 
			{
				// Verify the Timestamp
				//$this->time_difference($timestamp) > 15
				if(1) {
					
					if($NotificationType == 'NewOrderNotification') {
						
						$new_order = new NewOrderNotification();
						$new_order->update_order($NotificationData , $iopn_record_id);
					}
					
					if($NotificationType == 'OrderReadyToShipNotification') {
						
						if($Signature == '')
						{
							$xml = simplexml_load_string($NotificationData);
							$AmazonOrderID = (string)$xml->ProcessedOrder->AmazonOrderID;	
							
							$obj = new Pwapresta();
							if($obj->pwa_order_exist($AmazonOrderID))
							{
								$confirm_order = new OrderReadyToShipNotification();
								$confirm_order->update_order_status($NotificationData , $iopn_record_id);
								header('HTTP/1.1 200 OK');
							}
							else
							{
								echo 'Sorry! it seems that this order is a fake order.';
							}
						}
						else
						{
							$confirm_order = new OrderReadyToShipNotification();
							$confirm_order->update_order_status($NotificationData , $iopn_record_id);
							header('HTTP/1.1 200 OK');
						}
					}
					
					if($NotificationType == 'OrderCancelledNotification') {
						
						$cancel_order = new OrderCancelledNotification();
						$cancel_order->cancel_order($NotificationData , $iopn_record_id);
						header('HTTP/1.1 200 OK');
					}
				}
				else
				{
					$param['message'] = 'IOPN Notifications : '.$NotificationType.' : IOPN function called and with wrong timestamp.';
					$obj = new Pwapresta();
					$obj->generate_log($param);
					
					// Respond to the Request
					header('HTTP/1.1 403 PERMISSION_DENIED');
				}
			}
			else
			{
				
				$param['message'] = 'IOPN Notifications : '.$NotificationType.' : IOPN function called and with wrong signature.';
				$obj = new Pwapresta();
				$obj->generate_log($param);
						
				// Respond to the Request
				header('HTTP/1.1 403 PERMISSION_DENIED');
			}
			
		} catch (Exception $e) {
			 $param['message'] = 'IOPN Notifications : Caught exception : '.$e->getMessage().'.';
			 $obj = new Pwapresta();
			 $obj->generate_log($param);
		}
	}
	
	/*
	 * Calculate time difference 
	 */  
	public function time_difference($timestamp) {
		date_default_timezone_set("GMT");
		$mytimestamp =  date("Y-m-d H:i:s");
		
		$start_date = new DateTime($timestamp);
		$since_start = $start_date->diff(new DateTime($mytimestamp));
		
		$minutes = $since_start->days * 24 * 60;
		$minutes += $since_start->h * 60;
		$minutes += $since_start->i;
		return $minutes;
	}
	
	
	
	
	
}
?>
