<?php

require "config.php";
require "functions.php";
require "luajit.php";

printf("<h3>LuaJIT script scanner</h3>\n");

if (isset($_FILES["f"]) && $_FILES["f"]["size"] > 0)
{
    if ($_FILES["f"]["size"] > $MAX_UPLOAD_SIZE)
        err("The file you are trying to upload exceeds the 32 MB uploading limit");
    $s = new Lua($_FILES["f"]["tmp_name"]);
    if (!$s->isCompiled())
        err("The file is not a LuaJIT compiled script");
    if ($_SESSION["f"] && file_exists($_SESSION["f"]["path"]))
    {
        @unlink($_SESSION["f"]["path"]);
        @unlink($_SESSION["f"]["path"] . "-copy");
    }
    $_SESSION["f"] = array(
        "name" => basename($_FILES["f"]["name"]),
        "path" => "files/" . md5(IP_ADDRESS . rand(111111111, 999999999))
    );
    move_uploaded_file($_FILES["f"]["tmp_name"], $_SESSION["f"]["path"]);
    copy($_SESSION["f"]["path"], $_SESSION["f"]["path"] . "-copy");
}

if (file_exists($f = $_SESSION["f"]["path"]))
{
    if (isset($_GET["dl"]))
    {
        if (ob_get_level())
        	ob_end_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header(sprintf('Content-Disposition: attachment; filename="%s"', $_SESSION["f"]["name"]));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($f));
        readfile($f);
        exit;
    }

    if (isset($_GET["restore"]))
    {
        @unlink($f);
        @copy($f . "-copy", $f);
    }

    if (isset($_GET["del"]))
    {
        @unlink($f);
        @unlink($f . "-copy");
        unset($_SESSION["f"]);
        err("File deleted");
    }

    if (isset($_GET["sub"]))
    {
        $n = $_GET["sub"];
        $s = new Lua($f);
        if (!$protos = $s->protos())
        {
            @unlink($f);
            @unlink($f . "-copy");
            unset($_SESSION["f"]);
            err("An error has occurred while scanning the script");
        }
        if ($n < 0 || $n > count($protos) - 1)
            err("You have entered an incorrect value");
        $result = substr($s->data, 0, $protos[$n]["pos"]);
        // $result .= "\x0F\x00\x00\x01\x00\x00\x00\x02\x2B\x00\x01\x00\x4C\x00\x02\x00";
        $result .= "\x0B\x00\x00\x01\x00\x00\x00\x01\x4B\x00\x01\x00";
        $result .= substr($s->data, $protos[$n + 1]["pos"]);
        file_put_contents($f, $result);
    }

    if (isset($_GET["delf"]))
    {
        $n = $_GET["delf"];
        $s = new Lua($f);
        if (!$protos = $s->protos())
        {
            @unlink($f);
            @unlink($f . "-copy");
            unset($_SESSION["f"]);
            err("An error has occurred while scanning the script");
        }
        if ($n < 0 || $n > count($protos) - 1)
            err("You have entered an incorrect value");
        $result = substr($s->data, 0, $protos[$n]["pos"]);
        $result .= substr($s->data, $protos[$n + 1]["pos"]);
        file_put_contents($f, $result);
    }
    
    if (isset($_GET["unhide"]))
    {
        echo "<p style='color: #dd0000; font-weight: bold'>unavailable now</p>\n";
    }

    $s = new Lua($f);
    if (!$protos = $s->protos())
    {
        @unlink($f);
        @unlink($f . "-copy");
        unset($_SESSION["f"]);
        err("An error has occurred while scanning the script");
    }
    printf("File: %s", basename($_SESSION["f"]["name"]));
    printf(" [<a href='?dl'>download</a>] [<a href='?restore'>restore</a>] [<a href='conv'>convert</a>] [<a href='?del'>delete</a>]<br />\n");
    printf("File size: %d bytes (original size: %d bytes)<br />\n", filesize($f), filesize($f . "-copy"));
    printf("LuaJIT version: %s<br />\n", $s->version());
    printf("Prototypes: %d<br />\n", count($protos));
    foreach ($protos as $k => $v)
    {
        echo '<div class="proto">';
        printf("[<a href='?delf=%d'>del</a>] [<a href='?sub=%d'>sub</a>] [%d] pos = %X, size = %X, fullsize = %X, flags = %X, params = %X, framesize = %X, globals = %X, numkgc = %X, numkn = %X, numbc = %X, ins = %X",
        $k, $k, $k, $v["pos"], $v["size"], $v["fullsize"], $v["flags"], $v["params"], $v["framesize"], $v["globals"], $v["numkgc"], $v["numkn"], $v["numbc"], $v["ins"]);
        $func = substr($s->data, $v["pos"], $v["fullsize"]);
        if (strpos($func, "\x12\x01\x00\x00\x39\x00\x01\x00\x42\x00\x02\x02\x42\x00\x01\x02") !== false)
            printf(" -- looks like anti-python.babulya");
        elseif (strpos($func, "\x0B\x01\x00\x00\x58\x02\x03\x80\x36\x02\x00\x00\x39\x02\x01\x02\x32\x00\x04\x80\x36\x02\x02\x00\x33\x03\x03\x00\x42\x02\x02\x01") !== false)
            printf(" -- looks like anti-python.arthur");
        elseif (strpos($func, "\x2A\xDA\xDA\x00\x2A\xDB\xDB\x00\x2A\xDC\xDC\x00") !== false)
            printf(" -- looks like anti-autoit.1");
        elseif (strpos($func, "\x29\x6E\x01\x00\x29\x6F\x01\x00\x29\x70\x01\x00") !== false)
            printf(" -- looks like anti-autoit.2");
        elseif (strpos($func, "\x55\x00\xFF\x7F") !== false)
            printf(" -- looks like anti-python.2");
        elseif (strpos($func, "\x3B\x00\x00\x00\x00\x00\x00\x0D\x61\x39\x56\x18\x55\x61\x89\x42\x42\x16\x46\x17\x54\x70\x10\x58\x60\x10\x10\x01\x75\x10\xF0\xC0\x00\x01\x00\x02\x00\xC0\x00\xD0\x00\x01\x00\xC4\x00\x01\x00\x02\x00\x01\x00\x00\x02\x00\x00") !== false)
            printf(" -- looks like anti-python.1");
        elseif (strpos($func, "\x36\x00\x00\x00\x36\x01\x01\x00\x36\x02\x02\x00\x36\x03\x00\x00\x36\x04\x03\x00\x36\x05\x03\x00\x36\x06\x04\x00\x36\x07\x05\x00") !== false)
            printf(" -- looks like anti-python.3");
        elseif (strpos($func, "\x39\x00\x04\x00\x42\x00\x02\x02\x42\x00\x01\x02\x12\x01\x00\x00\x39\x00\x04\x00\x42\x00\x02\x02\x42\x00\x01\x02\x12\x01\x00\x00") !== false)
            printf(" -- looks like anti-python.4");
        elseif ($v["numbc"] == 0)
            printf(" -- empty func");
        elseif (preg_match("/\x58\x02\x01\x80\x58..\x80/", $func))
            printf(" -- looks like anti-python.hide [<a href='?unhide=%d'>unhide</a>]", $k);
        printf("</div>\n");
    }
    
    exit;
}

?>

<form class="file_upload" enctype="multipart/form-data" method="POST">
    Choose a file (*.luac): <input name="f" type="file" />
    <button type="submit" class="start_upload">START SCANNING</button>
</form>

(max 32 mb)