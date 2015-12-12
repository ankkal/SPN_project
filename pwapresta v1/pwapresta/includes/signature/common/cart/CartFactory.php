<?php

/**
 * Factory which generates signature input (to pass to SignatureCalculator) and
 * the final cart HTML.
 *
 * Copyright 2008-2011 Amazon.com, Inc., or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *    http://aws.amazon.com/apache2.0/
 *
 * or in the "license" file accompanying this file.
 * This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */
abstract class CartFactory {
	// Strings to construct the final cart form, with regular expression replacements
	// indicated via [YOUR REPLACEMENT VALUE]
	protected static $PROD_CART_JAVASCRIPT_START = "<script data-cfasync='false' type=\"text/javascript\">if(!CBA||typeof (CBA)==\"undefined\"){var CBA={}}CBA.ZERO_TIME=(new Date()).getTime();var __cba__buttonversion=0;if(CBA.jQuery==undefined){document.write(\"<script type='text/javascript' src='https://images-na.ssl-images-amazon.com/images/G/01/cba/js/jquery-1.4.2.min.js'><\/script>\");if(typeof (JSON)==\"undefined\"){document.write(\"<script type='text/javascript' src='https://images-na.ssl-images-amazon.com/images/G/01/cba/js/widget/json2.js'><\/script>\")}document.write(\"<script type='text/javascript' src='https://images-na.ssl-images-amazon.com/images/G/31/cba/india/js/widget/PaymentWidgets_core._V1430370177_.js'><\/script>\")}</script>\n";
	
	protected static $SAND_CART_JAVASCRIPT_START = "
	<script data-cfasync='false' type=\"text/javascript\">
	if (!CBA || typeof(CBA) == \"undefined\") {
    	var CBA = {}; // Make sure the base namespace exists
	}
	// store time when we start processing our first JS
	CBA.ZERO_TIME  = (new Date()).getTime();
	var __cba__buttonversion = 0;
	if(CBA.jQuery==undefined){
		document.write(\"<script type='text/javascript' src='https://images-na.ssl-images-amazon.com/images/G/01/cba/js/jquery-1.4.2.min.js'><\/script>\");
	if (typeof(JSON) == \"undefined\") {
    	document.write(\"<script type='text/javascript' src='https://images-na.ssl-images-amazon.com/images/G/01/cba/js/widget/json2.js'><\/script>\");
	}
	document.write(\"<script type='text/javascript' src='https://images-na.ssl-images-amazon.com/images/G/31/cba/india/js/widget/sandbox/PaymentWidgets_core.js'><\/script>\");}</script>\n";

	protected static $CART_FORM_START = "<form method=\"POST\" action=\"\" id=\"CBACartForm\">\n";

	protected static $CART_FORM_SIGNATURE_INPUT_FIELD = "<input type=\"hidden\" name=\"merchant_signature\" value=\"[SIGNATURE]\" />\n";

	protected static $CBA_BUTTON_DIV_CART = "<div id=\"cbaButton\" style=\"float:right\"></div>\n";
	
	protected static $CBA_BUTTON_DIV_CHECKOUT = "<div id=\"cbaButton\"></div>\n";

	protected static $CART_FORM_END = "</form>\n";



	protected static $STANDARD_CHECKOUT_WIDGET_SCRIPT = "
	<script data-cfasync='false' type=\"text/javascript\">
	function callme()
	{
		if(CBA.jQuery) {
			CBA.jQuery(document).ready(function () {
			new CBA.Widgets.StandardCheckoutWidget({ 
				merchantId: '[MERCHANT_ID]', 
				buttonSettings: { size: '[PWA_BTN_SIZE]', 
						  color: '[PWA_BTN_COLOR]', 
						  background: 'light' 
						}
				, orderInput: { format: '[CART_TYPE]', value: '[CART_VALUE]' }
			}).render('cbaButton'); });
		}
	}
	
	function call_again()
	{
		if(CBA.jQuery('#cbaButton').html() == '')
		{
			callme();
		}
	}
	
	callme();
	
	setTimeout(function(){ call_again(); }, 1000);
	</script>";

	protected static $STANDARD_CHECKOUT_WIDGET_SCRIPT_CUSTOM = "
	<script data-cfasync='false' type=\"text/javascript\">
	function callme()
	{
		if(CBA.jQuery) {
			
			CBA.jQuery(document).ready(function () {
			new CBA.Widgets.StandardCheckoutWidget({ 
				merchantId: '[MERCHANT_ID]', 
				buttonSettings: { size: '[PWA_BTN_SIZE]', 
						  color: '[PWA_BTN_COLOR]', 
						  background: 'light' 
						}
				, orderInput: { format: '[CART_TYPE]', value: '[CART_VALUE]' }
			}).render('cbaButton'); 
			CBA.jQuery(\"#cbaButton img\").not(\"#customamazonbutton\").hide();
			}); 
		}
	}
	
	function clickoriginalamazon()
	{
		CBA.jQuery(\"#cbaButton img\").not(\"#customamazonbutton\").click();
	}
	
	function call_again()
	{
		if(CBA.jQuery('#cbaButton').html() == '')
		{
			callme();
		}
	}
	
	callme();
	
	setTimeout(function(){ callme(); }, 1000);
	</script>";
	/**
	 * Gets cart HTML fragment used to generate entire cart HTML
	 *
	 * @param merchantID
	 * @param awsAccessKeyID
	 */
	public abstract function getCart($merchantID, $awsAccessKeyID);


	/**
	 * Returns the concatenated cart used for signature generation.
	 *
	 * @param merchantID
	 * @param awsAccessKeyID
	 */
	public abstract function getSignatureInput($merchantID, $awsAccessKeyID);

	/**
	 * Returns a finalized full cart HTML including the base 64 encoded cart,
	 * signature, and buy button image link.
	 *
	 * @param merchantID
	 * @param awsAccessKeyID
	 * @param signature
	 */
	public abstract function getCartHTML($merchantID, $awsAccessKeyID, $signature , $type);
}
?>
