<?php

/*
   This is NOT a freeware, use is subject to license terms
   版权所有：TOM微信 www.tomwx.net
   微信支付回调接口文件
*/

define('APPTYPEID', 127);
define('CURSCRIPT', 'plugin');
define('DISABLEXSSCHECK', true); 

$_GET['id'] = 'tom_love';

require substr(dirname(__FILE__), 0, -23).'/source/class/class_core.php';

$discuz = C::app();
$cachelist = array('plugin', 'diytemplatename');

$discuz->cachelist = $cachelist;
$discuz->init();

define('CURMODULE', 'tom_love');

$_G['siteurl'] = substr($_G['siteurl'], 0, -23);
$_G['siteroot'] = substr( $_G['siteroot'], 0, - 23);

$jyConfig = $_G['cache']['plugin']['tom_love'];
/*
$wxpay_appid        = trim($jyConfig['love_appid']);
$wxpay_mchid        = trim($jyConfig['wxpay_mchid']);
$wxpay_key          = trim($jyConfig['wxpay_key']);
$wxpay_appsecret    = trim($jyConfig['love_appsecret']);
*/
define("TOM_WXPAY_APPID", '');
define("TOM_WXPAY_MCHID", '');
define("TOM_WXPAY_KEY", "");
define("TOM_WXPAY_APPSECRET", "");

include DISCUZ_ROOT.'./source/plugin/tom_love/wxpay/lib/WxPay.Api.php';
include DISCUZ_ROOT.'./source/plugin/tom_love/wxpay/lib/WxPay.Notify.php';
include DISCUZ_ROOT.'./source/plugin/tom_love/wxpay/log.php';

$logDir = DISCUZ_ROOT."./source/plugin/tom_love/logs/";
if(!is_dir($logDir)){
    mkdir($logDir, 0777,true);
}else{
    chmod($logDir, 0777); 
}
$logHandler= new CLogFileHandler(DISCUZ_ROOT."./source/plugin/tom_love/logs/".date("Y-m-d").".log");
$log = Log::Init($logHandler, 15);

function dump_log($data){
	$logDir = DISCUZ_ROOT."./source/plugin/tom_love/logs/";
		if(!is_dir($logDir)){
			mkdir($logDir, 0777,true);
		}else{
			chmod($logDir, 0777); 
		}
    $file = DISCUZ_ROOT."./source/plugin/tom_love/logs/".date("Y-m-d").".wxapppay.log";
    file_put_contents($file,date("Y-m-d H:i:s").":【".json_encode($data)."】\n",FILE_APPEND);
}


class PayNotifyCallBack extends WxPayNotify{
    
	public function Queryorder($transaction_id){
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);
        Log::DEBUG("query:" . json_encode($result));
		dump_log($result);
		if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS"){
			return true;
		}
		return false;
	}
	
	public function NotifyProcess($data, &$msg){
        global $jyConfig;
        
        Log::DEBUG("call back:" . json_encode($data));
        
		$notfiyOutput = array();
		
		if(!array_key_exists("transaction_id", $data)){
            Log::DEBUG("error:can shu cuo wu");
            $msg = "can shu cuo wu";
			return false;
		}
		if(!$this->Queryorder($data["transaction_id"])){
            Log::DEBUG("error:ding dan cha xu shi bai");
            $msg = "ding dan cha xu shi bai";
			return false;
		}
        
        if(isset($data['result_code']) && $data['result_code']=='SUCCESS'){
        }else{
            Log::DEBUG("error:result_code error");
            $msg = "result_code error";
            return false;
        }
        
        if(isset($data['out_trade_no']) && !empty($data['out_trade_no'])){
        }else{
            Log::DEBUG("error:out_trade_no error");
            $msg = "out_trade_no error";
            return false;
        }
        
        $orderInfo = C::t('#tom_love#tom_love_order')->fetch_by_order_no($data['out_trade_no']);
        if($orderInfo && $orderInfo['order_status'] == 1){
            $updateData = array();
            $updateData['order_status'] = 2;
            $updateData['pay_time'] = TIMESTAMP;
            C::t('#tom_love#tom_love_order')->update($orderInfo['id'],$updateData);
            
            Log::DEBUG("update order:" . json_encode($orderInfo));
            
            if($orderInfo['order_type'] == 1){
                $userinfo = C::t('#tom_love#tom_love')->fetch_by_id($orderInfo['user_id']);
                $updateData = array();
                $updateData['score'] = $userinfo['score'] + $orderInfo['score_value'];
                C::t('#tom_love#tom_love')->update($userinfo['id'],$updateData);
                
                $insertData = array();
                $insertData['user_id'] = $userinfo['id'];
                $insertData['score_value'] = $orderInfo['score_value'];
                $insertData['log_type'] = 14;
                $insertData['log_time'] = TIMESTAMP;
                C::t('#tom_love#tom_love_scorelog')->insert($insertData);
                
            }else if($orderInfo['order_type'] == 2){
                $userinfo = C::t('#tom_love#tom_love')->fetch_by_id($orderInfo['user_id']);
                
                $vip_time = TIMESTAMP;
                if($userinfo['vip_time'] > TIMESTAMP){
                    $vip_time = $userinfo['vip_time'] + $orderInfo['time_value']*30*86400;
                }else{
                    $vip_time = TIMESTAMP + $orderInfo['time_value']*30*86400;
                }
                $updateData = array();
                $updateData['vip_id'] = 1;
                $updateData['vip_time'] = $vip_time;
                C::t('#tom_love#tom_love')->update($userinfo['id'],$updateData);
            }
            
            
        }
        
		return true;
	}
    
}

Log::DEBUG("begin notify3");
$notify = new PayNotifyCallBack();
$notify->Handle(false);
?>
