<?php

require_once "config.php";
define(NAME, $_POST["name"]);
define(EMAIL, $_POST["email"]);
define(MESSAGE, $_POST["message"]);

if (strlen(NAME) && strlen(EMAIL) && strlen(MESSAGE))
{
    $msg = sprintf("From: %s\nE-mail: %s\nIP address: %s\n\n%s", NAME, EMAIL, IP_ADDRESS, MESSAGE);
    if (mail("admin@luajit.ru", "Website Feedback", $msg, "From: feedback@lua.kl.com.ua"))
        exit("Message sent. (<a href='/'>Go back</a>)");
    else
        exit("An error has occurred while trying to send the message. Please try again later. (<a href='?'>Go back</a>)");
}

?>

<form method="post">
    <table>
        <tr><td>Your name:</td></tr>
        <tr><td><input type="text" name="name" placeholder="John Doe" /></td></tr>
        <tr></tr>
        <tr><td>Your e-mail address:</td></tr>
        <tr><td><input type="text" name="email" placeholder="johndoe@gmail.com" /></td></tr>
        <tr></tr>
        <tr><td>Your message:</td></tr>
        <tr><td><textarea name="message" cols="40" rows="20"></textarea></td></tr>
        <tr></tr>
        <tr><td><button type="submit">Report</button></td></td>
    </table>
</form>