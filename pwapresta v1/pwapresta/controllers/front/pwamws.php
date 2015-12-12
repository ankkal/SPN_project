<?php

class PwaprestaPwamwsModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;

	public function __construct($type = null)
	{
		parent::__construct();
		if($type != 'byepass')
		{
			if(Configuration::get('PWAPRESTA_PWAPRESTA_ORDER_UPDATE_API') == 'IOPN')
			{
				echo "Kindly enable MWS in plugin setting";
				exit;
			}
			$this->context = Context::getContext();
		}
	}

	
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();
		$this->define_plugin_constant();
		if (Tools::isSubmit('action'))
		{
			switch(Tools::getValue('action'))
			{
				case 'pwa_mws_report_schedule':
					$this->schedule_report_api();
					break;	
					
				case 'pwa_mws_report':
					$this->get_report_request_list_api();
					break;

				case 'pwa_mws_order':
					$this->order_list_api();
					break;		
			}
		}
	}


	public function define_plugin_constant()
	{
		if ( ! defined( 'AWS_ACCESS_KEY_ID' ) ) 
		define('AWS_ACCESS_KEY_ID', Configuration::get('PWAPRESTA_PWAPRESTA_ACCESS_KEY'));
		
		if ( ! defined( 'AWS_SECRET_ACCESS_KEY' ) ) 
		define('AWS_SECRET_ACCESS_KEY', Configuration::get('PWAPRESTA_PWAPRESTA_SECRET_KEY'));
		
		if ( ! defined( 'APPLICATION_NAME' ) ) 
		define('APPLICATION_NAME', 'pwa_mws');
		
		if ( ! defined( 'APPLICATION_VERSION' ) ) 
		define('APPLICATION_VERSION', '1.0.0');
		
		if ( ! defined( 'MERCHANT_ID' ) ) 
		define('MERCHANT_ID', Configuration::get('PWAPRESTA_PWAPRESTA_MERCHANT_ID'));
		
		if ( ! defined( 'MARKETPLACE_ID' ) ) 
		define('MARKETPLACE_ID', Configuration::get('PWAPRESTA_PWAPRESTA_MARKETPLACE_ID'));
		
		if ( ! defined( 'PLUGIN_PATH' ) ) 
		define('PLUGIN_PATH',dirname(__FILE__).'/mws_report/');
		
		if ( ! defined( 'PLUGIN_PATH_ORDER_MWS' ) ) 
		define('PLUGIN_PATH_ORDER_MWS',dirname(__FILE__).'/mws/');
		
		if ( ! defined( 'MWS_ENDPOINT_URL' ) ) 
		define('MWS_ENDPOINT_URL','https://mws.amazonservices.in/');
	}


	/*
	 * Schedule Reports on seller central
	 */
	public function schedule_report_api() {
		require_once('mws_report/ManageReportSchedule.php');
		$report_schedule = new Manage_Report_Schedule();
		$report_schedule->init();
		exit;
	}
	
	
	/*
	 * Fetch Report Request Lists from seller central
	 */
	public function get_report_request_list_api() {
		require_once('mws_report/GetReportRequestList.php');
		$report_request_list = new Get_Report_Request_List();
		$report_request_list->init_create_orders();
		exit;
	}
	
	
	/*
	 * Fetch Reports from seller central
	 */
	public function get_report_list_api($ReportRequestId) {
		require_once('mws_report/GetReportList.php');
		$report_list = new Get_Report_List();
		return $report_list->init_get_report_list($ReportRequestId);
	}
	
	
	/*
	 * Fetch Reports from seller central
	 */
	public function get_report_api($ReportId) {
		require_once('mws_report/GetReport.php');
		$report = new Get_Report();
		return $report->init_get_report($ReportId);
	}
	

	/*
	 * Fetch MWS order api
	 */
	public function order_list_api()
	{
		require_once('mws/ListOrders.php');
		$mws_order = new List_Orders();
		$mws_order->init_mws_order();
		exit;
	}
	
	/*
	 * Fetch MWS order api
	 */
	public function check_order_exist($order_id)
	{
		$this->define_plugin_constant();
		require_once('mws/GetOrder.php');
		$mws_order = new Get_Order();
		return $mws_order->init($order_id);
	}
}
