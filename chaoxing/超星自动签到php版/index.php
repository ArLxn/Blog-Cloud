<?php
function set_zh_file($filename,$psw){
	$file = fopen("./zh/".$filename, "w") or die("写到账号失败");
	fwrite($file, $psw);
	fclose($file);
}
function get_zh_file($phone){
	$file = fopen("./zh/".$phone, "r") or die("读取账号失败");
	$text=fread($file,filesize("./zh/".$phone));
	fclose($file);
	login($phone,$text);
}
function set_cookie_file($filename,$cookies){
	$file = fopen("./temp/".$filename, "w") or die("Unable to open file!");
	fwrite($file, $cookies);
	fclose($file);
}
function get_cookie_file($filename){
	$file = fopen("./temp/".$filename, "r") or die("无法读取cookie");
	$text=fread($file,filesize("./temp/".$filename));
	fclose($file);
	return $text;
}
function get_geturl($url,$headers){
	$ch = curl_init();
	//指定URL
	
	curl_setopt($ch, CURLOPT_URL, $url);
	//设定请求后返回结果
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headers); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	   
	//忽略header头信息
	curl_setopt($ch, CURLOPT_HEADER, 0);
	   
	//设置超时时间
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	//发送请求
	$output = curl_exec($ch);
	   
	//关闭curl
	curl_close($ch);
	return $output;
}


function login($phone, $psw)
{
	$data = "uname=$phone&code=$psw&loginType=1&roleSelect=true";
	#array("uname"=>$phone,"code"=>$psw,"loginType"=>1,"roleSelect"=>"true)
    $headerArray =array("user-agent:Dalvik/2.1.0 (Linux; U; Android 10; Redmi K30 5G MIUI/V11.0.7.0.QGICNXM) com.chaoxing.mobile/ChaoXingStudy_3_4.3.6_android_phone_496_27 (@Kalimdor)_8934fa880a1843e59a11aafb01377a3c");
	//初使化init方法
    $ch = curl_init();
    //指定URL

    curl_setopt($ch, CURLOPT_URL, "https://passport2-api.chaoxing.com/v11/loginregister?cx_xxt_passport=json");
    //设定请求后返回结果
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //声明使用POST方式来进行发送
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray); 
    curl_setopt($ch, CURLOPT_POST, 1);
    //发送什么数据呢
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
    //忽略证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
       
    //忽略header头信息
    curl_setopt($ch, CURLOPT_HEADER, 1);
       
    //设置超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    //发送请求
    $output = curl_exec($ch);
       
    //关闭curl
    curl_close($ch);
	if(strpos($output,'验证通过') !== false){ 
	 #echo '登录成功'; 
	 if(preg_match_all('/Set-Cookie:[\s]+([^=]+)=([^;]+)/', $output,$match)) {
	 	 $cookies="";
	             for($i=0;$i<count($match[1]);$i++)
	 			{
	 				$cookies=$cookies.$match[1][$i]."=".$match[2][$i].";";
	 			}
	 			set_cookie_file("$phone.txt",$cookies);
	 			
	 }
	 set_zh_file($phone,$psw);
	 echo '{"code":200,"msg":"登录成功！"}';
	}else{

	 echo $output; 
	}
	
    
}
function get_lea($phone){
	$cookies=get_cookie_file("$phone.txt");
	$headerArray =array(
	"user-agent:Dalvik/2.1.0 (Linux; U; Android 10; Redmi K30 5G MIUI/V11.0.7.0.QGICNXM) com.chaoxing.mobile/ChaoXingStudy_3_4.3.6_android_phone_496_27 (@Kalimdor)_8934fa880a1843e59a11aafb01377a3c",
	"Content-type:application/json;charset='utf-8'",
	"Accept:application/json",
	"Cookie:$cookies"
	);
	$url="https://mooc1-api.chaoxing.com/mycourse/backclazzdata?view=json&mcode=";
	$output=get_geturl($url,$headerArray);
	if($output==""){
		
		echo '{"code":0,"msg":"读取课程失败！尝试重新登录"}';
		get_zh_file($phone);
	}
	else{
		$sz=array();
		$lesdata=json_decode($output,true)['channelList'];
		for($i=0;$i<count($lesdata);$i++){
			
			$courseid = $lesdata[$i]['content']['course']['data'][0]['id'];
			$classid = $lesdata[$i]['content']['id'];
			$name = $lesdata[$i]['content']['course']['data'][0]['name'];
			$addurl='http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?type=qd&phone=$phone&courseid=$courseid&classid=$classid";
			$temp=array("courseid"=>$courseid,"classid"=>$classid,"name"=>$name,'qdurl'=>$addurl);
			array_push($sz,$temp);
		}
		echo json_encode($sz);
	}

	
	
}
function get_all_qiandao($phone,$courseid,$classid){
	$cookies=get_cookie_file("$phone.txt");
	$headerArray =array(
	"user-agent:Dalvik/2.1.0 (Linux; U; Android 10; Redmi K30 5G MIUI/V11.0.7.0.QGICNXM) com.chaoxing.mobile/ChaoXingStudy_3_4.3.6_android_phone_496_27 (@Kalimdor)_8934fa880a1843e59a11aafb01377a3c",
	"Cookie:$cookies"
	);
	$url="https://mobilelearn.chaoxing.com/ppt/activeAPI/taskactivelist?courseId=$courseid&classId=$classid";
	$output=get_geturl($url,$headerArray);
	#echo $output;
	if($output=="")get_zh_file($phone);
	$qiandaodata=json_decode($output,true)['activeList'];
	#var_dump($qiandaodata);
	for($i=0;$i<count($qiandaodata);$i++){
		$type=$qiandaodata[$i]['activeType'];//是否是签到类型
		$status=$qiandaodata[$i]['status'];
		$qdtype=$qiandaodata[$i]['nameOne'];//签到的类型
		$urll=$qiandaodata[$i]['url'];
		if($type==2&&$status==1){//需要签到
			preg_match('/aryId=(.*?)&[\s|\S]*?uid=(.*?)&/',$urll,$match);
			qiandao($phone,$match[1],$match[2]);
			// switch($qdtype){
			// 	case '位置签到':
			// 		echo '位置签到';
			// 		break;
			// 	case '签到':
			// 		echo '普通签到或图片签到';
			// 		break;
			// 	default:
			// 		echo '通用签到';
			// }
		}
	}
	echo '监控完毕~';
}
function qiandao($phone,$activeId,$uid){
	//普通签到、手势签到、二维码签到 仅需两个参数activeid uid
	//位置签到 address latitude longitude activeId uid 地址 经纬度
		$cookies=get_cookie_file("$phone.txt");
	$headerArray =array("X-Forwarded-For:128.38.82.8","CLIENT-IP:233.82.81.8",
	"user-agent:Dalvik/2.1.0 (Linux; U; Android 10; Redmi K30 5G MIUI/V11.0.7.0.QGICNXM) com.chaoxing.mobile/ChaoXingStudy_3_4.3.6_android_phone_496_27 (@Kalimdor)_8934fa880a1843e59a11aafb01377a3c",
	"Cookie:$cookies"
	);
	$url="https://mobilelearn.chaoxing.com/pptSign/stuSignajax?activeId=$activeId&uid=$uid&clientip=&useragent=&latitude=-1&longitude=-1&appType=15&fid=0";
	$req=get_geturl($url,$headerArray);

	echo $req;
	
}
function ardess_qiandao($phone,$activeId,$uid,$address,$latitude,$longitude){
	//位置签到 address latitude longitude activeId uid 地址 经纬度
}
function pic_qiandao($phone,$activeId,$uid){
	//图片签到  $activeId,$uid,picobjid
	}
function upfile($phone,$pic){
	$cookies=get_cookie_file("$phone.txt");
	$headerArray =array(
	"user-agent:Dalvik/2.1.0 (Linux; U; Android 10; Redmi K30 5G MIUI/V11.0.7.0.QGICNXM) com.chaoxing.mobile/ChaoXingStudy_3_4.3.6_android_phone_496_27 (@Kalimdor)_8934fa880a1843e59a11aafb01377a3c",
	"Cookie:$cookies"
	);
	$url="https://pan-yz.chaoxing.com/api/token/uservalid";
	$token=json_decode(get_geturl($url,$headerArray),true)['_token'];
	$url="https://pan-yz.chaoxing.com/upload?_token=".$token;
	$ch = curl_init();
	//指定URL
	
	curl_setopt($ch, CURLOPT_URL, $url);
	//设定请求后返回结果
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//声明使用POST方式来进行发送
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray); 
	curl_setopt($ch, CURLOPT_POST, 1);
	//发送什么数据呢
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	//忽略证书
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	   
	//忽略header头信息
	curl_setopt($ch, CURLOPT_HEADER, 1);
	   
	//设置超时时间
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	//发送请求
	$output = curl_exec($ch);
	   
	//关闭curl
	curl_close($ch);
}

$phone=$_GET['phone'];
$t=$_GET['type'];
if($t==""||$phone=="")
	exit("参数不能为空！");
switch($t){
	
	case 'login':
		$psw=$_GET['psw'];
		login($phone,$psw);
		break;
	case 'lea':
		get_lea($phone);
		break;
	case 'qd':
		$courseid=$_GET['courseid'];
		$classid=$_GET['classid'];
		get_all_qiandao($phone,$courseid,$classid);
		break;
}