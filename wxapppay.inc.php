<?php
session_start();

/**
 *  1 待支付 2 已支付
 */
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$jyConfig = $_G['cache']['plugin']['tom_love'];
$payment_mode = '微信APP支付';
$out_trade_no = isset($_GET['out_trade_no'])? addslashes($_GET['out_trade_no']):"";
$outArr = array(
	'status'=> 1,
);


$act = isset($_GET['act'])? addslashes($_GET['act']):"score";

if($_SESSION['status'] == "score"){
	    
    $user_id    = isset($_GET['user_id'])? intval($_GET['user_id']):0;
    $openid    = isset($_GET['openid'])? daddslashes($_GET['openid']):"";
    $pay_price  = intval($_GET['pay_price'])>0? intval($_GET['pay_price']):20;
    $userinfo = C::t('#tom_love#tom_love')->fetch_by_id($user_id);
    if(!$userinfo && !$_GET['pay_price']){
        $outArr = array(
            'status'=> $pay_price,
        );
        echo json_encode($outArr); exit;
    }
    
    $yuan_score_listStr = str_replace("\r\n","{n}",$jyConfig['yuan_score_list']); 
    $yuan_score_listStr = str_replace("\n","{n}",$yuan_score_listStr);
    $yuan_score_listTmpArr = explode("{n}", $yuan_score_listStr);

    $yuan_scoreArr = array();
    if(is_array($yuan_score_listTmpArr) && !empty($yuan_score_listTmpArr)){
        foreach ($yuan_score_listTmpArr as $key => $value){
            if(!empty($value)){
                list($yuan, $score) = explode("|", $value);
                $yuan = intval($yuan);
                $score = intval($score);
                if(!empty($yuan) && !empty($score)){
                    $yuan_scoreArr[$yuan] = $score;
                }
            }
        }
    }
    $pay_price = $_SESSION['pay_price'];
    if(!isset($yuan_scoreArr[$pay_price])){
        $outArr = array(
            'status'=> 302,
        );
        echo json_encode($outArr); exit;
    }
	
		if($out_trade_no){
			C::t('#tom_love#tom_love_order')->delete_by_order_no($out_trade_no);
			
			$insertData = array();
			$insertData['order_no']         = $out_trade_no;
			$insertData['openid']           = $openid;
			$insertData['user_id']          = $user_id;
			$insertData['score_value']      = $yuan_scoreArr[$pay_price];
			$insertData['pay_price']        = $pay_price;
			$insertData['order_status']     = 1;
			$insertData['order_time']       = TIMESTAMP;
			$insertData['payment_mode']       = $payment_mode;
			if(C::t('#tom_love#tom_love_order')->insert($insertData)){
				
				$outArr = array(
					'status'=> 200
				);
				echo json_encode($outArr); exit;
			}else{
				$outArr = array(
					'status'=> 304,
				);
				echo json_encode($outArr); exit;
			}
		}
		$outArr = array(
					'status'=> 306,
				);
		echo json_encode($outArr); exit;

        

    
    
}else if($_SESSION['status'] == "vip"){

    $user_id    = isset($_GET['user_id'])? intval($_GET['user_id']):0;
    $openid     = isset($_GET['openid'])? daddslashes($_GET['openid']):"";
    $month_id   = intval($_GET['month_id'])>0? intval($_GET['month_id']):$_SESSION['months'];
    $vip_id     = intval($_GET['vip_id'])>0? intval($_GET['vip_id']):1;
    
    $userinfo = C::t('#tom_love#tom_love')->fetch_by_id($user_id);
    if(!$userinfo){
        $outArr = array(
            'status'=> 301,
        );
        echo json_encode($outArr); exit;
    }
    
    $yuan_vip1_listStr = str_replace("\r\n","{n}",$jyConfig['yuan_vip1_list']); 
    $yuan_vip1_listStr = str_replace("\n","{n}",$yuan_vip1_listStr);
    $yuan_vip1_listTmpArr = explode("{n}", $yuan_vip1_listStr);
    
    $yuan_vip1Arr = array();
    if(is_array($yuan_vip1_listTmpArr) && !empty($yuan_vip1_listTmpArr)){
        foreach ($yuan_vip1_listTmpArr as $key => $value){
            if(!empty($value)){
                list($month, $price) = explode("|", $value);
                $month = intval($month);
                $price = intval($price);
                if(!empty($month) && !empty($price)){
                    $yuan_vip1Arr[$month] = $price;
                }
            }
        }
    }

    if(!isset($yuan_vip1Arr[$month_id])){
        $outArr = array(
            'status'=> 302,
        );
        echo json_encode($outArr); exit;
    } 

	if($vip_id == 1){
		$order_type = 2;
	}else if($vip_id == 2){
		$order_type = 3;
	}
	C::t('#tom_love#tom_love_order')->delete_by_order_no($out_trade_no);
	$insertData = array();
	$insertData['order_no']         = $out_trade_no;
	$insertData['order_type']       = $order_type;
	$insertData['openid']           = $openid;
	$insertData['user_id']          = $user_id;
	$insertData['score_value']      = 0;
	$insertData['time_value']       = $month_id;
	$insertData['pay_price']        = $yuan_vip1Arr[$month_id];
	$insertData['order_status']     = 1;
	$insertData['order_time']       = TIMESTAMP;
	$insertData['payment_mode']       = $payment_mode;
	if(C::t('#tom_love#tom_love_order')->insert($insertData)){
		$outArr = array(
			'status'=> 200
		);
		echo json_encode($outArr); exit;
	}else{
		$outArr = array(
			'status'=> 304,//304
		);
		echo json_encode($outArr); exit;
	}

   
    
}else{
    $outArr = array(
        'status'=> 111111,
    );
    echo json_encode($outArr); exit;
}


    
?>
