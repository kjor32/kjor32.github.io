<?php

require "config.php";
require "functions.php";
require "luajit.php";

printf("<h3>LuaJIT Script Converter (2.0 to 2.1)</h3>\n");

if (isset($_FILES["f"]) && $_FILES["f"]["size"] > 0)
{
    if ($_FILES["f"]["size"] > $MAX_UPLOAD_SIZE)
        err("The file you are trying to upload exceeds the 32 MB uploading limit");
    $s = new Lua($_FILES["f"]["tmp_name"]);
    if (!$s->isCompiled())
        err("The file is not a LuaJIT compiled script");
    if ($s->version() != "2.0")
        err("This is only for LuaJIT 2.0 compiled scripts");
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

    if (isset($_GET["del"]))
    {
        @unlink($f);
        @unlink($f . "-copy");
        unset($_SESSION["f"]);
        err("File deleted");
    }

    $s = new Lua($f);
    if (!$protos = $s->protos())
    {
        @unlink($f);
        @unlink($f . "-copy");
        unset($_SESSION["f"]);
        err("An error has occurred while scanning the script");
    }
    if ($s->version() == "2.0")
    {
        printf("File: %s<br />\n", basename($_SESSION["f"]["name"]));
        printf("LuaJIT version: %s<br />\n", $s->version());
        $s->data[3] = "\x02";
        foreach ($protos as $k => $v)
        {
            for ($i = 0; $i < $v["numbc"]; $i++)
            {
                $pins = $v["ins"] + ($i * 4);
                $ins = ord($s->data[$pins]);
                if ($ins > 60)
                    $s->data[$pins] = chr($ins + 4);
                else if ($ins > 56)
                    $s->data[$pins] = chr($ins + 3);
                else if ($ins > 15)
                    $s->data[$pins] = chr($ins + 2);
            }
        }
        file_put_contents($s->path, $s->data);
        printf("Script converted! <a href='conv?dl'>Download v2.1</a> || <a href='conv?del'>Delete</a>");
        if (!$private)
            printf(" || <a href='/'>Go back</a>");
    } else
    {
        printf("File: %s", basename($_SESSION["f"]["name"]));
        printf(" [<a href='conv?dl'>download</a>] [<a href='conv?del'>delete</a>]");
        if (!$private)
            printf(" [<a href='/'>go back</a>]");
        printf("<br />\n");
        printf("LuaJIT version: %s<br />\n", $s->version());
    }
    
    exit;
}

?>

<form enctype="multipart/form-data" method="POST">
    Choose a file (*.luac): <input name="f" type="file" />
    <br /><br />
    <input type="submit" style="padding: 10px; padding-left: 125px; padding-right: 125px;" value="CONVERT" />
</form>

<p>Forum thread: <a href="https://blast.hk/threads/35380/">https://blast.hk/threads/35380/</a></p>