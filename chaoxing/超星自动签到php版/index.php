<?php
function set_zh_file($filename,$psw){
	$file = fopen("./zh/".$filename, "w") or die("д���˺�ʧ��");
	fwrite($file, $psw);
	fclose($file);
}
function get_zh_file($phone){
	$file = fopen("./zh/".$phone, "r") or die("��ȡ�˺�ʧ��");
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
	$file = fopen("./temp/".$filename, "r") or die("�޷���ȡcookie");
	$text=fread($file,filesize("./temp/".$filename));
	fclose($file);
	return $text;
}
function get_geturl($url,$headers){
	$ch = curl_init();
	//ָ��URL
	
	curl_setopt($ch, CURLOPT_URL, $url);
	//�趨����󷵻ؽ��
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headers); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	   
	//����headerͷ��Ϣ
	curl_setopt($ch, CURLOPT_HEADER, 0);
	   
	//���ó�ʱʱ��
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	//��������
	$output = curl_exec($ch);
	   
	//�ر�curl
	curl_close($ch);
	return $output;
}


function login($phone, $psw)
{
	$data = "uname=$phone&code=$psw&loginType=1&roleSelect=true";
	#array("uname"=>$phone,"code"=>$psw,"loginType"=>1,"roleSelect"=>"true)
    $headerArray =array("user-agent:Dalvik/2.1.0 (Linux; U; Android 10; Redmi K30 5G MIUI/V11.0.7.0.QGICNXM) com.chaoxing.mobile/ChaoXingStudy_3_4.3.6_android_phone_496_27 (@Kalimdor)_8934fa880a1843e59a11aafb01377a3c");
	//��ʹ��init����
    $ch = curl_init();
    //ָ��URL

    curl_setopt($ch, CURLOPT_URL, "https://passport2-api.chaoxing.com/v11/loginregister?cx_xxt_passport=json");
    //�趨����󷵻ؽ��
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //����ʹ��POST��ʽ�����з���
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray); 
    curl_setopt($ch, CURLOPT_POST, 1);
    //����ʲô������
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
    //����֤��
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
       
    //����headerͷ��Ϣ
    curl_setopt($ch, CURLOPT_HEADER, 1);
       
    //���ó�ʱʱ��
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    //��������
    $output = curl_exec($ch);
       
    //�ر�curl
    curl_close($ch);
	if(strpos($output,'��֤ͨ��') !== false){ 
	 #echo '��¼�ɹ�'; 
	 if(preg_match_all('/Set-Cookie:[\s]+([^=]+)=([^;]+)/', $output,$match)) {
	 	 $cookies="";
	             for($i=0;$i<count($match[1]);$i++)
	 			{
	 				$cookies=$cookies.$match[1][$i]."=".$match[2][$i].";";
	 			}
	 			set_cookie_file("$phone.txt",$cookies);
	 			
	 }
	 set_zh_file($phone,$psw);
	 echo '{"code":200,"msg":"��¼�ɹ���"}';
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
		
		echo '{"code":0,"msg":"��ȡ�γ�ʧ�ܣ��������µ�¼"}';
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
		$type=$qiandaodata[$i]['activeType'];//�Ƿ���ǩ������
		$status=$qiandaodata[$i]['status'];
		$qdtype=$qiandaodata[$i]['nameOne'];//ǩ��������
		$urll=$qiandaodata[$i]['url'];
		if($type==2&&$status==1){//��Ҫǩ��
			preg_match('/aryId=(.*?)&[\s|\S]*?uid=(.*?)&/',$urll,$match);
			qiandao($phone,$match[1],$match[2]);
			// switch($qdtype){
			// 	case 'λ��ǩ��':
			// 		echo 'λ��ǩ��';
			// 		break;
			// 	case 'ǩ��':
			// 		echo '��ͨǩ����ͼƬǩ��';
			// 		break;
			// 	default:
			// 		echo 'ͨ��ǩ��';
			// }
		}
	}
	echo '������~';
}
function qiandao($phone,$activeId,$uid){
	//��ͨǩ��������ǩ������ά��ǩ�� ������������activeid uid
	//λ��ǩ�� address latitude longitude activeId uid ��ַ ��γ��
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
	//λ��ǩ�� address latitude longitude activeId uid ��ַ ��γ��
}
function pic_qiandao($phone,$activeId,$uid){
	//ͼƬǩ��  $activeId,$uid,picobjid
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
	//ָ��URL
	
	curl_setopt($ch, CURLOPT_URL, $url);
	//�趨����󷵻ؽ��
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//����ʹ��POST��ʽ�����з���
	curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray); 
	curl_setopt($ch, CURLOPT_POST, 1);
	//����ʲô������
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	//����֤��
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	   
	//����headerͷ��Ϣ
	curl_setopt($ch, CURLOPT_HEADER, 1);
	   
	//���ó�ʱʱ��
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	//��������
	$output = curl_exec($ch);
	   
	//�ر�curl
	curl_close($ch);
}

$phone=$_GET['phone'];
$t=$_GET['type'];
if($t==""||$phone=="")
	exit("��������Ϊ�գ�");
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