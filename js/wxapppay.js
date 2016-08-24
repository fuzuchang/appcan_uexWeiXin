window.uexOnload = function()
{
	//注册APP
	uexWeiXin.registerApp(wx_app.appid);
	//预支付回调
	uexWeiXin.cbGetPrepayId = function(data)
	{
		var pay = JSON.parse(data);
		var date = new Date();
		var timestamp = date.getTime().toString().substring(0, 10);
		if(pay.result_code == "SUCCESS"){
			var param1 = {
					appid		: pay.appid ,
					noncestr	: pay.nonce_str ,
					package		: 'Sign=WXPay' ,
					partnerid	: pay.mch_id ,
					prepayid	: pay.prepay_id ,
					timestamp	: timestamp
				};
			var str =   "appid=" 		+ param1.appid +
						"&noncestr=" 	+ param1.noncestr + 
						"&package=" 	+ param1.package +
						"&partnerid=" 	+ param1.partnerid +
						"&prepayid=" 	+ param1.prepayid + 
						"&timestamp=" 	+ param1.timestamp + 
						"&key=" 		+ wx_app.key;
				//生成签名
				param1.sign = $.md5(str).toUpperCase();
				//APP端 调起支付
				uexWeiXin.startPay(JSON.stringify(param1));
		}else{
			uexWindow.toast("1", "5", "创建订单失败", "5000");
		}
	}
	//调起支付结果的回调
	uexWeiXin.cbStartPay = function(data)
	{
        //alert(JSON.stringify(data));
	}
}
//APP端 预支付
function getPrepayId()
{
	var params = {
		appid 			: wx_app.appid ,				
		mch_id			: wx_app.mch_id ,				
		nonce_str		: wx_app.nonce_str ,			
		body			: wx_app.body ,					
		out_trade_no	: wx_app.out_trade_no ,			
		total_fee		: wx_app.total_fee ,			
		spbill_create_ip: wx_app.spbill_create_ip ,		
		notify_url		: wx_app.notify_url ,			
		trade_type		: wx_app.trade_type ,			
		sign			: wx_app.sign					
	};
	var post_data = $('#wxapppayForm').serialize();
	$.post('plugin.php?id=tom_love:wxapppay',post_data,function(json){
		if(json.status == 200){
			uexWeiXin.getPrepayId(JSON.stringify(params));
		}else{
			uexWindow.toast("1", "5", "创建订单失败,"+json.status, "5000");
		}
	},'json');
}


//异步查询支付订单状态
$(function(){
	setInterval(function()
	{
		var post_data = {};
			post_data.act = 'query_payment_order';
			post_data.out_trade_no = document.getElementById("out_trade_no").value;
			$.get('plugin.php?id=tom_love:alipay',post_data,function(json)
			{
				if(json.status == 200)
				{
					$(".score_recharge_box_1").text("支付成功");
					setTimeout(function()
					{
						location.href = 'plugin.php?id=tom_love&mod=my';
					},5000);
				}
			},'json');
	},5000);
});