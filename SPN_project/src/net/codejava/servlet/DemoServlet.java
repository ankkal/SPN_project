package net.codejava.servlet;

import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.PrintWriter;
import java.security.SignatureException;
import java.util.Date;
import java.util.HashMap;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.apache.commons.net.ftp.FTP;
import org.apache.commons.net.ftp.FTPClient;

import signature.merchant.*;
@WebServlet("/demoServlet")
public class DemoServlet extends HttpServlet {

	protected void doPost(HttpServletRequest request,
			HttpServletResponse response) throws ServletException, IOException {
		String username = request.getParameter("username");
		/*String password = request.getParameter("password");
		*/
		System.out.println("username is: " + username);
/*		System.out.println("password is: " + password);

		String languages[] = request.getParameterValues("language");
		String langHtml = "";
		
		if (languages != null) {
			System.out.println("Languages are: ");
			for (String lang : languages) {
				langHtml += lang + ",";
				System.out.println("\t" + lang);
			}
		}
		
		String gender = request.getParameter("gender");
		System.out.println("Gender is: " + gender);*/

		
		String feedback = request.getParameter("feedback");
		System.out.println("Feed back is: " + feedback);

		/*String jobCategory = request.getParameter("jobCat");
		System.out.println("Job category is: " + jobCategory);*/
		HashMap isUrlcreated=new HashMap();
		HashMap parameters=new HashMap();
		parameters.put("invoice", username);
		parameters.put("amount", feedback);
		String PayUrl="";
		MerchantSignedCartDemo test=new MerchantSignedCartDemo();
		try {
			 isUrlcreated=test.createUrl(parameters);
		} catch (SignatureException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		String filecreated=(String)isUrlcreated.get("FileName");
		HashMap isUpload=FTPUpload(filecreated);
		if((boolean) isUpload.get("isUploaded")){
			PayUrl=(String) isUpload.get("PayUrl");
		}
		PrintWriter writer = response.getWriter();
		
		String htmlRespone = "<html><h3>";
		htmlRespone += "Amount is: " + feedback + "<br/>";		
		htmlRespone += " " +  "<a href='"+PayUrl+"'>"+"Pay here</a> <br/> or copy the URL  "+ PayUrl+"<br/>";		
		/*htmlRespone += "password is: " + password + "<br/>";		
		htmlRespone += "language is: " + langHtml + "<br/>";		
		htmlRespone += "gender is: " + gender + "<br/>";		*/
		
		/*htmlRespone += "job category is: " + jobCategory + "<br/>";	*/	
		htmlRespone += "</h3></html>";
		
		// return response
		writer.println(htmlRespone);		
	}
	
	public HashMap FTPUpload(String filename) {
		String server = "cbapwatest.com";
		int port = 21;
		String user = "cbapwatest@godaddy.purplesms.in";
		String pass = ").V_Konv(z&PQ";
		HashMap Status=new HashMap(); 
		FTPClient ftpClient = new FTPClient();
		try {

			ftpClient.connect(server, port);
			ftpClient.login(user, pass);
			ftpClient.enterLocalPassiveMode();

			ftpClient.setFileType(FTP.BINARY_FILE_TYPE);

			// APPROACH #1: uploads first file using an InputStream
			File firstLocalFile = new File(filename);
			Date date = new Date(System.currentTimeMillis());
	         String dateString = date.toString();

			String firstRemoteFile = "testspn"+dateString+".html";
			Status.put("PayUrl", "http://"+server+"/"+firstRemoteFile);
			InputStream inputStream = new FileInputStream(firstLocalFile);

			System.out.println("Start uploading first file");
			boolean done = ftpClient.storeFile(firstRemoteFile, inputStream);
			inputStream.close();
			if (done) {
				Status.put("isUploaded", true);
				System.out.println("The first file is uploaded successfully.");
				return Status;
				
			}

			// APPROACH #2: uploads second file using an OutputStream
			/*File secondLocalFile = new File("E:/Test/Report.doc");
			String secondRemoteFile = "test/Report.doc";
			inputStream = new FileInputStream(secondLocalFile);

			System.out.println("Start uploading second file");
			OutputStream outputStream = ftpClient.storeFileStream(secondRemoteFile);
	        byte[] bytesIn = new byte[4096];
	        int read = 0;

	        while ((read = inputStream.read(bytesIn)) != -1) {
	        	outputStream.write(bytesIn, 0, read);
	        }
	        inputStream.close();
	        outputStream.close();

	        boolean completed = ftpClient.completePendingCommand();
			if (completed) {
				System.out.println("The second file is uploaded successfully.");
			}
*/
			Status.put("isUploaded", false);
			return Status;
		} catch (IOException ex) {
			System.out.println("Error: " + ex.getMessage());
			ex.printStackTrace();
			Status.put("isUploaded", false);
			return Status;
		} finally {
			try {
				if (ftpClient.isConnected()) {
					ftpClient.logout();
					ftpClient.disconnect();
				}
				return Status;
			} catch (IOException ex) {
				ex.printStackTrace();
			}
		}
	}


}
