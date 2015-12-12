<?php

class PwaprestaPwaiopnModuleFrontController extends ModuleFrontController
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
				case 'pwa_iopn':
					$this->pwa_iopn($_POST);
					break;	
					
				case 'pwa_iopn_test':
					$this->pwa_iopn_test();
					break;		
			}
		}
	}

	public function pwa_iopn($data)
	{
		include_once( 'iopn/pwa-iopn.php' );
		
		if(Configuration::get('PWAPRESTA_PWAPRESTA_ORDER_UPDATE_API') == 'IOPN')
		{
			if(!empty($data)) {
				$param['message'] = 'IOPN Notifications : IOPN function called with some POST data.';
				$obj = new Pwapresta();
				$obj->generate_log($param);
				
				$data1 = json_encode($data);
				$filename = '1_iopn_non';
				$myfile = fopen($filename, "w");
				fwrite($myfile, $data1);
				fclose($myfile);
						
				$iopn = new PWA_Iopn();
				$iopn->notifications($data); 
			}else{
				$param['message'] = 'IOPN Notifications : IOPN function called without POST data.';
				$obj = new Pwapresta();
				$obj->generate_log($param);
			}
		}
		exit;
	}
	
	public function pwa_iopn_test()
	{
		$data =  '';
		$data = json_decode($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1/prestashop/module/pwapresta/pwaiopn?action=pwa_iopn");
		curl_setopt($ch, CURLOPT_POST, 6);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);
		curl_close($ch);
		exit;
	}

}
