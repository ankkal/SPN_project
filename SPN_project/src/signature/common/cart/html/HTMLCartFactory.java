package signature.common.cart.html;

import java.util.Map;
import java.util.TreeMap;

import org.apache.commons.httpclient.URIException;
import org.apache.commons.httpclient.util.URIUtil;
import org.apache.commons.lang.StringEscapeUtils;

import signature.common.cart.CartFactory;

/**
 * Abstract class that contains utility methods for converting a map of url
 * parameters to its string representation for use with signature generation.
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
 * or in the “license” file accompanying this file.
 * This file is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language
 * governing permissions and limitations under the License.
 */
public abstract class HTMLCartFactory extends CartFactory {
	/**
	 * Returns html fragment used to generate complete html cart.
	 * 
	 * @see CartFactory
	 */
	public String getCart(String merchantID, String accessKeyID) {
		Map<String, String> parameterMap = getCartMap(merchantID, accessKeyID);
		
		StringBuffer cart = new StringBuffer();
		
		// add lines to the cart html fragment like:
		// <input name="item_title_1" value="Red Fish" type="hidden" />
		for (Map.Entry<String, String> entry : parameterMap.entrySet()) {
			cart.append(CART_FORM_INPUT_FIELD.replace("[KEY]", entry.getKey()).
					replace("[VALUE]", StringEscapeUtils.escapeHtml(entry.getValue())));
		}
		
		return cart.toString();
	}

	/**
	 * Generates the finalized cart html, including javascript headers, cart contents,
	 * signature and button.
	 * 
	 */
	public String getCartHTML(String merchantID, String accessKeyID, String signature) {
		StringBuffer cartHTML = new StringBuffer();
		cartHTML.append(CART_JAVASCRIPT_START);
		cartHTML.append(CBA_BUTTON_DIV);
		cartHTML.append(CART_FORM_START.replaceAll("\\[MERCHANT_ID\\]", merchantID));
		cartHTML.append(getCart(merchantID, accessKeyID));
		cartHTML.append(CART_FORM_SIGNATURE_INPUT_FIELD.replaceAll("\\[SIGNATURE\\]", signature));
		cartHTML.append(CART_FORM_END);
		String widgetScript = STANDARD_CHECKOUT_WIDGET_SCRIPT.replaceAll("\\[CART_VALUE\\]", "CBACartForm");
		widgetScript = widgetScript.replaceAll("\\[CART_TYPE\\]", "HTML").replaceAll("\\[MERCHANT_ID\\]", merchantID);
		cartHTML.append(widgetScript);
		return cartHTML.toString();
	}

	/**
	 * Generates the signature input - basically a contenation of all url parameters.
	 * Doesn't handle full url specification, since it
	 * doesn't handle parameter value of arrays - just assumes each parameter
	 * value is a basic string.
	 * 
	 * Checkout by Amazon only supports this format as well, so that is
	 * perfectly fine.
	 * 
	 * @see CartFactory
	 */
	protected String getSignatureInput(Map<String, String> parameterMap)
			throws URIException {
		StringBuilder stringBuilder = new StringBuilder();
		Map<String, String> sortedParameterMap = new TreeMap<String, String>(
				parameterMap);

		/*
		 * Assumes url parameters are in a Map named parameterMap where the key
		 * is the parameter name.
		 */
		for (Map.Entry<String, String> entry : sortedParameterMap.entrySet()) {
			stringBuilder.append(entry.getKey());
			stringBuilder.append("=");
			stringBuilder.append(URIUtil.encodeWithinQuery(entry.getValue()));
			stringBuilder.append("&");
		}

		return stringBuilder.toString();
	}

	protected abstract Map<String, String> getCartMap(String merchantID,
			String accessKeyID);
}
