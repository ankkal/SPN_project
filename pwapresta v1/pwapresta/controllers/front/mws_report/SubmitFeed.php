<?php


require_once('config.inc.php');

class Submit_Feed  {
	
	public $serviceUrl = MWS_ENDPOINT_URL;

	/**
	 * Constructor for the submit feed class.
	 */
	public function __construct() {
		$this->includes();
	}

	/**
	 * Include required core files and classes.
	 */
	public function includes() {
		require_once('MarketplaceWebService/Client.php');
		require_once('MarketplaceWebService/Model/SubmitFeedRequest.php');
	}
	
	/*
	 *Acknowledge Amazon seller central order with Merchant Order Id updation
	 */
	function acknowledge_feed($param)
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
 
$feed = '<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <DocumentVersion>1.02</DocumentVersion>
        <MerchantIdentifier>'.MERCHANT_ID.'</MerchantIdentifier>
    </Header>
    <MessageType>OrderAcknowledgement</MessageType>
    <Message>
        <MessageID>1</MessageID>
        <OrderAcknowledgement>
            <AmazonOrderID>'.$param['AmazonOrderID'].'</AmazonOrderID>
            <MerchantOrderID>'.$param['MerchantOrderID'].'</MerchantOrderID>
            <StatusCode>'.$param['StatusCode'].'</StatusCode>';
         foreach($param['items'] as $key => $value){
$feed.=  '<Item>
				<AmazonOrderItemCode>'.$value['AmazonOrderItemCode'].'</AmazonOrderItemCode>
				<MerchantOrderItemID>'.$value['product_id'].'</MerchantOrderItemID>
		  </Item>';
		 }
			
$feed.= '</OrderAcknowledgement>
    </Message>
</AmazonEnvelope>';

		$marketplaceIdArray = array("Id" => array(MARKETPLACE_ID));
		$feedHandle = @fopen('php://memory', 'rw+');
		fwrite($feedHandle, $feed);
		rewind($feedHandle);

		$request = new MarketplaceWebService_Model_SubmitFeedRequest();
		$request->setMerchant(MERCHANT_ID);
		$request->setMarketplaceIdList($marketplaceIdArray);
		$request->setFeedType('_POST_ORDER_ACKNOWLEDGEMENT_DATA_');
		$request->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));
		rewind($feedHandle);
		$request->setPurgeAndReplace(false);
		$request->setFeedContent($feedHandle);

		rewind($feedHandle);

		return $this->invokeSubmitFeed($service, $request);

		@fclose($feedHandle);                
    }
   
    /*
	 *Cancel Amazon seller central order with Merchant Order Id updation
	 */
    function cancel_feed($param)
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
 


$feed = '<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <DocumentVersion>1.02</DocumentVersion>
        <MerchantIdentifier>'.MERCHANT_ID.'</MerchantIdentifier>
    </Header>
    <MessageType>OrderAcknowledgement</MessageType>
    <Message>
        <MessageID>1</MessageID>
        <OrderAcknowledgement>
            <AmazonOrderID>'.$param['AmazonOrderID'].'</AmazonOrderID>
            <MerchantOrderID>'.$param['MerchantOrderID'].'</MerchantOrderID>
            <StatusCode>'.$param['StatusCode'].'</StatusCode>
		</OrderAcknowledgement>
    </Message>
</AmazonEnvelope>';

		$marketplaceIdArray = array("Id" => array(MARKETPLACE_ID));

		$feedHandle = @fopen('php://memory', 'rw+');
		fwrite($feedHandle, $feed);
		rewind($feedHandle);

		$request = new MarketplaceWebService_Model_SubmitFeedRequest();
		$request->setMerchant(MERCHANT_ID);
		$request->setMarketplaceIdList($marketplaceIdArray);
		$request->setFeedType('_POST_ORDER_ACKNOWLEDGEMENT_DATA_');
		$request->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));
		rewind($feedHandle);
		$request->setPurgeAndReplace(false);
		$request->setFeedContent($feedHandle);

		rewind($feedHandle);

		return $this->invokeSubmitFeed($service, $request);

		@fclose($feedHandle);                
    }
   
    /*
	 *Refund on Amazon seller central order
	 */
    function refund_feed($param)
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
 


$feed = '<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <DocumentVersion>1.02</DocumentVersion>
        <MerchantIdentifier>'.MERCHANT_ID.'</MerchantIdentifier>
    </Header>
    <MessageType>OrderAdjustment</MessageType>
    <Message>
        <MessageID>1</MessageID>
        <OrderAdjustment>
            <AmazonOrderID>'.$param['AmazonOrderID'].'</AmazonOrderID>';
            
            //BuyerCanceled,CustomerReturn
            
            foreach($param['items'] as $key => $value){
				
$feed.= '<AdjustedItem>
				<MerchantOrderItemID>'.$value['MerchantOrderItemID'].'</MerchantOrderItemID>
				<AdjustmentReason>CustomerReturn</AdjustmentReason>
				<ItemPriceAdjustments>
					<Component>
						<Type>Principal</Type>
						<Amount currency="INR">'.$value['Principal'].'</Amount>
					</Component>
					<Component>
						<Type>Tax</Type>
						<Amount currency="INR">'.$value['Tax'].'</Amount>
					</Component>
				</ItemPriceAdjustments>
				<QuantityCancelled>'.$value['quantity'].'</QuantityCancelled>
			</AdjustedItem>';
		}
			
$feed.= '</OrderAdjustment>
    </Message>
</AmazonEnvelope>';

		$marketplaceIdArray = array("Id" => array(MARKETPLACE_ID));

		$feedHandle = @fopen('php://memory', 'rw+');
		fwrite($feedHandle, $feed);
		rewind($feedHandle);

		$request = new MarketplaceWebService_Model_SubmitFeedRequest();
		$request->setMerchant(MERCHANT_ID);
		$request->setMarketplaceIdList($marketplaceIdArray);
		$request->setFeedType('_POST_PAYMENT_ADJUSTMENT_DATA_');
		$request->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));
		rewind($feedHandle);
		$request->setPurgeAndReplace(false);
		$request->setFeedContent($feedHandle);

		rewind($feedHandle);

		$this->invokeSubmitFeed($service, $request);

		@fclose($feedHandle);                
   }
   
   
   /*
	 *Refund on Amazon seller central order
	 */
    function shipment_feed($param)
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
 


$feed = '<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <DocumentVersion>1.02</DocumentVersion>
        <MerchantIdentifier>'.MERCHANT_ID.'</MerchantIdentifier>
    </Header>
    <MessageType>OrderFulfillment</MessageType>
    <Message>
        <MessageID>1</MessageID>
        <OrderFulfillment>
            <AmazonOrderID>'.$param['AmazonOrderID'].'</AmazonOrderID>
            <MerchantFulfillmentID>'.$param['MerchantOrderID'].'</MerchantFulfillmentID>
            <FulfillmentDate>'.$param['FulfillmentDate'].'</FulfillmentDate>
             <FulfillmentData>
				<CarrierName>'.$param['CarrierName'].'</CarrierName>
				<ShippingMethod>'.$param['ShippingMethod'].'</ShippingMethod>
				<ShipperTrackingNumber>'.$param['ShipperTrackingNumber'].'</ShipperTrackingNumber>
			</FulfillmentData>
        </OrderFulfillment>
    </Message>
</AmazonEnvelope>';

		$marketplaceIdArray = array("Id" => array(MARKETPLACE_ID));

		$feedHandle = @fopen('php://memory', 'rw+');
		fwrite($feedHandle, $feed);
		rewind($feedHandle);

		$request = new MarketplaceWebService_Model_SubmitFeedRequest();
		$request->setMerchant(MERCHANT_ID);
		$request->setMarketplaceIdList($marketplaceIdArray);
		$request->setFeedType('_POST_ORDER_FULFILLMENT_DATA_');
		$request->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));
		rewind($feedHandle);
		$request->setPurgeAndReplace(false);
		$request->setFeedContent($feedHandle);

		rewind($feedHandle);

		return $this->invokeSubmitFeed($service, $request);

		@fclose($feedHandle);                
   }
  
  
   function invokeSubmitFeed(MarketplaceWebService_Interface $service, $request) 
   {
	  try {
              $response = $service->submitFeed($request);
              
              if ($response->isSetSubmitFeedResult()) { 
                 
                    $submitFeedResult = $response->getSubmitFeedResult();
                    
                    if ($submitFeedResult->isSetFeedSubmissionInfo()) { 
                       
                        $feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
                        
                        if ($feedSubmissionInfo->isSetFeedSubmissionId()) 
                        {
							$feedSubmissionId = $feedSubmissionInfo->getFeedSubmissionId();
							
							$param['message'] = 'Order Acknowledged : Feed Submission Id - '.$feedSubmissionId;
							
							$obj = new Pwapresta();
							$obj->generate_log($param);
		
                        }
                    } 
              }
                
     } catch (MarketplaceWebService_Exception $ex) {
		$message  =  'MWS Feed API : Caught Exception : '.$ex->getMessage(). "\n";
		$message .= "Response Status Code: " . $ex->getStatusCode() . "\n";
		$message .= "Error Code: " . $ex->getErrorCode() . "\n";
		$message .= "Error Type: " . $ex->getErrorType() . "\n";

		$param['message'] = $message;
		$obj = new Pwapresta();
		$obj->generate_log($param);
     }
   }
 
}
                                                                
