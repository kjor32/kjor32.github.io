<?php

$f = fopen("logger.txt", "a+");
fwrite($f, sprintf("[%s] ip = %s\n", date("Y-m-d H:i:s"), $_SERVER["HTTP_CF_CONNECTING_IP"]));
fclose($f);

header("content-type: image/png");
echo file_get_contents("test.png");