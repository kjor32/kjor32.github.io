<?php




// database

$users = array(
    "root" => array(
        "password" => "123",
        "auth_code" => "123123", /* temporary */
        "secret_word" => "whatever"
    ),
    
    "admin" => array(
        "password" => "123",
        "auth_code" => "123123" /* temporary */
    ),
    
    "test" => array(
        "password" => "123"
    )
);




// processor

$data = "";
foreach (str_split($_GET["data"], 2) as $val)
    $data .= chr(hexdec($val));
$data = json_decode($data);

if (!is_object($data)) exit;

$response = array(
    "status" => "error",
    "message" => "Wrong username or password."
);

foreach ($users as $username => $userdata)
{
    if (strtolower($username) == strtolower($data->username) && $userdata["password"] == $data->password)
    {
        if (!isset($userdata["auth_code"]))
        {
            $response = array(
                "status" => "success",
                "hello" => "You are signed in as " . $username
            );
        }
        
        else
        {
            if (!isset($data->auth_code))
            {
                $response = array(
                    "status" => "next",
                    "secret_word" => "no"
                );
                
                if (isset($userdata["secret_word"]))
                    $response["secret_word"] = "required";
            }
            
            else
            {
                if (isset($userdata["secret_word"]) && $userdata["secret_word"] != $data->secret_word)
                    $response["message"] = "Wrong secret word";
                else if ($userdata["auth_code"] != $data->auth_code)
                    $response["message"] = "Wrong auth code";
                else
                {
                    $response = array(
                        "status" => "success",
                        "hello" => "You are signed in as " . $username
                    );
                }
            }
        }
    }
}

exit(json_encode($response));