<?php

require "config.php";
require "functions.php";
require "luajit.php";

printf("<h3>Protect your LuaJIT scripts from being decompiled</h3>\n");

if (isset($_FILES["f"]) && $_FILES["f"]["size"] > 0)
{
    if ($_FILES["f"]["size"] > $MAX_UPLOAD_SIZE)
        err("The file you are trying to upload exceeds the 2 MB uploading limit");
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
        "path" => "files/" . md5($_SERVER["REMOTE_ADDR"] . rand(111111111, 999999999))
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

    if (isset($_GET["protect"]))
    {
        $n = (int)$_GET["protect"];
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

        // proto += x34
        // numbc += x0b
        int_uleb128($protos[$n]["numbc"], $numbc, $numbc_size);
        $numbc_pos = $protos[$n]["ins"] - $numbc_size;
        uleb128_int($s->data, $numbc_pos, $numbc);
        $numbc += 0x0d;
        int_uleb128($numbc, $numbc_new);
        $result = substr($s->data, 0, $numbc_pos);
        $result .= $numbc_new;
        $result .= "\x58\x02\x0C\x80\x61\x39\x56\x18\x55\x61\x89\x42\x42\x16\x46\x17\x54\x70\x10\x58\x60\x10\x10\x01\x75\x10\xF0\xC0\x00\x01\x00\x02\x00\xC0\x00\xD0\x00\x01\x00\xC4\x00\x01\x00\x02\x00\x01\x00\x00\x02\x00\x00\x00";
        $result .= substr($s->data, $numbc_pos + $numbc_size);
        $s->data = $result;

        uleb128_int($s->data, $protos[$n]["pos"], $proto_size, $size);
        $proto_size += 0x34;
        int_uleb128($proto_size, $new_size);
        $result = substr($s->data, 0, $protos[$n]["pos"]);
        $result .= $new_size;
        $result .= substr($s->data, $protos[$n]["pos"] + $size);

        file_put_contents($f, $result);
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
        $check = substr_count($s->data, "\x58\x02\x0C\x80\x61\x39\x56\x18\x55\x61\x89\x42\x42\x16\x46\x17\x54\x70\x10\x58\x60\x10\x10\x01\x75\x10\xF0\xC0\x00\x01\x00\x02\x00\xC0\x00\xD0\x00\x01\x00\xC4\x00\x01\x00\x02\x00\x01\x00\x00\x02\x00\x00\x00", $v["pos"], $v["fullsize"]);
        printf("[<a href='?protect=%d'>protect</a>] [%d] pos = %X, size = %X, protected = %d times", $k, $k, $v["pos"], $v["size"], $check);
        printf("<br />\n");
    }
    
    exit;
}

?>

<form enctype="multipart/form-data" method="POST">
    Choose a file (*.luac): <input name="f" type="file" />
    <br /><br />
    <input type="submit" style="padding: 10px; padding-left: 140px; padding-right: 140px;" value="Upload" />
</form>

<p>Forum thread: <a href="https://blast.hk/threads/37035/">https://blast.hk/threads/37035/</a></p>