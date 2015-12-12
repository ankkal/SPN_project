<?php

require_once('config.inc.php');

class Manage_Report_Schedule  extends  PwaprestaPwamwsModuleFrontController {
	
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
		require_once('MarketplaceWebService/Model/ManageReportScheduleRequest.php');
		
	}
	
	function init()
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
	 

		$request = new MarketplaceWebService_Model_ManageReportScheduleRequest();
		$request->setMerchant(MERCHANT_ID);
		$request->setReportType('_GET_ORDERS_DATA_');
		$request->setSchedule('_15_MINUTES_');
		$request->setScheduleDate(new DateTime('now', new DateTimeZone('UTC')));
		
		$this->invokeManageReportSchedule($service, $request);
	}

    function invokeManageReportSchedule(MarketplaceWebService_Interface $service, $request) 
    {
		  try {
				  $response = $service->manageReportSchedule($request);
				  
					echo("        ManageReportScheduleResponse\n");
					if ($response->isSetManageReportScheduleResult()) { 
						echo("            ManageReportScheduleResult\n");
						$manageReportScheduleResult = $response->getManageReportScheduleResult();
						if ($manageReportScheduleResult->isSetCount()) 
						{
							echo("                Count\n");
							echo("                    " . $manageReportScheduleResult->getCount() . "\n");
						}
						$reportScheduleList = $manageReportScheduleResult->getReportScheduleList();
						foreach ($reportScheduleList as $reportSchedule) {
							echo("                ReportSchedule\n");
							if ($reportSchedule->isSetReportType()) 
							{
								echo("                    ReportType\n");
								echo("                        " . $reportSchedule->getReportType() . "\n");
							}
							if ($reportSchedule->isSetSchedule()) 
							{
								echo("                    Schedule\n");
								echo("                        " . $reportSchedule->getSchedule() . "\n");
							}
							if ($reportSchedule->isSetScheduledDate()) 
							{
								echo("                    ScheduledDate\n");
								echo("                        " . $reportSchedule->getScheduledDate()->format(DATE_FORMAT) . "\n");
							}
						}
					} 

		 } catch (MarketplaceWebService_Exception $ex) {
			 
			 $message  =  'MWS Report API : Caught Exception : '.$ex->getMessage(). "\n";
			 $message .= "Response Status Code: " . $ex->getStatusCode() . "\n";
			 $message .= "Error Code: " . $ex->getErrorCode() . "\n";
			 $message .= "Error Type: " . $ex->getErrorType() . "\n";

			 $param['message'] = $message;
			 $obj = new Pwapresta();
			 $obj->generate_log($param);
			 
			 echo $message;
		 }
   }
   
}
            
