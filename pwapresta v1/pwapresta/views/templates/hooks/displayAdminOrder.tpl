<div class="row">
	 <div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading">
				<i class="icon-credit-card"></i>
				Amazon Order Details
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div>Amazon Order Id : <a href="https://sellercentral.amazon.in/gp/orders-v2/details/ref=cb_orddet_cont_myo?ie=UTF8&orderID={$AmazonOrderId}" target="_blank">{$AmazonOrderId}</a></div>
					<div>Shipping Type : {$ShippingType}</div>
					{if $OrderType == 'junglee'}
					<div><b>Note : This is a Junglee order.</b></div>
					{/if}
				</div>
			</div>
		</div>    
	 </div>           
 </div>
