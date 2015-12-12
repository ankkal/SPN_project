# Pay with Amazon

Author : Amazon
Tags : Amazon, Seller central, Pay with Amazon, Checkout by Amazon, Payment, Orders, IOPN, MWS API
Prestashop Version : Requires at least 1.6x
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

## About
Pay with Amazon is a module to Checkout by Amazon and also manage orders.


## Description
By using PWA Module, Seller can provide an option to his customers to make payment using Checkout by Amazon. Where Seller do not need to worry about Checkout and Payments. 
Amazon will take care of everything.


## Manual installation 
1) Download the pwapresta.zip file.
2) Go to Module >> Module in Prestashop Admin Panel
3) Click on "Add a new module"
4) Select downloaded zip file and click on  "Upload this module"
5) Now go to uploaded module named with "Pay with Amazon". (You can find this module under "Payments and Gateways" Tab.)
6) Click on install button to install module.


## Enable Module & Module Configuration
After successful installation you will be redirected to configuration page or you can follow below steps. Here you need to enable module and need to do some settings.
1) Go to Module -> module -> payment Gateway Tab -> Pay with Amazon
2) Enable Pay with Amazon
3) Insert correct Merchant ID , Access Key , Secret Key which you get from Amazon Seller Central. (Please mind that these keys should be correct or module will fail to function properly)
4) Do some other settings as per your requirement (Information on other points are also listed in this file)


### Setup IOPNs Properly ===
To make IOPN works properly you need to follow these steps.
1) Go to Seller Central https://sellercentral.amazon.in
2) Login With your credentials
3) Go to Settings -> Integration Settings -> Edit
4) Set "Merchant URL" or "Integrator URL" under Instant Order Processing Notification Settings.


Note : - 
1) You need to provide a valid SSL URL, Because IOPN will work only with Secure URLs. You can find this URL on PWA Module configuration page named 
as "IOPN Merchant Url". 
2) Module will only accept signed carts IOPN.
3) If you are setting both "Merchant URL" and "Integrator URL", make sure both URL should be different, otherwise duplicate orders may be generate.

Check is IOPN URL working properly? : -
1) Please hit your "IOPN Merchant Url" in browser and if browser didn't give you any 404 error and show blank page it means it's working.
2) If browser gives you 404 error then please check your Friendly URLs . (You can check this setting under Preferences >> SEO and URLs in prestashop admin panel.)


### Setup MWS Properly
1) Schedule MWS Report by hitting the "MWS Schedule Report API Url" manually (Only hit once). You can find this URL on module configuration page. 
2) Setup cron jobs to fetch generated report automatically and reflect orders in prestashop admin panel. You need to setup cron on "MWS Report API Url" and "MWS Order API Url".
You can find respective cron URLs on module's configuration page.
Note: - To setup cron please concern with your developer or your host provider.

Check is MWS URL working properly : -
1) Please hit your "MWS Report API Url" and "MWS Order API Url" in browser and if browser didn't give you any 404 error and show blank page it means it's working.
2) If browser gives you 404 error then please check your Friendly URLs. (You can check this setting under Preferences >> SEO and URLs in prestashop admin panel.)

### Things to Note 
1) It is highly recommended that please request to Amazon to generate Report for your orders whether you are going to use MWS or not.
   You can request this by manually hitting an URL. You can find this URL on module configuration page named as "MWS Schedule Report API Url:"
   Note : Please only hit once, no need to hit again and again to generate reports.
2) Module will accept both Signed and Unsigned Carts IOPN.
3) Unsigned Carts is only for Junglee Orders.
4) About MWS : Few things you need to know if you want to use MWS now or in future.
	4.1) When you hit "MWS Schedule Report API Url" it will start generating report from the current timestamp and will generate after every 15 Minutes. So if an order got into Unshipped state on Seller Central
		 it will take max 15 Minute to reflect in next report.
	4.2) When you hit "MWS Schedule Report API Url" it will start generating report from the current timestamp. Means orders before this timestamp will not reflect in Reports.
		 So if your cron will run it will not reflect the orders that are not in reports.
	4.3) If there is more than 3 days difference in between your report generation time and first cron run time then cron will fetch only last three days reports and will reflect the orders
		 that come only under these reports.
		 You can read more about MWS here : http://docs.developer.amazonservices.com/en_IN/dev_guide/index.html


### PWA  Mails
1) IOPN 
1.a) Customer will get Payment Accepted Email when Order Ready to Ship Notification received.
1.d) Customer will be notified if order is cancelled/Shipped.


2) MWS 
2.b) Customer will get Payment Accepted Email when order details will get reflected.
2.c) Customer will be notified if order is cancelled/shipped.

		 
### Common Issues/ FAQ
Que 1) Can i enable/disable Pay with Amazon button on Cart page?
Ans 1) Yes you can enable/disable Pay with Amazon button on cart page from module configuration page. 

Que 2) Why default Pay with Amazon image not displaying more than one time on a page?
Ans 2) Yes, default Pay with Amazon image will not work more than one time on a page like if you use One page Checkout for Order Process type.

Que 3) May i change default Pay with amazon button image?
Ans 3) Yes you can set your own pay with amazon image. To do so browse an image for "Choose custom PWA button image" option and save.
Note : You can add your own CSS attributes and can change style for your custom button.

Que 4) Will Custom Pay with Amazon button display more than one times on a page?
Ans 4) Yes If you add your own Pay with Amazon button image it will display more than one time on a single page.

Que 5) Why my Prestashop cart total amount and total amount on Amazon is different?
Ans 5) Actually module will add tax and promotions on items and them send to amazon. So sometimes if quantity is more than one then due to rounding issue there may be 1 paisa difference.

Que 6) Why my Prestashop shipping price are not applying/displaying on Amazon Checkout page?
Ans 6) Actually Amazon does not support shipping prices so to apply shipping charges you need to set these on seller central.

Que 7) When my inventory will be reduced if i am using IOPN?
Ans 7) When you will get an New Order Notification it will update order details and will reduce your inventory.

Que 8) When my inventory will be reduced if i am using MWS.
Ans 8) As in MWS, order details will not be reflected before order goes into Unshipped condition so at the order details updation time inventory will reduce and order status will also change 
       to "Payment Accepted" from "Awaiting PWA Payment".

Que 10) Is module generating exception error logs in any file?
Ans 10) Yes module will generate and maintain all your exception logs in error log file. 
	   You can find error log file in your module folder.
	   File Name : pwa_error.log

Que 11) Can i check or dump the IOPN XML Notification Data?
Ans 11) Yes you can dump the IOPN XML Notification Data, To do the same you need to enable it on module configuration page.
		Check "Enable IOPN for debugging purpose"  and set path under  "Set Path for IOPNs dump file" option.
		Default is : modules/pwapresta/iopn_dump/ but you can change it. But if you change the destination make sure you have given 777 permission to that folder.

Que 12) Can i check or dump the MWS XML Response?
Ans 12) Yes you can dump the MWS XML Response, To do the same you need to enable it on module configuration page.
		Check "Generate MWS Report Dump file"  and set path under "Set Path for MWS report dump file" option.
		Default is : modules/pwapresta/mws_report_dump/ but you can change it. But if you change the destination make sure you have given 777 permission to that folder.

		Note :- Do the same for MWS Order API.

Que 13) Does in "Awaiting PWA Payment" status, invoice and delivery slip are generated?
Ans 13) No, invoice and elivery slip will not be generated for "Awaiting PWA Payment" status.

Que 14) Does Prestashop Order Id and other order details like Shipping/Refund will be reflected on seller central.
Ans 14) Yes Prestashop order Id and other order details like Shipping/Refund will relect on seller central automatically. 
Note : Any Order which will contain a product that don't have any reference, will not reflect on seller central in MWS.

Que 15) I am using MWS to update order details. After making successful payment by customer, his/her order didn't show in orders list in prestashop admin panel.
Ans 15) Yes if you are using MWS, customer order will not reflected untill MWS cron don't run.

Que 16) Will my junglee orders get reflected in prestashop admin panel?
Ans 16) Yes your all junglee orders will also get reflected in prestashop admin panel.

