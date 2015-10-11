package signature.common.cart.xml;


import java.util.HashMap;

import org.apache.commons.codec.binary.Base64;

import signature.common.cart.CartFactory;


/**
 * Returns a simple static cart to generate a signature from,
 * and the final complete cart html.
 * 
 * @author Joshua Wong
 * 
 * Copyright 2008-2011 Amazon.com, Inc., or its affiliates. All Rights Reserved. 
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 * 
 * 		http://aws.amazon.com/apache2.0/
 * 
 * or in the â€œlicenseâ€� file accompanying this file.
 * This file is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language
 * governing permissions and limitations under the License.
 */
public class XMLCartFactory extends CartFactory {
	protected static final String CART_FORM_ORDER_INPUT_FIELD = "type:merchant-signed-order/aws-accesskey/1;order:[ORDER];" +
									"signature:[SIGNATURE];aws-access-key-id:[ACCESS_KEY_ID]";
	
	public XMLCartFactory() {
	}

	/**
	 * Gets cart html fragment used to generate entire cart html
	 * Base 64 encode the cart.
	 * 
	 * @see CartFactory
	 */
	public String getCart(String merchantID,
			String accessKeyID,HashMap parameters) {
		
		String cartXML = getCartXML(merchantID, accessKeyID,parameters);
		return new String(Base64.encodeBase64(cartXML.getBytes()));
	}

	
	/**
	 * Returns the concatenated cart used for signature generation.
	 * @see CartFactory
	 */
	public String getSignatureInput(String merchantID,
			String accessKeyID,HashMap parameters) {
		return getCartXML(merchantID, accessKeyID,parameters);
	}

	/**
	 * Returns a finalized full cart html including the base 64 encoded cart,
	 * signature, and buy button image link.
	 */
	public String getCartHTML(String merchantID, String accessKeyID, String signature,HashMap parameters) {
		StringBuffer cartHTML = new StringBuffer();

		cartHTML.append(CART_JAVASCRIPT_START);
		cartHTML.append(CBA_BUTTON_DIV);
		// construct the order-input section
		String encodedCart = getCart(merchantID, accessKeyID,parameters);
		String cartValue =CART_FORM_ORDER_INPUT_FIELD.replaceAll("\\[SIGNATURE\\]", signature).
					replaceAll("\\[ACCESS_KEY_ID\\]", accessKeyID).replaceAll("\\[ORDER\\]", encodedCart);
		String widgetScript = STANDARD_CHECKOUT_WIDGET_SCRIPT.replaceAll("\\[CART_TYPE\\]", "XML");
		widgetScript = widgetScript.replaceAll("\\[CART_VALUE\\]", cartValue).replaceAll("\\[MERCHANT_ID\\]", merchantID);
		cartHTML.append(widgetScript);

		return cartHTML.toString();
	}
	
	/**
	 * Replace with your own cart here to try out
	 * different promotions, tax, shipping, etc. 
	 * 
	 * @param merchantID
	 * @param accessKeyID
	 * @return
	 */
	private String getCartXML(String merchantID,
			String accessKeyID,HashMap parameters) {
		String InvoiceNo=(String) parameters.get("invoice");
		String Amount=(String) parameters.get("amount");
		return
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>" +
		"<Order xmlns=\"http://payments.amazon.com/checkout/2009-05-15/\">" +
		"    <ClientRequestId>123457</ClientRequestId>" +
		"    <Cart>" +
		"    <Items>" +
		"      <Item>" +
		"         <SKU>CALVIN-HOBBES</SKU>" +
		"         <MerchantId>" + merchantID + "</MerchantId>" +
		"         <Title>"+InvoiceNo+"</Title>" +
		"         <Description>By Bill Watterson</Description>" +
		"         <Price>" +
		"            <Amount>"+Amount+"</Amount>" +
		"            <CurrencyCode>INR</CurrencyCode>" +
		"         </Price>" +
		"         <Quantity>1</Quantity>" +
		"         <Weight>" +
		"            <Amount>8.5</Amount>" +
		"            <Unit>kg</Unit>" +
		"         </Weight>" +
		"         <Category>Books</Category>" +
		"         <ItemCustomData><ItemLevelData>item custom data</ItemLevelData></ItemCustomData>" +
		"      </Item>" +
		"    </Items>" +
		"    </Cart>" +
		"</Order>";
	}

}
