// Jquery with no conflict
(function($){
	
	$(document).ready(function($) {
		// show hide iopn dump file path 
    $( "input:radio[name=pwapresta_iopn_dump]" ).on("click",function() {          
         showHideIOPNDumpFilePath($('input:radio[name=pwapresta_iopn_dump]:checked').val());
    });
    showHideIOPNDumpFilePath($('input:radio[name=pwapresta_iopn_dump]:checked').val());
   
    // show hide mws order dump file path 
    $( "input:radio[name=pwapresta_mws_order_dump]" ).on("click",function() {          
         showHideMWSOrderDumpFilePath($('input:radio[name=pwapresta_mws_order_dump]:checked').val());
    });
    showHideMWSOrderDumpFilePath($('input:radio[name=pwapresta_mws_order_dump]:checked').val());

    // show hide mws report dump file path 
    $( "input:radio[name=pwapresta_mws_report_dump]" ).on("click",function() {          
         showHideMWSReportDumpFilePath($('input:radio[name=pwapresta_mws_report_dump]:checked').val());
    });
    showHideMWSReportDumpFilePath($('input:radio[name=pwapresta_mws_report_dump]:checked').val());



    /*
    ** function checkStringEndEithSlash() is used to check that the file end with slash(/).
    ** If path not end with slash then it add slash with path to make proper directory path.
    */

    $( "#pwapresta_iopn_dump_url" ).blur(function() {
        checkStringEndWithSlash($("#pwapresta_iopn_dump_url").attr('id'));
    });

    $( "#pwapresta_mws_order_dump_url" ).blur(function() {
        checkStringEndWithSlash($("#pwapresta_mws_order_dump_url").attr('id'));
    });

    $( "#pwapresta_mws_report_dump_url" ).blur(function() {
        checkStringEndWithSlash($("#pwapresta_mws_report_dump_url").attr('id'));
    });
    
    
    

    //assign default value to url and make readonly filed
    $('#pwapresta_success_return_url').val($('#pwapresta_readonly_url').val()+"module/pwapresta/pwaorder?action=pwa_order");
    $('#pwapresta_success_return_url').attr("readonly" ,"readonly");

    $('#pwapresta_iopn_merchant_url').val($('#pwapresta_readonly_iopn_url').val()+"module/pwapresta/pwaiopn?action=pwa_iopn ");
    $('#pwapresta_iopn_merchant_url').attr("readonly" ,"readonly");

    $('#pwapresta_mws_order_api_url').val($('#pwapresta_readonly_url').val()+"module/pwapresta/pwamws?action=pwa_mws_order");
    $('#pwapresta_mws_order_api_url').attr("readonly" ,"readonly");

    $('#pwapresta_mws_schedule_report_api_url').val($('#pwapresta_readonly_url').val()+"module/pwapresta/pwamws?action=pwa_mws_report_schedule");
    $('#pwapresta_mws_schedule_report_api_url').attr("readonly" ,"readonly");

    $('#pwapresta_mws_report_api_url').val($('#pwapresta_readonly_url').val()+"module/pwapresta/pwamws?action=pwa_mws_report");
    $('#pwapresta_mws_report_api_url').attr("readonly" ,"readonly");

	$('#pwapresta_custom_html_code_data').val('width: 100% ! important; max-height: 40px ! important; max-width: 170px ! important;');
	$('#pwapresta_mws_report_dump_url').val('modules/pwapresta/mws_report_dump/');
	$('#pwapresta_mws_order_dump_url').val('modules/pwapresta/mws_order_dump/');
	$('#pwapresta_iopn_dump_url').val('modules/pwapresta/iopn_dump/');
   

    // change marketplace id and environment on change vice-versa
    $( "#pwapresta_marketplace_id" ).on( "change", function() {    
        if( $( "#pwapresta_marketplace_id" ).val() =="A3PY9OQTG31F3H" ){
          $( "#pwapresta_environment" ).val("production");
        }
        else{
          $( "#pwapresta_environment" ).val("sandbox");
        }
    });
    $( "#pwapresta_environment" ).on( "change", function() {    
        if( $( "#pwapresta_environment" ).val() =="production" ){
          $( "#pwapresta_marketplace_id" ).val("A3PY9OQTG31F3H");
        }
        else{
          $( "#pwapresta_marketplace_id" ).val("AXGTNDD750VEM");
        }
    });
    
    

  // check validaion on submit 
  $('#configuration_form').submit(function(event) {
     if(window.location.href.indexOf("pwapresta") >= 0)   
     {
        if( $.trim( $("#pwapresta_access_key").val()) == "" )
        {
            $('#pwapresta_access_key').css('border-color', 'red');
            event.preventDefault();
        }else{
            $('#pwapresta_access_key').css('border-color', '#DDDDDD');
        }

        if( $.trim( $("#pwapresta_secret_key").val()) == "" )
        {
            $('#pwapresta_secret_key').css('border-color', 'red');
            event.preventDefault();
        }
        else{
            $('#pwapresta_secret_key').css('border-color', '#DDDDDD');
        }

        if( $.trim( $("#pwapresta_merchant_id").val()) == "" )
        {
            $('#pwapresta_merchant_id').css('border-color', 'red');
            event.preventDefault();
        }
        else{
            $('#pwapresta_merchant_id').css('border-color', '#DDDDDD');
        }
        
        if($('#pwapresta_custom_pwa_image_url-name').val()  != ''){
			 
			 fileName = $('#pwapresta_custom_pwa_image_url-name').val();
			 fileExtension = fileName.substr((fileName.lastIndexOf('.') + 1));
			 if ( !(fileExtension == 'jpg' || fileExtension == 'jpeg' || fileExtension == 'png' || fileExtension == 'gif' ) )
			 {
				 $("#pwa_btn_img_error").html(" only jpg, jpeg, png, gif extension image allowed!");
				 event.preventDefault();
			 }
        }
           
      }
  });


    function showHideIOPNDumpFilePath(iopn){
	   if(iopn ==1){
            $('#pwapresta_iopn_dump_url').parent('div').parent('div').show();
        }
        else{
            $('#pwapresta_iopn_dump_url').parent('div').parent('div').hide();
        }
    }
    function showHideMWSOrderDumpFilePath(mws_order){
       if(mws_order ==1){
            $('#pwapresta_mws_order_dump_url').parent('div').parent('div').show();
        }
        else{
            $('#pwapresta_mws_order_dump_url').parent('div').parent('div').hide();
        }
    }
    function showHideMWSReportDumpFilePath(mws_report){
       if(mws_report ==1){
            $('#pwapresta_mws_report_dump_url').parent('div').parent('div').show();
        }
        else{
            $('#pwapresta_mws_report_dump_url').parent('div').parent('div').hide();
        }
    }
    function checkStringEndWithSlash(ids){
          str= $( "#"+ids ).val()
          len= str.length;
          var n = str.lastIndexOf("/");
          if (len-1 != n)
             str=    str.concat("/");
          $( "#"+ids ).attr('value',str);
    }


     });
})(jQuery);

