<?php

if (!defined('_PS_VERSION_')){
	exit;
}
	

/**
 * Class Pwapresta
 *
 * Module class
 */
class Pwapresta extends PaymentModule {

	const PREFIX = 'pwapresta_';
  	protected $html = '';


	/**
	 * create module object 
	 */
	public function __construct()
	{
		$this->name = 'pwapresta';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'Amazon';
		$this->controllers = array('pwaiopn','pwaorder');
		
		$this->need_instance = 1;
		$this->is_configurable = 1;
		
		$this->bootstrap = true;
		parent::__construct();
		
		$this->ps_versions_compliancy = array('min' => '1.6.0.1', 'max' => '1.6.1.3');
		$this->displayName = $this->l('Pay With Amazon');
		$this->description = $this->l('Accept online payments easily and securely with an Amazon payment gateway.');
		$this->confirmUninstall = $this->l('Are you sure about removing these details?');
		
		
	}

	/**
	 * install module, register hooks, set default config values
	 *
	 * @return bool
	 */
	public function install()
	{
		$this->installDB();
		
		$source = dirname(__file__).'/99.gif';
		$target = dirname(__file__).'/../../img/os/99.gif';
		copy($source,$target);
		
		if (!parent::install() || 
		    !$this->registerHook('displayHeader') ||
		    !$this->registerHook('displayBackOfficeHeader') || 
		    !$this->registerHook('displayShoppingCart') ||
		    !$this->registerHook('displayAdminOrder') ||
		    !$this->registerHook('actionOrderStatusPostUpdate') ||
		    !$this->registerHook('displayPayment'))
			return false;
		return true;
	}


	/**
	 * uninstall module
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		$this->uninstallDB();
		
		if(!Configuration::deleteByName('PWAPRESTA_PWAPRESTA_ENABLE') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_MERCHANT_ID') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_ACCESS_KEY') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_SECRET_KEY') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_MARKETPLACE_ID') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_ENVIRONMENT') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_HIDDEN_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_SUCCESS_RETURN_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_ORDER_UPDATE_API') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_IOPN_DUMP') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_IOPN_DUMP_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_IOPN_MERCHANT_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_MWS_ORDER_DUMP') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_MWS_ORDER_DUMP_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_MWS_ORDER_API_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_MWS_REPORT_DUMP') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_MWS_REPORT_DUMP_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_MWS_REPORT_API_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_MWS_SCHEDULE_REPORT_API_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_SHOW_CART_BUTTON') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_CUSTOM_PWA_IMAGE') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_CUSTOM_PWA_IMAGE_URL') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_CUSTOM_HTML_CODE_DATA') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_BTN_SHOW') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_BTN_COLOR') ||
		   !Configuration::deleteByName('PWAPRESTA_PWAPRESTA_BTN_SIZE') ||
		   !parent::uninstall() )
			return false;
		return true;
	}


	/**
	 * alias for Configuration::get()
	 *
	 * @param $name
	 * @return mixed
	 */
	public static function getConfig($name)
	{
		return Configuration::get(Tools::strtoupper(self::PREFIX.$name));
	}
	

	/**
	 * alias for Configuration::updateValue()
	 *
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public static function setConfig($name, $value)
	{
		return Configuration::updateValue(Tools::strtoupper(self::PREFIX.$name), $value);
	}
	

	/**
	 * return html with configuration
	 *
	 * @return string
	 */
	public function getContent()
	{
		if(!empty($_FILES)) 
		{
			define('IMAGE_FOLDER_URL',dirname(__file__).'/views/img/');
			$target_dir = IMAGE_FOLDER_URL;
			$target_file = $target_dir . basename($_FILES["pwapresta_custom_pwa_image_url"]["name"]);
			if( $_FILES["pwapresta_custom_pwa_image_url"]["name"] )
			{
				try 
				{
					move_uploaded_file($_FILES["pwapresta_custom_pwa_image_url"]["tmp_name"], $target_file); 
					chmod($target_file , 0777);
					$_POST['pwapresta_custom_pwa_image_url'] = $_FILES["pwapresta_custom_pwa_image_url"]["name"];
				}
				catch(Exception $e) 
				{
					echo 'error in uploading file';
				}  
			}
		}
        
		$this->postProcess();
		$helper = $this->initForm();
		foreach ($this->fields_form as $field_form)
		{
			foreach ($field_form['form']['input'] as $input)
				$helper->fields_value[$input['name']] = $this->getConfig(Tools::strtoupper($input['name']));
		}

		$this->html .= $helper->generateForm($this->fields_form);
		return $this->html;
	}


	/**
	 * helper with configuration
	 *
	 * @return HelperForm
	 */
	private function initForm()
	{
		$helper = new HelperForm();
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->identifier = $this->identifier;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->toolbar_scroll = true;
		$helper->toolbar_btn = $this->initToolbar();
		$helper->title = $this->displayName;
		$helper->submit_action = 'submitUpdate';

		$siteurls =  _PS_BASE_URL_.__PS_BASE_URI__;
		$site =  _PS_BASE_URL_.__PS_BASE_URI__;
		
		//check url contains http if not then appends 
        if(! preg_match ('/http/',$siteurls))
        {
            $siteurls ="https://".$siteurls;
        }
        else
        {   // ipon url must be https 
			$site_url_arr = explode(':',$siteurls);
			$siteurl_iopn ="https:".$site_url_arr[1];
		}
		
		// check url end with "/" if not then append
        if (substr($siteurls, -1) != '/') {
            $siteurls =$siteurls."/";   
        }
        if (substr($siteurl_iopn, -1) != '/') {
            $siteurl_iopn =$siteurl_iopn."/";    
        }
        
        if(Configuration::get('PWAPRESTA_PWAPRESTA_CUSTOM_PWA_IMAGE_URL') != '')
        {
			define('IMAGE_URL',$site.'modules/pwapresta/views/img/');
   			$preview = '<img src="'.IMAGE_URL.Configuration::get('PWAPRESTA_PWAPRESTA_CUSTOM_PWA_IMAGE_URL').'" width="100px" >';
		}
		else
		{
			$preview = '';
		}

		$this->fields_form[0]['form'] = array(
			'tinymce' => true,
			'legend' => array('title' => $this->l('Amazon settings'), 'image' => 'https://paywithamazon.amazon.in/gp/cba/button?size=medium&color=orange&background=white'),
			'submit' => array(
				'name' => 'submitUpdate',
				'title' => $this->l('   Save   ')
			),
			'input' => array(
				array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Enable'), 'value' => 1, 'id' => self::PREFIX.'enable'),
						array('label' => $this->l('Disable'), 'value' => 0, 'id' => self::PREFIX.'disable'),
					),
					'is_bool' => false,
					'class' => 't',
					'label' => $this->l('Enable/Disable'),
					'name'  => self::PREFIX.'enable',
					'desc'  => $this->l('Enable Pay With Amazon'), 
				),
				array(
					'type' => 'text',
					'label' => $this->l('Merchant ID:'),
					'name' => self::PREFIX.'merchant_id',
					'desc' => $this->l('Merchant Id given by amazon payments.'),
					'size' => 64,
					'required' => true,
				),
				array(
					'type' => 'text',
					'label' => $this->l('Access Key:'),
					'name' => self::PREFIX.'access_key',
					'desc' => $this->l('An application identifier associates your site, and Amazon application.'),
					'size' => 64,
					'required' => true,
				),
				array(
					'type' => 'text',
					'label' => $this->l('Secret Key:'),
					'name' => self::PREFIX.'secret_key',
					'desc' => $this->l('The secret code from Amazon.'),
					'size' => 64,
					'required' => true,
				),
				array(
					'type' => 'select',
					'label' => $this->l('Marketplace ID:'),
					'name' => self::PREFIX.'marketplace_id',
					'desc' => $this->l('The Marketplace ID from Amazon.'),
					'options' => array(
                    'query' => array(
                        array(
                            'id' => 'AXGTNDD750VEM',
                            'name' => $this->l('Sandbox / AXGTNDD750VEM'),
                        ),
                        array(
                            'id' => 'A3PY9OQTG31F3H',
                            'name' => $this->l('Production / A3PY9OQTG31F3H'),
                        ),
                    ),
                    'id' => 'id',
                    'name' => 'name'
                  )
					
				),
				array(
				  'type' => 'select',                             
				  'label' => $this->l('Environment'),   
				  'name' => self::PREFIX.'environment',                   
				  'desc'    => $this->l("In Production environment order will be placed at seller central in Production view 
									 In Sandbox environment order will be placed at seller central in Sandbox view."),
                  'options' => array(
                    'query' => array(
                        array(
                            'id' => 'sandbox',
                            'name' => $this->l('Sandbox'),
                        ),
                        array(
                            'id' => 'production',
                            'name' => $this->l('Production'),
                        ),
                    ),
                    'id' => 'id',
                    'name' => 'name'
                  )

				),
				array(
				  'type' => 'select',                             
				  'label' => $this->l('Show Pay With Amazon button when'),   
				  'name' => self::PREFIX.'btn_show', 
				  'options' => array(
				    'query' => array(
				        array(
				    		'id' => 'notlogged',
				    		'name' => $this->l('Not Logged In'),
				    	),
				    	array(
				    	 	'id' => 'loggeed',
				    	 	'name' => $this->l('Logged In'),
				    	),
				    ),                         
				    'id' => 'id',          // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
				    'name' => 'name'       // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
				  )
				),
			),
		);


		$this->fields_form[1]['form'] = array(
			'tinymce' => true,
			'legend' => array('title' => $this->l('IOPN And MWS settings'), 'image' => 'https://paywithamazon.amazon.in/gp/cba/button?size=medium&color=orange&background=white'),
			'submit' => array(
				'name' => 'submitUpdate',
				'title' => $this->l('   Save   ')
			),
			'input' => array(
			array(
					'type' => 'text',
					'label' => $this->l('Successful Payment Return Url:'),
					'name' => self::PREFIX.'success_return_url',
					'desc' => $this->l('Use this url in amazon seller central settings.'),
					'size' => 64,
				),
				array(
                    'name' => self::PREFIX.'order_update_api',
                    'type' => 'select',
                    'label' => $this->l('Use IOPN or MWS Report API:'),
                    'desc'  => $this->l('IOPN will be preferred over MWS. But IOPN will only work if SSL is enabled on server. 
								MWS is cron based so you need to setup cron. So It will only update the details when cron will run.'),
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 'IOPN',
                                'name' => $this->l('IOPN'),
                            ),
                            array(
                                'id' => 'MWS',
                                'name' => $this->l('MWS'),
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Enable'), 'value' => 1, 'id' => self::PREFIX.'enable'),
						array('label' => $this->l('Disable'), 'value' => 0, 'id' => self::PREFIX.'disable'),
					),
					'is_bool' => false,
					'class' => 't',
					'label' => $this->l('Enable IOPN for debugging purpose:'),
					'name'  => self::PREFIX.'iopn_dump',
					'desc'  => $this->l('Will be in effect only when IOPN is enabled.'), 
				),
                array(
                	'name'  => self::PREFIX.'iopn_dump_url',
                	'type'  => 'text',
                	'label' => $this->l('Set Path for IOPN dump file:'),
                	'desc'  => $this->l('Type the path of folder for IOPN dump file.'),
                	'placeholder' => 'modules/pwapresta/iopn_dump',
                ),
                array(
                	'name'  => self::PREFIX.'iopn_merchant_url',
                	'type'  => 'text',
                	'label' => $this->l('IOPN Merchant Url:'),
                ),
               array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Enable'), 'value' => 1, 'id' => self::PREFIX.'enable'),
						array('label' => $this->l('Disable'), 'value' => 0, 'id' => self::PREFIX.'disable'),
					),
					'is_bool' => false,
					'class' => 't',
					'label' => $this->l('Enable to generate MWS Order Dump file:'),
					'name'  => self::PREFIX.'mws_order_dump',
				),
                array(
                	'name'  => self::PREFIX.'mws_order_dump_url',
                	'type'  => 'text',
                	'label' => $this->l('Set Path for MWS Order dump file:'),
                	'desc'  => $this->l('Type the path of folder for MWS Order dump file.'),
                	'placeholder' => 'modules/pwapresta/mws_order_dump',
                ),       
                array(
                	'name'  => self::PREFIX.'mws_order_api_url',
                	'type'  => 'text',
                	'label' => $this->l('MWS Order API Url:'),
                ),   
                
                 array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Enable'), 'value' => 1, 'id' => self::PREFIX.'enable'),
						array('label' => $this->l('Disable'), 'value' => 0, 'id' => self::PREFIX.'disable'),
					),
					'is_bool' => false,
					'class' => 't',
					'label' => $this->l('Enable to generate MWS Report Dump file:'),
					'name'  => self::PREFIX.'mws_report_dump',
				),
                array(
                	'name'  => self::PREFIX.'mws_report_dump_url',
                	'type'  => 'text',
                	'label' => $this->l('Set Path for MWS Report dump file:'),
                	'desc'  => $this->l('Type the path of folder for MWS Report dump file.'),
                	'placeholder' => 'modules/pwapresta/mws_report_dump',
                ), 
                      
                array(
                	'name'  => self::PREFIX.'mws_report_api_url',
                	'type'  => 'text',
                	'label' => $this->l('MWS Report API Url:'),
                ), 
                
                array(
					'type' => 'text',
					'label' => $this->l('MWS Schedule Report API Url Url:'),
					'name' => self::PREFIX.'mws_schedule_report_api_url',
				),         
               
			),
		);

		$this->fields_form[2]['form'] = array(
			'tinymce' => true,
			'legend' => array('title' => $this->l('PWA Button settings'), 'image' => 'https://paywithamazon.amazon.in/gp/cba/button?size=medium&color=orange&background=white'),
			'submit' => array(
				'name' => 'submitUpdate',
				'title' => $this->l('   Save   ')
			),
			'input' => array(
			  array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Enable'), 'value' => 1, 'id' => self::PREFIX.'enable'),
						array('label' => $this->l('Disable'), 'value' => 0, 'id' => self::PREFIX.'disable'),
					),
					'is_bool' => false,
					'class' => 't',
					'label' => $this->l('Enable/Disable PWA button on cart page:'),
					'name'  => self::PREFIX.'show_cart_button',
				),
                array(
					'type' => 'radio',
					'values' => array(
						array('label' => '<img src="https://paywithamazon.amazon.in/gp/cba/button?size=medium&color=orange&background=white">', 'value' => 'orange', 'id' => self::PREFIX.'btn_color_orange'),
						array('label' => '<img src="https://paywithamazon.amazon.in/gp/cba/button?size=medium&color=tan&background=white">', 'value' => 'tan', 'id' => self::PREFIX.'btn_color_tan'),
					),
					'is_bool' => false,
					'class' => 't',
					'label' => $this->l('Choose a colour for the button:'),
					'name'  => self::PREFIX.'btn_color',
				),
				array(
					'type' => 'radio',
					'values' => array(
						array('label' => '<img src="https://paywithamazon.amazon.in/gp/cba/button?size=medium&color=orange&background=white">', 'value' => 'medium', 'id' => self::PREFIX.'btn_size_medium'),
						array('label' => '<img src="https://paywithamazon.amazon.in/gp/cba/button?size=large&color=orange&background=white">', 'value' => 'large', 'id' => self::PREFIX.'btn_size_large' ,'checked' => 'checked'),
						array('label' => '<img src="https://paywithamazon.amazon.in/gp/cba/button?size=x-large&color=orange&background=white">', 'value' => 'x-large', 'id' => self::PREFIX.'btn_size_xlarge'),
					),
					'is_bool' => false,
					'class' => 't',
					'label' => $this->l('Choose a size for the button:'),
					'name'  => self::PREFIX.'btn_size',
				),
				array(
					'name'  => self::PREFIX.'hidden_url',
					'type' => 'read-only',
					'label' =>  '<input type= "hidden" value ="'.$siteurls.'" id="pwapresta_readonly_url"><input type= "hidden" value ="'.$siteurl_iopn.'" id="pwapresta_readonly_iopn_url">',
				),
				array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Enable'), 'value' => 1, 'id' => self::PREFIX.'enable'),
						array('label' => $this->l('Disable'), 'value' => 0, 'id' => self::PREFIX.'disable'),
					),
					'is_bool' => false,
					'class' => 't',
					'label' => $this->l('Enable custom image for PWA button :'),
					'name'  => self::PREFIX.'custom_pwa_image',
				),
				 array(
                	'name'  => self::PREFIX.'custom_pwa_image_url',
                	'type'  => 'file',
                	'label' => $this->l('Choose custom PWA button image:'),
                	'desc'  => '<div id="pwa_btn_img_error" class ="error"></div>'.$preview,
                ),
                array(
                	'name'  => self::PREFIX.'custom_html_code_data',
                	'type'  => 'textarea',
                	'label' => $this->l('Edit CSS code for PWA button:'),
                	'desc'  => $this->l('You can edit this CSS to change the design of PWA Button but, make sure your CSS is right.'),
                	'placeholder' => '',
                ), 
				
			),
		);
		return $helper;
	}


	/**
	 * PrestaShop way save button
	 *
	 * @return mixed
	 */
	private function initToolbar()
	{
		$toolbar_btn = array();
		$toolbar_btn['save'] = array('href' => '#', 'desc' => $this->l('Save'));
		return $toolbar_btn;
	}


	/**
	 * save configuration values
	 */
	protected function postProcess()
	{
		if (Tools::isSubmit('submitUpdate'))
		{
			$data = $_POST;
			if (is_array($data))
			{
				foreach ($data as $key => $value)
				{
					self::setConfig($key, $value);
				}
			}

			Tools::redirectAdmin('index.php?tab=AdminModules&conf=4&configure='.$this->name.
			'&token='.Tools::getAdminToken('AdminModules'.
			(int)Tab::getIdFromClassName('AdminModules').(int)$this->context->employee->id));
		}
	}


    public function hookDisplayShoppingCart()
    {
    	 //check PWA is enable if yes then show Pay With Amazon button on cart page
		 if ( Configuration::get('PWAPRESTA_PWAPRESTA_ENABLE') )
		 { 
			 if( Configuration::get('PWAPRESTA_PWAPRESTA_SHOW_CART_BUTTON') )			
	         {
				 if( Configuration::get('PWAPRESTA_PWAPRESTA_BTN_SHOW') == 'loggeed' && $this->context->customer->isLogged())
				 {
					 if ( ! defined( 'PWA_MODULE_DIR' ) ) {
						define( 'PWA_MODULE_DIR' , dirname(__file__));
					 }
					 include_once( 'includes/class-pwapresta.php' );
					 $cba = new PWA_Cba();   
					 return $cba->pay_with_amazon_button('cart');
				 }
				 
				 if( Configuration::get('PWAPRESTA_PWAPRESTA_BTN_SHOW') == 'notlogged')
				 {
					 if ( ! defined( 'PWA_MODULE_DIR' ) ) {
						define( 'PWA_MODULE_DIR' , dirname(__file__));
					 }
					 include_once( 'includes/class-pwapresta.php' );
					 $cba = new PWA_Cba();   
					 return $cba->pay_with_amazon_button('cart');
				 }
			 }
		     
		 }
    }
    
    
    public function hookdisplayPayment()
    {
    	 //check PWA is enable if yes then show Pay With Amazon button on cart page
		 if ( Configuration::get('PWAPRESTA_PWAPRESTA_ENABLE') )
		 {
	        if ( ! defined( 'PWA_MODULE_DIR' ) ) {
				define( 'PWA_MODULE_DIR' , dirname(__file__));
			 }
		     include_once( 'includes/class-pwapresta.php' );
			 $cba = new PWA_Cba();   
		     return $cba->pay_with_amazon_button('checkout');
		 }
    }


	/**
	 * use this for Backoffice
	 */
	public function hookdisplayBackOfficeHeader() {
	    $this->context->controller->addCSS(($this->_path) .'views/css/style.css');
	    $this->context->controller->addJS(($this->_path) .'views/js/add_jquery.js');
	    $this->context->controller->addJS(($this->_path) .'views/js/pwapresta_settings.js');
	}
	
	/**
	 * use this for Backoffice
	 */
	public function hookdisplayHeader() {
	    $this->context->controller->addCSS(($this->_path) .'views/css/style.css');
	}
	
	
	/**
	 * use this for Backoffice
	 */
	public function hookdisplayAdminOrder($hook)
	{
		$prefix    = _DB_PREFIX_;
		
		$sql = 'select * from `'.$prefix.'pwa_orders` where prestashop_order_id = "'.$hook['id_order'].'" ';
		$results = Db::getInstance()->ExecuteS($sql);
		if(!empty($results)) {
			
			$AmazonOrderId = $results[0]['amazon_order_id'];
			$ShippingType  = $results[0]['shipping_service'];
			$OrderType  = $results[0]['order_type'];
			
			$this->context->smarty->assign(array(
											'AmazonOrderId' => $AmazonOrderId,
											'ShippingType' => $ShippingType,
											'OrderType' => $OrderType
											));
			return $this->display(__FILE__, 'views/templates/hooks/displayAdminOrder.tpl');	 
		}
	}
	
	
	public function hookactionOrderStatusPostUpdate($hook)
	{
		$prefix    = _DB_PREFIX_;
		
		$status   = $hook['newOrderStatus']->id;
		$order_id = $hook['id_order'];
		
		// Order Cancelled
		if($status == 6){
			if (strpos('pwa_mws_order',$_SERVER['REQUEST_URI']) !== false)
			{
				
			}
			else
			{
				$sql = 'select * from `'.$prefix.'pwa_orders` where prestashop_order_id = "'.$order_id.'" ';
				$results = Db::getInstance()->ExecuteS($sql);
				if(!empty($results)) {
					$this->pwa_cancel_feed($order_id);
				}
			}
		}
		
		// Order Shipped
		if($status == 4){
			$sql = 'select * from `'.$prefix.'pwa_orders` where prestashop_order_id = "'.$order_id.'" ';
			$results = Db::getInstance()->ExecuteS($sql);
			if(!empty($results)) {
				$this->pwa_shipment_feed($order_id);
			}
		}
		
		//Order Refund
		if($status == 7){
			$sql = 'select * from `'.$prefix.'pwa_orders` where prestashop_order_id = "'.$order_id.'" ';
			$results = Db::getInstance()->ExecuteS($sql);
			if(!empty($results)) {
				$this->pwa_cancel_feed($order_id);
			}
		}
	}
	
	
	public function define_constants(){
		 if ( Configuration::get('PWAPRESTA_PWAPRESTA_ENABLE') ){
			 
			 if ( ! defined( 'PWA_MODULE_DIR' ) ) 
	         define('PWA_MODULE_DIR' , dirname(__file__));
	         
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
			 
			 if ( ! defined( 'MWS_ENDPOINT_URL' ) ) 
			 define('MWS_ENDPOINT_URL','https://mws.amazonservices.in/');
			 
			 if ( ! defined( 'PLUGIN_PATH' ) ) 
			 define('PLUGIN_PATH',dirname(__FILE__).'/controllers/front/mws_report/');
			 //define('PLUGIN_PATH',dirname(__FILE__).'/mwsfeed/');
			 
		  }
	}
	
	
	/*
	 * MWS Feed API to acknowledge an order
	 */
	public function pwa_acknowledge_feed($acknowledge_arr) {
		
		$prefix    = _DB_PREFIX_;
		include_once( 'controllers/front/pwa-mws-feed.php' );
		//include_once( 'mwsfeed/pwa-mws-feed.php' );
		$this->define_constants();
		
		$sql = 'select * from `'.$prefix.'pwa_orders` where prestashop_order_id = "'.$acknowledge_arr['MerchantOrderID'].'" ';
		$results = Db::getInstance()->ExecuteS($sql);
		
		$acknowledge_arr['AmazonOrderID'] = $results[0]['amazon_order_id'];
        $acknowledge_arr['StatusCode'] = 'Success';
        
		$mws_feed = new PWA_Mws_Feed();
		$mws_feed->submit_acknowledge_feed($acknowledge_arr);
	}
	
	
	/*
	 * MWS Feed API to cancel an order
	 */
	public function pwa_cancel_feed($order_id) {
		
		$prefix    = _DB_PREFIX_;
		include_once( 'controllers/front/pwa-mws-feed.php' );
		//include_once( 'mwsfeed/pwa-mws-feed.php' );
		$this->define_constants();
		
		$sql = 'select * from `'.$prefix.'pwa_orders` where prestashop_order_id = "'.$order_id.'" ';
		$results = Db::getInstance()->ExecuteS($sql);
		
		$param['AmazonOrderID'] = $results[0]['amazon_order_id'];
        $param['MerchantOrderID'] = $order_id;
        $param['StatusCode'] = 'Failure';
        
		$mws_feed = new PWA_Mws_Feed();
		$mws_feed->submit_cancel_feed($param);
	}
	
	
	/*
	 * MWS Feed API to ship an order
	 */
	public function pwa_shipment_feed($order_id) {
		
		$prefix    = _DB_PREFIX_;
		include_once( 'controllers/front/pwa-mws-feed.php' );
		//include_once( 'mwsfeed/pwa-mws-feed.php' );
		$this->define_constants();
		
		$sql = 'select * from `'.$prefix.'pwa_orders` where prestashop_order_id = "'.$order_id.'" ';
		$results = Db::getInstance()->ExecuteS($sql);
		
		$order = new Order($order_id);
		$carrier = new Carrier($order->id_carrier);
		
		$param['AmazonOrderID'] = $results[0]['amazon_order_id'];
        $param['MerchantOrderID'] = $order_id;
        $param['CarrierName'] = $carrier->name;
        $param['ShippingMethod'] = $results[0]['shipping_service'];
        $param['ShipperTrackingNumber'] = $order->shipping_number;
        
		$param['FulfillmentDate'] = gmdate("Y-m-d\TH:i:s", time());
        
		$mws_feed = new PWA_Mws_Feed();
		$mws_feed->submit_shipment_feed($param);
	}
	
	
	/*
	 * MWS Feed API to ship an order
	 */
	public function pwa_refund_feed($param) {
		
		$prefix    = _DB_PREFIX_;
		include_once( 'controllers/front/pwa-mws-feed.php' );
		//include_once( 'mwsfeed/pwa-mws-feed.php' );
		$this->define_constants();
		
		$sql = 'select * from `'.$prefix.'pwa_orders` where prestashop_order_id = "'.$param['MerchantOrderID'].'" ';
		$results = Db::getInstance()->ExecuteS($sql);
		
		$param['AmazonOrderID'] = $results[0]['amazon_order_id'];
       
		$mws_feed = new PWA_Mws_Feed();
		$mws_feed->submit_refund_feed($param);
	}
	
	/*
	 * MWS Order API to check that an order exist on seller central or not.
	 */
	public function pwa_order_exist($order_id) {
		
		include_once( 'controllers/front/pwamws.php' );
		$mws_order = new PwaprestaPwamwsModuleFrontController('byepass');
		return $mws_order->check_order_exist($order_id);
	}
	

	public function installDB()
	{
		Db::getInstance()->execute('	
		CREATE TABLE IF NOT EXISTS `'. _DB_PREFIX_ .'pwa_orders` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `prestashop_order_id` varchar(100) NOT NULL,
		  `amazon_order_id` varchar(100) NOT NULL,
		  `shipping_service` varchar(100) NOT NULL,
		 `_non_received` varchar(10) NOT NULL DEFAULT "0",
		 `order_type` varchar(50) NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;');
		
		Db::getInstance()->execute('	
		CREATE TABLE IF NOT EXISTS `'. _DB_PREFIX_ .'pwa_order_products` (
		  `id_cart` int(11) NOT NULL,
		  `id_product` int(11) NOT NULL,
		  `id_product_attribute` int(11) NOT NULL,
		 `quantity` int(11) NOT NULL,
		 `amount` varchar(50) NOT NULL,
		 `amount_excl` varchar(50) NOT NULL,
		 `sku` varchar(100) NOT NULL,
		 `title` varchar(100) NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;');
		
		Db::getInstance()->execute('	
		CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pwa_iopn_records` (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `uuid` varchar(100) NOT NULL,
		  `timestamp` datetime NOT NULL,
		  `notification_type` varchar(50) NOT NULL,
		  `notification_reference_id` varchar(100) NOT NULL,
		  `amazon_order_id` varchar(50) NOT NULL,
		  `status` varchar(20) NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;');
		
		Db::getInstance()->execute('	
		CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mws_report_cron` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `created_before` varchar(80) NOT NULL,
		  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;');
		
		Db::getInstance()->execute('	
		CREATE TABLE IF NOT EXISTS `'. _DB_PREFIX_ .'mws_order_cron` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
					 `created_before` varchar(80) NOT NULL,
					 `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					 PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";');
		
		$sql = 'select * from `' . _DB_PREFIX_ . 'order_state` where `id_order_state` = "99" ';
		$results = Db::getInstance()->ExecuteS($sql);
		
		if(empty($results))
		{
			Db::getInstance()->execute('
			INSERT into `' . _DB_PREFIX_ . 'order_state` set
			`id_order_state` = 99,
			`invoice` = 0,
			`send_email` = 0,
			`module_name` = "pwa",
			`color` = "#DDEEFF",
			`hidden` = 0,
			`logable` = 1,
			`delivery` = 0,
			`shipped` = 0,
			`paid` = 0
			');
			
			Db::getInstance()->execute('
			INSERT into `' . _DB_PREFIX_ . 'order_state_lang` set
			`id_order_state` = 99,
			`id_lang` = 1,
			`name` = "Awaiting PWA Payment",
			`template` = ""
			');
		}
		
	}
	
	public function uninstallDB()
	{
		//Db::getInstance()->execute('DROP TABLE IF EXISTS `'. _DB_PREFIX_ .'pwa_orders` ;');
		
		//Db::getInstance()->execute('DROP TABLE IF EXISTS `'. _DB_PREFIX_ .'pwa_order_products` ;');
		
		//Db::getInstance()->execute('DROP TABLE IF EXISTS `'. _DB_PREFIX_ .'pwa_iopn_records` ;');
		
		//Db::getInstance()->execute('DROP TABLE IF EXISTS `'. _DB_PREFIX_ .'mws_report_cron` ;');
		
		//Db::getInstance()->execute('DROP TABLE IF EXISTS `'. _DB_PREFIX_ .'mws_order_cron` ;');
	}
	
	
	/*
	 * Generate log for every activity of plugin
	 */ 
	public function generate_log($param) {
		
		$this->define_constants();
		$filename =  PWA_MODULE_DIR.'/pwa_error.log';	
		if(!file_exists($filename)) {
			$myfile = fopen($filename, "w");
			$entry = date('Y-m-d H:i:s').' : '.$param['message'].'';
			fwrite($myfile, $entry);
			fclose($myfile);	
		}else{
			$myfile = fopen($filename, "r+");
			$filedata = fread($myfile,filesize($filename));
			$entry = date('Y-m-d H:i:s').' : '.$param['message'].'';
			fwrite($myfile, $entry.PHP_EOL);
			fclose($myfile);
		}
	}
	
	

}
