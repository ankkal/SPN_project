<?php
/**
 * MWS Order API Class
 *
 * @class List_Orders
 * @version	1.0.0
 * 
 * Update order status (Only update order status when order get cancelled)
 */
class List_Orders {

	public $serviceUrl = MWS_ENDPOINT_URL;
	
	public $LastUpdatedAfter = "";
	
	public $order_status_array = array('Pending'=>'pending','Unshipped'=>'processing','Canceled'=>'cancelled');

	/**
	 * Constructor for the list order class.
	 */
	public function __construct() {
		$this->includes();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		require_once('MarketplaceWebServiceOrders/Client.php');
		require_once('MarketplaceWebServiceOrders/Model/ListOrdersRequest.php');
		
	}

	
	function init_mws_order()
	{
		$prefix    = _DB_PREFIX_;

		$sql = 'select * from `'. $prefix .'mws_order_cron` order by id desc limit 0 , 1 ';
		$result = Db::getInstance()->ExecuteS($sql);
		
		if(!empty($result)) {
			$this->LastUpdatedAfter = $result[0]['created_before'];
		}
		else{
			$dateTime = new DateTime('-3 day', new DateTimeZone('UTC'));
			$time = $dateTime->format(DATE_ISO8601);  
			$this->LastUpdatedAfter = $time;
		}

		$config = array (
		   'ServiceURL' => $this->serviceUrl."Orders/2013-09-01",
		   'ProxyHost' => null,
		   'ProxyPort' => -1,
		   'ProxyUsername' => null,
		   'ProxyPassword' => null,
		   'MaxErrorRetry' => 3,
		 );
		 
		 $service = new MarketplaceWebServiceOrders_Client(
					AWS_ACCESS_KEY_ID,
					AWS_SECRET_ACCESS_KEY,
					APPLICATION_NAME,
					APPLICATION_VERSION,
					$config);
		
		 $request = new MarketplaceWebServiceOrders_Model_ListOrdersRequest();
		 $request->setSellerId(MERCHANT_ID);
		 $request->setMarketplaceId(MARKETPLACE_ID);
		 $request->setLastUpdatedAfter($this->LastUpdatedAfter);
		
		// object or array of parameters
		$this->invokeListOrders($service, $request);
	}

	
	function invokeListOrders(MarketplaceWebServiceOrders_Interface $service, $request)
	{
		  try {
		
			$response = $service->ListOrders($request);
			$dom = new DOMDocument();
			$dom->loadXML($response->toXML());
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$xml = $dom->saveXML();
			$this->update_order($xml);

		 } catch (MarketplaceWebServiceOrders_Exception $ex) {
			 
			$message  =  'MWS Order API : Caught Exception : '.$ex->getMessage(). "\n";
			$message .= "Response Status Code: " . $ex->getStatusCode() . "\n";
			$message .= "Error Code: " . $ex->getErrorCode() . "\n";
			$message .= "Error Type: " . $ex->getErrorType() . "\n";

			$param['message'] = $message;
			$obj = new Pwapresta();
			$obj->generate_log($param);
		 }
	}
	
	public function update_order($data) {
		$prefix    = _DB_PREFIX_;
		$tablename = $prefix.'pwa_orders';
		$xml = simplexml_load_string($data);
		
		$this->context = Context::getContext();
		
		$mws_dump = Configuration::get('PWAPRESTA_PWAPRESTA_MWS_ORDER_DUMP');
		if( $mws_dump == '1' ){
			$dir = Configuration::get('PWAPRESTA_PWAPRESTA_MWS_ORDER_DUMP_URL');
			if ( !file_exists($dir) && !is_dir($dir) ) {
				mkdir($dir, 0777);
			}

			$filename = $dir.time().'_mws_order';
		 	$myfile = fopen($filename, "w");
		 	fwrite($myfile, $data);
		 	fclose($myfile);
		}
		
		$LastUpdatedBefore = $xml->ListOrdersResult->LastUpdatedBefore;
		
		$sql = 'INSERT into `'.$prefix .'mws_order_cron` (`created_before`) VALUES("'.$LastUpdatedBefore.'") ';
		Db::getInstance()->Execute($sql);
		
		foreach($xml->ListOrdersResult->Orders->Order as $order)
		{
			$AmazonOrderId = (string)$order->AmazonOrderId;
			$OrderStatus   = (string)$order->OrderStatus;
			$sql = 'select prestashop_order_id from `'.$tablename.'` where amazon_order_id = "'.$AmazonOrderId.'"';
			$results = Db::getInstance()->ExecuteS($sql);
			$presta_order_id = isset($results[0]['prestashop_order_id']) ? $results[0]['prestashop_order_id'] : 0;

			if($presta_order_id && $OrderStatus=='Canceled')
			{
				$id_order_state = Configuration::get('PS_OS_CANCELED');
				$history = new OrderHistory();
		 		$history->id_order = (int)$presta_order_id;
		 		$history->changeIdOrderState((int)$id_order_state, $presta_order_id, true);
		 		$history->addWithemail(true , array() , $this->context);
			}
		}
	}

	
}
