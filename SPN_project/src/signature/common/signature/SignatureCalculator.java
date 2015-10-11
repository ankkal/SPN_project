package signature.common.signature;
import java.security.SignatureException;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;

import org.apache.commons.codec.binary.Base64;


/**
 * A simple class that demostrates how to generate a signature with the
 * user specified paramters: Merchant ID, Access Key ID and Secret Key ID
 * 
 * @author Joshua Wong
 * 
 * Copyright 2008-2011 Amazon.com, Inc., or its affiliates. All Rights Reserved. 
 * Licensed under the Apache License, Version 2.0 (the “License”).
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
public class SignatureCalculator {

	private static final String HMAC_SHA1_ALGORITHM = "HmacSHA1";

	public SignatureCalculator() {
	}

	/**
	 * Computes RFC 2104-compliant HMAC signature.
	 * 
	 * @param data
	 *            The data to be signed.
	 * @param key
	 *            The signing key, a.k.a. the AWS secret key.
	 * @return The base64-encoded RFC 2104-compliant HMAC signature.
	 * @throws java.security.SignatureException
	 *             when signature generation fails
	 */
	public byte[] calculateRFC2104HMAC(byte[] data, String key)
			throws SignatureException {
		byte[] result = null;

		try {
			// get an hmac_sha1 key from the raw key bytes
			SecretKeySpec signingKey = new SecretKeySpec(key.getBytes(),
					HMAC_SHA1_ALGORITHM);

			// get an hmac_sha1 Mac instance and initialize with the signing key
			Mac mac = Mac.getInstance(HMAC_SHA1_ALGORITHM);
			mac.init(signingKey);

			// compute the hmac on input data bytes
			mac.update(data);
			byte[] rawHmac = mac.doFinal();

			// base64-encode the hmac
			result = Base64.encodeBase64(rawHmac);
		} catch (Exception e) {
			throw new SignatureException("Failed to generate HMAC: ", e);
		}
		
		return result;
	}
}