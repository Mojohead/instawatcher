<?php

function post_result ($url, $postdata){
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $url);
 curl_setopt($ch, CURLOPT_POST, 1);
 curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
 curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // отключение сертификата
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 $result = curl_exec($ch);
 curl_close($ch);
 return $result;
}

function api($postdata){
	$result = post_result("https://api.sendsay.ru/", $postdata);
	$res = json_decode($result,true);
	print_r ($res);
	if ($res['REDIRECT']){
		$result = post_result("https://api.sendsay.ru/".$res['REDIRECT'], $postdata);
		return $result;
	}else{
		return $result;
	}
}

$params = array(
    	'apikey' => '18nu0YlSgJvHuUQsAIt5t_scGwZtWpgi_kS0fy2qQA4vm3N_5n7KL49fTA6ripZDF1RVXseM292Uhc31eeEYeAVI',
	"action" => "issue.send",
	"letter" => array (
		"from.name" => "Instagram Watcher",
	    	"from.email" => "paninaro9@yandex.ru",
	 	"subject" 	=> "@[% anketa.data.account %] new post",
	 	"message"	=> array(
	 	       "html" => "<HTML><HEAD></HEAD><BODY>
<a href=\"https://www.instagram.com/[% anketa.data.account %]\">@[% anketa.data.account %] профиль</a><br>
<a href=\"https://www.instagram.com/p/[% anketa.data.shortcode %]\">@[% anketa.data.account %] ссылка на новый пост</a><br>
<p>Текст поста:</p>
<p>[% anketa.data.text %]</p>

</BODY></HTML>"
	 	),
	),
     	"group" => "personal",
	"only_unique" => "1",    
	"sendwhen" => "now",
	//"users.list" => array

);

$params["users.list"]["caption"] = array(array("anketa" => "member","quest" =>"email") ,array("anketa" => "data","quest" =>"account"),array("anketa" => "data","quest" =>"shortcode"),array("anketa" => "data","quest" =>"text"));
$params["users.list"]["rows"] = array();

$accounts = file('accounts.txt');

foreach ($accounts as $acc){
	
	
	echo $acc."\n";


	$acc = trim($acc);
	$page = file_get_contents("https://www.instagram.com/" . $acc. "/");
	preg_match('/\<script type\=\"text\/javascript\"\>window\.\_sharedData \= ([^<]*)\;\</',$page,$matches);

	$arr = json_decode($matches[1],true);
	
	
	$posts = $arr['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];

	if (!$line = array_shift(file("data/" . $acc))){
		$line = 0;
	}

	$fh = fopen("data/".$acc, 'w');	

	foreach ($posts as $post){
		if ($post['node']['taken_at_timestamp'] > $line){

			echo $post['node']['taken_at_timestamp']."\n";
			
			
			

			$params["users.list"]["rows"] = array(array("paninaro9@yandex.ru",$acc,$post['node']['shortcode'],$post['node']['edge_media_to_caption']['edges'][0]['node']['text']));

			print_r($params);
			
			$param = json_encode($params);

			$postdata = "apiversion=100&json=1&request=" . urlencode($param);
			echo urldecode(api($postdata));
			
		}
		
		fwrite($fh, $post['node']['taken_at_timestamp']."\n");
	}

	fclose($fh);
	sleep(2);

	print_r($arr['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']);
	//break;
}

//[entry_data][ProfilePage][0][graphql][user][edge_owner_to_timeline_media]
