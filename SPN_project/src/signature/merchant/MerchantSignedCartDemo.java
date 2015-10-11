package signature.merchant;

import java.security.SignatureException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;

import org.apache.commons.httpclient.URIException;

import signature.common.cart.CartFactory;
import signature.common.cart.xml.XMLCartFactory;
import signature.common.signature.SignatureCalculator;
import signature.merchant.cart.html.MerchantHTMLCartFactory;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileWriter;
import java.io.IOException;

/**
 * A simple demo class that demostrates how to generate a signature with the
 * user specified parameters: Merchant ID, Access Key ID and Secret Key ID.
 * 
 * NOTE: You may modify signature.common.cart.xml.XMLCartFactory.getCartXML(...) and
 * signature.merchant.cart.html.MerchantHTMLCartFactory.getCartMap(...)
 * to generate signatures for your own cart.
 * 
 * @author Joshua Wong
 * 
 * Copyright 2008-2011 Amazon.com, Inc., or its affiliates. All Rights Reserved. 
 * Licensed under the Apache License, Version 2.0 (the â€œLicenseâ€�).
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
public class MerchantSignedCartDemo {
	public MerchantSignedCartDemo() {
	}

	/**
	 * Demostrates how to generate a merchant signed cart (both HTML and XML)
	 * 
	 * @param args
	 * @throws SignatureException
	 * @throws URIException
	 */
	public HashMap createUrl(HashMap parameters) throws SignatureException,
			URIException {
		// initialization
		String merchantID = "A2FGKT8VB5FH6B";
		String accessKeyID = "AKIAIAPEKTOI5VMQZJUQ";
		String secretKeyID = "J4BxJI5asf2qyUw9hRnA1/xhtUSAGTo4nsfEC9rt";
		String InvoiceNo=(String) parameters.get("invoice");
		String Amount=(String) parameters.get("amount");

		System.out.println("--------------------- Initialization ------------------------");
		
		if (merchantID == null || merchantID.trim().equals("") ||
			accessKeyID == null || accessKeyID.trim().equals("") ||
			secretKeyID == null || secretKeyID.trim().equals("")) {
			System.out.println("Unable to initialized program with arguments:\n" +
					"\tMerchant ID: " + merchantID + "\n" +
					"\tAccess Key ID: " + accessKeyID + "\n" +
					"\tSecret Key ID: " + secretKeyID + "\n");
			System.exit(-1);
		}

		// Begin demo
		CartFactory factory = new MerchantHTMLCartFactory();
		SignatureCalculator calculator = new SignatureCalculator();

		File file = new File("C:\\Integration_Deck\\SPN_Files\\"+InvoiceNo+".html");
	      
	    HashMap Status=new HashMap();  
		try {
			if (file.createNewFile()){
			    System.out.println("File is created!");
			  }else{
			    System.out.println("File already exists.");
			  }
		
		Status.put("FileName", file.getCanonicalPath());
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		// XML Cart demonstration
		factory = new XMLCartFactory();

		String cart = factory.getSignatureInput(merchantID, accessKeyID,parameters);
		String merchantSignature = new String(calculator.calculateRFC2104HMAC(cart
				.getBytes(), secretKeyID));
		String cartHTML = factory.getCartHTML(merchantID, accessKeyID, merchantSignature,parameters);
		System.out.println("");
		System.out.println("--------------------- XML Cart Example ---------------------");
		System.out.println("2a. Merchant signature input: " + cart);
		System.out.println("2b. Generated signature: "
				+ merchantSignature);
		System.out.println("2c. Generated cart html:\n" + cartHTML);
		BufferedWriter bw;
		try {
			bw = new BufferedWriter(new FileWriter(file));
			bw.write("<html>"+cartHTML+"</html>");
			bw.close();
			Status.put("ButtonCreated", true);
			return Status;
		
		} catch (IOException e) {
			// TODO Auto-generated catch block
		    System.out.println("File not created");

			e.printStackTrace();
			Status.put("ButtonCreated", false);
			return Status;
		}
		
		
	}
}
