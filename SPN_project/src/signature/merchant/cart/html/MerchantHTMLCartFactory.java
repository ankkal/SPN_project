package signature.merchant.cart.html;

import java.util.HashMap;
import java.util.Map;
import java.util.TreeMap;

import org.apache.commons.httpclient.URIException;

import signature.common.cart.html.HTMLCartFactory;

/**
 * Returns a simple static cart to generate a signature from.
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
public class MerchantHTMLCartFactory extends HTMLCartFactory {

	/**
	 * Replace with your own cart here to try out
	 * different promotions, tax, shipping, etc. 
	 * 
	 * @param merchantID
	 * @param accessKeyID
	 * @return
	 */
	protected Map<String, String> getCartMap(String merchantID, String accessKeyID) {
		Map<String, String> parameterMap = new TreeMap<String, String>();

		parameterMap.put("item_merchant_id_1", merchantID);
		parameterMap.put("item_title_1", "Red Fish");
		parameterMap.put("item_sku_1", "RedFish123");
		parameterMap.put("item_description_1",
				"A red fish packed in spring water.");
		parameterMap.put("item_price_1", "19.99");
		parameterMap.put("item_quantity_1", "1");
		parameterMap.put("currency_code", "INR");
		parameterMap.put("item_custom_data", "my custom data");
		parameterMap.put("cart_custom_data", "some cart info");
		parameterMap.put("aws_access_key_id", accessKeyID);

		return parameterMap;
	}
	
	/**
	 * Construct a very basic cart with one item.
	 */
	public String getSignatureInput(String merchantID, String accessKeyID)
			throws URIException {

		return getSignatureInput(getCartMap(merchantID, accessKeyID));
	}

	@Override
	public String getCart(String merchantID, String accessKeyID, HashMap parameters) throws URIException {
		// TODO Auto-generated method stub
		return null;
	}

	@Override
	public String getSignatureInput(String merchantID, String accessKeyID, HashMap parameters) throws URIException {
		// TODO Auto-generated method stub
		return null;
	}

	@Override
	public String getCartHTML(String merchantID, String accessKeyID, String signature, HashMap parameters) {
		// TODO Auto-generated method stub
		return null;
	}
}
