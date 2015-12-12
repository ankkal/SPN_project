<?php

require_once('config.inc.php');

class Get_Report  extends  PwaprestaPwamwsModuleFrontController {
	
	public $serviceUrl = MWS_ENDPOINT_URL;

	/**
	 * Constructor for the list order class.
	 */
	public function __construct() {
		$this->includes();
	}

	/**
	 * Include required core files used and classes.
	 */
	public function includes() {
		require_once('MarketplaceWebService/Client.php');
		require_once('MarketplaceWebService/Model/GetReportRequest.php');
		
	}
	
	function init_get_report($reportId)
	{
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
 
		$request = new MarketplaceWebService_Model_GetReportRequest();
		$request->setMerchant(MERCHANT_ID);
		$request->setReport(@fopen('php://memory', 'rw+'));
		$request->setReportId($reportId);
		 
		return $this->invokeGetReport($service, $request);
	}


    function invokeGetReport(MarketplaceWebService_Interface $service, $request) 
    {
		  try {
				   $response = $service->getReport($request);
				  
				   return stream_get_contents($request->getReport());
					
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
 
}
                                                                                
