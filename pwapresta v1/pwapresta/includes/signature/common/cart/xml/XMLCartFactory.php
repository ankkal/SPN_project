<?php
require_once(PWA_MODULE_DIR."/includes/signature/common/cart/CartFactory.php");
                                                                                                                                                            /**
 * Returns a simple static cart to generate a signature from,
 * and the final complete cart html.
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
  class XMLCartFactory extends CartFactory {
   protected static $CART_ORDER_INPUT_FIELD ="type:merchant-signed-order/aws-accesskey/1;order:[ORDER];signature:[SIGNATURE];aws-access-key-id:[AWS_ACCESS_KEY_ID]";
   
   public function XMLCartFactory() {
   }

   /**
    * Gets cart html fragment used to generate entire cart html
    * Base 64 encode the cart.
    * 
    */
   public function getCart($merchantID, $awsAccessKeyID) {
    $cartXML = $this->getCartXML($merchantID, $awsAccessKeyID);
    return base64_encode($cartXML);
  }
  
   /**
    * Returns the concatenated cart used for signature generation.
    * @see CartFactory
    */
   public function getSignatureInput($merchantID, $awsAccessKeyID) {
    return $this->getCartXML($merchantID, $awsAccessKeyID);
  }

   /**
    * Returns a finalized full cart html including the base 64 encoded cart,
    * signature, and buy button image link.
    */
   public function getCartHTML($merchantID, $awsAccessKeyID, $signature , $type) {
    $css = Configuration::get('PWAPRESTA_PWAPRESTA_CUSTOM_HTML_CODE_DATA');
    $cartHTML = '<style>
	#cbaButton img , #customamazonbutton{
	'.$css.'
	}
	</style>
	';
    if(Configuration::get('PWAPRESTA_PWAPRESTA_ENVIRONMENT') == 'production') 
	  $cartHTML = $cartHTML . CartFactory::$PROD_CART_JAVASCRIPT_START;
	else
	  $cartHTML = $cartHTML . CartFactory::$SAND_CART_JAVASCRIPT_START;
    
    if( Configuration::get('PWAPRESTA_PWAPRESTA_CUSTOM_PWA_IMAGE') == '1' && Configuration::get('PWAPRESTA_PWAPRESTA_CUSTOM_PWA_IMAGE_URL') != '') 
    {
		$site =  _PS_BASE_URL_.__PS_BASE_URI__;
		define('IMAGE_URL',$site.'modules/pwapresta/views/img/');
   		$custom_image = IMAGE_URL.Configuration::get('PWAPRESTA_PWAPRESTA_CUSTOM_PWA_IMAGE_URL');
   			
		if($type == 'cart')
		$cartHTML = $cartHTML . "<div id=\"cbaButton\" style=\"float:right\"><img style=\"cursor: pointer;\" src= \"".$custom_image."\" id = \"customamazonbutton\" onclick = \"clickoriginalamazon()\" /></div>";
		
		if($type == 'checkout')
		$cartHTML = $cartHTML . "<div id=\"cbaButton\"><img style=\"cursor: pointer;\" src= \"".$custom_image."\" id = \"customamazonbutton\" onclick = \"clickoriginalamazon()\" /></div>";
	}
	else
	{
		if($type == 'cart')
		$cartHTML = $cartHTML . CartFactory::$CBA_BUTTON_DIV_CART;      
		
		if($type == 'checkout')
		$cartHTML = $cartHTML . CartFactory::$CBA_BUTTON_DIV_CHECKOUT; 
    }
    
    
    // construct the order-input section
    $encodedCart = $this->getCart($merchantID, $awsAccessKeyID);
    $input = preg_replace("/\\[ORDER\\]/", $encodedCart, XMLCartFactory::$CART_ORDER_INPUT_FIELD);
    $input = preg_replace("/\\[SIGNATURE\\]/", $signature, $input);
    $input = preg_replace("/\\[AWS_ACCESS_KEY_ID\\]/", $awsAccessKeyID, $input);
    
    if( Configuration::get('PWAPRESTA_PWAPRESTA_CUSTOM_PWA_IMAGE') == '1' && Configuration::get('PWAPRESTA_PWAPRESTA_CUSTOM_PWA_IMAGE_URL') != '') 
      $widgetScript = preg_replace("/\\[CART_TYPE\\]/", "XML",CartFactory::$STANDARD_CHECKOUT_WIDGET_SCRIPT_CUSTOM);
    else
      $widgetScript = preg_replace("/\\[CART_TYPE\\]/", "XML",CartFactory::$STANDARD_CHECKOUT_WIDGET_SCRIPT);
       
    $widgetScript = preg_replace("/\\[MERCHANT_ID\\]/", $merchantID,$widgetScript);
    $widgetScript =preg_replace ("/\\[CART_VALUE\\]/",$input ,$widgetScript);
    $widgetScript =preg_replace ("/\\[PWA_BTN_COLOR\\]/",PWA_BTN_COLOR,$widgetScript);
    $widgetScript =preg_replace ("/\\[PWA_BTN_SIZE\\]/",PWA_BTN_SIZE,$widgetScript);

    $cartHTML = $cartHTML . $widgetScript;        
    return $cartHTML;
  }
  
    /**
     * Replace with your own cart here to try out
     * different promotions, tax, shipping, etc. 
     * 
     * @param merchantID
     * @param awsAccessKeyID
     */
    private function getCartXML($merchantID, $awsAccessKeyID) 
    {
      $context =Context::getContext();
      if( $context->customer->isLogged() )
      {
        $cart_id    =  (int)$context->cart->id;
        $client_id  =  $context->customer->id.'/'.$cart_id;
      }
      else
      {
        $cart_id    =  (int)$context->cart->id;
        $client_id  =  '0/'.$cart_id;
      }
      
      $site =  _PS_BASE_URL_.__PS_BASE_URI__;
      $ReturnUrl = $site.'module/pwapresta/pwaorder?action=pwa_order&amp;CartId='.$cart_id;
     
     $doc = new DOMDocument('1.0');
     $doc->formatOutput= true;
     $root = $doc->createElement('Order');
     $attribute = $doc->createAttribute('xmlns');
     $attribute->value = 'http://payments.amazon.com/checkout/2009-05-15/';
     $root_attr = $root->appendChild($attribute);
     $root = $doc->appendChild($root);

     $ClientRequestId = $doc->createElement('ClientRequestId', $cart_id);
     $ClientRequestId = $root->appendChild($ClientRequestId);

     $Cart = $doc->createElement('Cart');
     $Cart = $root->appendChild($Cart);

     $Items = $doc->createElement('Items');
     $Items = $Cart->appendChild($Items);

     $CartPromotionId = $doc->createElement('CartPromotionId','Total_Discount');
     $CartPromotionId = $Cart->appendChild($CartPromotionId);

     $Promotions = $doc->createElement('Promotions');
     $Promotions = $root->appendChild($Promotions);
     
     $cart_rules = $context->cart->getCartRules();
    
     $total_product_with_tax = $context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
     $total_discount_at_amazon_with_tax = $context->cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
     $total_wrapping_with_tax = $context->cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
     
     global $currency;
     $currency_iso_code = $currency->iso_code;

     if($total_discount_at_amazon_with_tax)
     {
        $Promotion = $doc->createElement('Promotion');
        $Promotion = $Promotions->appendChild($Promotion);

        $Promotion_pro_id = $doc->createElement('PromotionId','Total_Discount');
        $Promotion_pro_id = $Promotion->appendChild($Promotion_pro_id);

        $Promotion_pro_desc = $doc->createElement('Description','Total discount at cart level');
        $Promotion_pro_desc = $Promotion->appendChild($Promotion_pro_desc);

        $Promotion_pro_benf = $doc->createElement('Benefit');
        $Promotion_pro_benf = $Promotion->appendChild($Promotion_pro_benf);

        $Promotion_pro_benf_fad = $doc->createElement('FixedAmountDiscount');
        $Promotion_pro_benf_fad = $Promotion_pro_benf->appendChild($Promotion_pro_benf_fad);

        $Promotion_pro_benf_fad_amount = $doc->createElement('Amount',$total_discount_at_amazon_with_tax);
        $Promotion_pro_benf_fad_amount = $Promotion_pro_benf_fad->appendChild($Promotion_pro_benf_fad_amount);

        $Promotion_pro_benf_fad_currency = $doc->createElement('CurrencyCode',$currency_iso_code);
        $Promotion_pro_benf_fad_currency = $Promotion_pro_benf_fad->appendChild($Promotion_pro_benf_fad_currency);
     }
     
     $products= $context->cart->getProducts();
     foreach ($products as $key => $product)
     {
      
      $product_id = $product['id_product'];
      
      $sku = $product['reference'];
      $sku = substr($sku,0,40);
      $sku = $this->replace_char($sku);
      $sku = htmlentities($sku,ENT_QUOTES,'UTF-8');

      $title = $product['name'];
      if(!$title)
                $title = 'Title';

      $title = substr($title,0,80);
      $title =  $this->replace_char($title);
      $title = htmlentities($title,ENT_QUOTES,'UTF-8');

      $description = $product['description_short'];
      $description = substr($description,0,1900);
      $description =  $this->replace_char($description);
      $description = htmlentities($description,ENT_QUOTES,'UTF-8');

      $quantity = $product['quantity'];
      $weight = $product['weight'];
      $weight_unit = Configuration::get('PS_WEIGHT_UNIT');
      if($weight_unit != 'kg' && $weight > 0)
      {
            $weight = $weight/1000 ;
            $weight_unit = 'kg';
      }

      if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice')  
        $address_id = (int)$this->id_address_invoice;    
      else    
        $address_id = (int)$product['id_address_delivery']; // Get delivery address of the product from the cart    
     
      if (!Address::addressExists($address_id))    
        $address_id = null;

      $virtual_context = Context::getContext()->cloneContext();
      $virtual_context->cart = $context->cart;

      $product_price = Product::getPriceStatic(  
            (int)$product['id_product'],   
            true,   
            (int)$product['id_product_attribute'],    
            2,
            null,  
            false, 
            true,  
            $product['cart_quantity'],    
            false,    
            ((int)$context->cart->id_customer ? (int)$context->cart->id_customer : null),    
            (int)$context->cart->id,    
            ((int)$address_id ? (int)$address_id : null),    
            $null,    
            true,    
            true,    
            $virtual_context    
          );

      $product_price_exl_tax = Product::getPriceStatic(  
            (int)$product['id_product'],   
            false,   
            (int)$product['id_product_attribute'],    
            2,
            null,  
            false, 
            true,  
            $product['cart_quantity'],    
            false,    
            ((int)$context->cart->id_customer ? (int)$context->cart->id_customer : null),    
            (int)$context->cart->id,    
            ((int)$address_id ? (int)$address_id : null),    
            $null,    
            true,    
            true,    
            $virtual_context    
          );    
      
      $Item = $doc->createElement('Item');
      $Item = $Items->appendChild($Item);
      
      $SKU = $doc->createElement('SKU',$sku);
      $SKU = $Item->appendChild($SKU);

      $MerchantId = $doc->createElement('MerchantId',$merchantID);
      $MerchantId = $Item->appendChild($MerchantId);

      $Title = $doc->createElement('Title',$title);
      $Title = $Item->appendChild($Title);

      $Description = $doc->createElement('Description',$description);
      $Description = $Item->appendChild($Description);

      $Price = $doc->createElement('Price');
      $Price = $Item->appendChild($Price);

      $Amount = $doc->createElement('Amount',$product_price);
      $Amount = $Price->appendChild($Amount);

      $CurrencyCode = $doc->createElement('CurrencyCode',$currency_iso_code);
      $CurrencyCode = $Price->appendChild($CurrencyCode);

      $Quantity = $doc->createElement('Quantity',$quantity);
      $Quantity = $Item->appendChild($Quantity);
      
      if($weight)
      {
        $Weight = $doc->createElement('Weight');
        $Weight = $Item->appendChild($Weight);

        $Amount_wt = $doc->createElement('Amount',$weight);
        $Amount_wt = $Weight->appendChild($Amount_wt);

        $Wt_unit = $doc->createElement('Unit',$weight_unit);
        $Wt_unit = $Weight->appendChild($Wt_unit);
      }
      /*
      if($total_discount_at_amazon_with_tax)
      {  
        $discount_code = "";
        $product_discount_with_tax = 0;
        foreach ($cart_rules as $key => $cart_rule) {
          if($cart_rule['reduction_product'] == $product_id)
          {
            $discount_code .= $cart_rule['code'].',';
            $product_discount_with_tax += $cart_rule['value_real'];
          }
          else if($cart_rule['reduction_product'] == 0){
            $discount_code .= $cart_rule['code'].',';
            $product_discount_with_tax += (($product_price * $quantity) / $total_product_with_tax) * $cart_rule['value_real'];
          }
        }
        $product_discount_with_tax = round($product_discount_with_tax,2);
        $this->create_promotion_xml_part($product_id,$discount_code,$product_discount_with_tax,$doc,$Item,$root,$Promotions,$currency_iso_code);
      }*/
      $variant_array = isset($product['attributes']) ? explode(',', $product['attributes']) : array();
      $ItemCustomData = $doc->createElement('ItemCustomData');
      $ItemCustomData = $Item->appendChild($ItemCustomData);
      
      $Item_product_id = $doc->createElement('Item_product_id',$product_id);
      $Item_product_id = $ItemCustomData->appendChild($Item_product_id);

      $Item_attr_product_id = $doc->createElement('Item_attr_product_id',$product['id_product_attribute']);
      $Item_attr_product_id = $ItemCustomData->appendChild($Item_attr_product_id);

      $Item_price_excl_tax = $doc->createElement('Item_price_excl_tax',$product_price_exl_tax);
      $Item_price_excl_tax = $ItemCustomData->appendChild($Item_price_excl_tax);

      foreach($variant_array as $key=> $value)
      {
        $value = explode(':', $value);
        
        $Item_attribute = $doc->createElement('Item_attribute');
        $Item_attribute = $ItemCustomData->appendChild($Item_attribute);
        
        $attr_name = substr($value[0],0,40);
        $attr_name = $this->replace_char($attr_name);
        $attr_name = htmlentities($attr_name,ENT_QUOTES,'UTF-8');

        $Attribute_name = $doc->createElement('Attribute_name',$attr_name);
        $Attribute_name = $Item_attribute->appendChild($Attribute_name);

        $attr_val = substr($value[1],0,40);
        $attr_val = $this->replace_char($attr_val);
        $attr_val = htmlentities($attr_val,ENT_QUOTES,'UTF-8');

        $Attribute_val = $doc->createElement('Attribute_val',$attr_val);
        $Attribute_val = $Item_attribute->appendChild($Attribute_val);  
      }
    }
    if($context->cart->gift)
    {
        $Item = $doc->createElement('Item');
        $Item = $Items->appendChild($Item);
        
        $SKU = $doc->createElement('SKU','wrapping_fee');
        $SKU = $Item->appendChild($SKU);

        $MerchantId = $doc->createElement('MerchantId',$merchantID);
        $MerchantId = $Item->appendChild($MerchantId);

        $Title = $doc->createElement('Title','Wrapping-fee');
        $Title = $Item->appendChild($Title);

        $Description = $doc->createElement('Description',$context->cart->gift_message);
        $Description = $Item->appendChild($Description);

        $Price = $doc->createElement('Price');
        $Price = $Item->appendChild($Price);

        $Amount = $doc->createElement('Amount',$total_wrapping_with_tax);
        $Amount = $Price->appendChild($Amount);

        $CurrencyCode = $doc->createElement('CurrencyCode',$currency_iso_code);
        $CurrencyCode = $Price->appendChild($CurrencyCode);

        $Quantity = $doc->createElement('Quantity',1);
        $Quantity = $Item->appendChild($Quantity);
    }
    
    $ReturnUrl = $doc->createElement('ReturnUrl',$ReturnUrl);
    $ReturnUrl = $root->appendChild($ReturnUrl);
 
    return $doc->saveXML();
    
  }
  public function create_promotion_xml_part($product_id, $discount_code, $total_discount_at_amazon_with_tax,$doc,$Item,$root,$Promotions,$currency)
  {  
    $promo_id = $product_id.','.$discount_code;

    $PromotionIds = $doc->createElement('PromotionIds');
    $PromotionIds = $Item->appendChild($PromotionIds);

    $promo_id = substr($promo_id,0,255);
    $promo_id = $this->replace_char($promo_id);
    $promo_id = htmlentities($promo_id,ENT_QUOTES,'UTF-8');

    $PromotionId = $doc->createElement('PromotionId',$promo_id);
    $PromotionId = $PromotionIds->appendChild($PromotionId);

    $Promotion = $doc->createElement('Promotion');
    $Promotion = $Promotions->appendChild($Promotion);

    $Promotion_pro_id = $doc->createElement('PromotionId',$promo_id);
    $Promotion_pro_id = $Promotion->appendChild($Promotion_pro_id);

    $Promotion_pro_desc = $doc->createElement('Description','Custom discount');
    $Promotion_pro_desc = $Promotion->appendChild($Promotion_pro_desc);

    $Promotion_pro_benf = $doc->createElement('Benefit');
    $Promotion_pro_benf = $Promotion->appendChild($Promotion_pro_benf);

    $Promotion_pro_benf_fad = $doc->createElement('FixedAmountDiscount');
    $Promotion_pro_benf_fad = $Promotion_pro_benf->appendChild($Promotion_pro_benf_fad);

    $Promotion_pro_benf_fad_amount = $doc->createElement('Amount',$total_discount_at_amazon_with_tax);
    $Promotion_pro_benf_fad_amount = $Promotion_pro_benf_fad->appendChild($Promotion_pro_benf_fad_amount);

    $Promotion_pro_benf_fad_currency = $doc->createElement('CurrencyCode',$currency);
    $Promotion_pro_benf_fad_currency = $Promotion_pro_benf_fad->appendChild($Promotion_pro_benf_fad_currency);
  }
  
   public function replace_char($string)
   {
		$string = str_replace('&','',$string);
		$string = str_replace('<','',$string);
		$string = str_replace('>','',$string);
		$string = str_replace('"','',$string);
		$string = str_replace("'","",$string);
		return $string;
   }
}
