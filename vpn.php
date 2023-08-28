<?php

// Your API Key.
$key = 'PdRN2eNjbRuObBWrLTamQTEtEC00YE3e';

/*
* Retrieve the user's IP address. 
* You could also pull this from another source such as a database.
* 
* If you use cloudflare change REMOTE_ADDR to HTTP_CF_CONNECTING_IP
*/
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_CLIENT_IP'];
$status = -1;

$query = $sql->query("select * from ips where ip = ?", $ip);

if ($query->numRows() === 0)
{
    // Retrieve additional (optional) data points which help us enhance fraud scores.
    $user_agent = $_SERVER['HTTP_USER_AGENT']; 
    $user_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    
    // Set the strictness for this query. (0 (least strict) - 3 (most strict))
    $strictness = 0;
    
    // You may want to allow public access points like coffee shops, schools, corporations, etc...
    $allow_public_access_points = 'true';
    
    // Reduce scoring penalties for mixed quality IP addresses shared by good and bad users.
    $lighter_penalties = 'true';
    
    // Create parameters array.
    $parameters = array(
        'user_agent' => $user_agent,
    	'user_language' => $user_language,
    	'strictness' => $strictness,
    	'allow_public_access_points' => $allow_public_access_points,
    	'lighter_penalties' => $lighter_penalties
    );
    
    /* User & Transaction Scoring
    * Score additional information from a user, order, or transaction for risk analysis
    * Please see the documentation and example code to include this feature in your scoring:
    * https://www.ipqualityscore.com/documentation/proxy-detection/transaction-scoring
    * This feature requires a Premium plan or greater
    */
    
    // Format Parameters
    $formatted_parameters = http_build_query($parameters);
    
    // Create API URL
    $url = sprintf(
    	'https://www.ipqualityscore.com/api/json/ip/%s/%s?%s', 
    	$key,
    	$ip, 
    	$formatted_parameters
    );
    
    // Fetch The Result
    $json = file_get_contents($url);
    
    // Decode the result into an array.
    $result = json_decode($json, true);
    
    // Check to see if our query was successful.
    if(isset($result['success']) && $result['success'] === true){
        if ($result['is_crawler'] === true)
            $status = 0;
        /*elseif ($result['vpn'] === true && $result['proxy'] === true)
            $status = 2;*/
        elseif ($result['fraud_score'] > 85)
            $status = 1;
        else
            $status = 0;
        $sql->query("insert into ips (ip, time, status, fraud_score) values (?, ?, ?, ?)", $ip, time(), $status, $result['fraud_score']);
    }
} else {
    $result = $query->fetchArray();
    $status = $result["status"];
}

if ($status > 0)
    exit("<h1>Access Denied</h1><h3>Sorry, but you are probably using a VPN or your IP is banned.</h3><p>If you think this is a mistake, please contact the website administrator: <a href=\"https://vk.me/stdcin\">vk.me/stdcin</a><br />Your IP: $ip</p>");