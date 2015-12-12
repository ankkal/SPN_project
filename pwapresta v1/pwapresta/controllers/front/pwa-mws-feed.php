<?php

/**
 * Pay with Amazon
 *
 * The PWA MWS Report class use MWS Report API to update order details.
 *
 * @class 		PWA_Mws_Feed
 * @version		1.0.0
 * @package		PWA/Mws_feed
 * @category	Class
 * @author 		Amazon
 */
 
 
class PWA_Mws_Feed {
	
	
	
	/**
	 * Constructor for the PWA MWS Feed class.
	 */
	public function __construct() {
		$this->includes();
	}
	
	
	/**
	 * Include required core files and classes.
	 */
	public function includes() {
		require_once('mws_report/SubmitFeed.php');
	}
	
	/*
	 * Feed API to acknowledge an order on seller central
	 */
	public function submit_acknowledge_feed($param) {
		$submit_feed = new Submit_Feed();
		return $submit_feed->acknowledge_feed($param);
	}
	
	/*
	 * Feed API to cancel an order on seller central
	 */
	public function submit_cancel_feed($param) {
		$submit_feed = new Submit_Feed();
		return $submit_feed->cancel_feed($param);
	}
	
	/*
	 * Feed API to ship an order on seller central
	 */
	public function submit_shipment_feed($param) {
		$submit_feed = new Submit_Feed();
		return $submit_feed->shipment_feed($param);
	}
	
	/*
	 * Feed API to refund for an order on seller central
	 */
	public function submit_refund_feed($param) {
		$submit_feed = new Submit_Feed();
		$submit_feed->refund_feed($param);
	}
	
	
	
}
