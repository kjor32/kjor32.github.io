<?php

$username = $_GET["u"];
$password = $_GET["p"];

$code = $_GET["ac"];
$secret = $_GET["sw"];

$users = array(
    "northon" => array(
        "password" => "password",
        "auth_code" => "auth", /* temporary */
        "secret_word" => "secret"
    ),
    
    "admin" => array(
        "password" => "password",
        "auth_code" => "auth" /* temporary */
    ),
    
    "testing" => array(
        "password" => "password"
    )
);

if (isset($username) && isset($password))
{
    if ($username == "northon" && $password == "northon")
    {
        $auth = array(
            "status" => "success",
            "hello" => "You are signed in as northon"
        );
    }
    
    else if ($username == "root" && $password == "password")
    {
        if (isset($code))
        {
            if ($code == "code" && $secret == "secret")
            {
                $auth = array(
                    "status" => "success",
                    "hello" => "You are signed in as root"
                );
            } else {
                $auth = array(
                    "status" => "error",
                    "message" => "Access denied."
                );
            }
        } else {
            $auth = array(
                "status" => "next",
                "secret_word" => "required"
            );
        }
    }
    
    else if ($username == "testing" && $password == "testing")
    {
        if (isset($code))
        {
            if ($code == "code")
            {
                $auth = array(
                    "status" => "success",
                    "hello" => "You are signed in as testing"
                );
            } else {
                $auth = array(
                    "status" => "error",
                    "message" => "Access denied."
                );
            }
        } else {
            $auth = array(
                "status" => "next",
                "secret_word" => "no"
            );
        }
    }
    
    else
    {
        $auth = array(
            "status" => "error",
            "message" => "Wrong username or password."
        );
    }
    
    die(json_encode($auth));
}