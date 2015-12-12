<?php

require_once('config.inc.php');

class Get_Report_List  extends  PwaprestaPwamwsModuleFrontController {
	
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
		require_once('MarketplaceWebService/Model/GetReportListRequest.php');
		
	}
	
	function init_get_report_list($ReportRequestId)
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
 

 
		$request = new MarketplaceWebService_Model_GetReportListRequest();
		$request->setMerchant(MERCHANT_ID);
		//$request->setAvailableToDate(new DateTime('now', new DateTimeZone('UTC')));
		//$request->setAvailableFromDate(new DateTime('-3 months', new DateTimeZone('UTC')));
		$request->setAcknowledged(false);
		$request->setReportRequestIdList(array('0'=>$ReportRequestId));
		
		return $this->invokeGetReportList($service, $request);
		
	}
                                                                    

  function invokeGetReportList(MarketplaceWebService_Interface $service, $request) 
  {
      try {
                $response = $service->getReportList($request);
              
                if ($response->isSetGetReportListResult()) { 
                    $getReportListResult = $response->getGetReportListResult();
                    
                    $reportInfoList = $getReportListResult->getReportInfoList();
                   
                    foreach ($reportInfoList as $reportInfo) {
                        
                        if ( ($reportInfo->isSetReportType() && $reportInfo->getReportType() == '_GET_ORDERS_DATA_')  && $reportInfo->isSetReportId() ) 
                        {
                            return  $reportInfo->getReportId();
                            
                        }
                    }
                } 
                else
                {
					return false;
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
 
}
?>
