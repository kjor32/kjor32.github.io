<?php

/*

    MAIN

*/

include "config.php";
include "functions.php";

printf("<h3>Склейщик Lua скриптов</h3>\n");

if (file_exists($_SESSION["j"]))
{
    if (isset($_GET["dl"]))
    {
        if (ob_get_level())
        	ob_end_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=joined.luac');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($_SESSION["j"]));
        readfile($_SESSION["j"]);
        exit;
    }
    if (isset($_GET["del"]))
    {
        @unlink($_SESSION["j"]);
        unset($_SESSION["j"]);
        die("Все файлы удалены (<a href='joiner'>Вернуться назад</a>)");
    }
    die("Склеенный файл: <a href='joiner?dl'>Скачать</a> | <a href='joiner?del'>Удалить с сервера</a>");
}

if (isset($_FILES["f"]) && $_FILES["f"]["size"] > 0)
{
    if ($_FILES["f"]["size"] > $MAX_UPLOAD_SIZE)
        err("The file you are trying to upload exceeds the 32 MB uploading limit");
    $uploaded_file = array(
        "name" => basename($_FILES["f"]["name"]),
        "path" => "files/" . md5(IP_ADDRESS . rand(111111111, 999999999))
    );
    $_SESSION["f"][] = $uploaded_file;
    move_uploaded_file($_FILES["f"]["tmp_name"], $uploaded_file["path"]);
}

if ($_GET["action"] == "discard" && is_array($_SESSION["f"]) && count($_SESSION["f"]) > 0)
{
    foreach ($_SESSION["f"] as $file)
        @unlink($file["path"]);
    unset($_SESSION["f"]);
    die("<p>Все файлы удалены (<a href='joiner'>Вернуться назад</a>)</p>\n");
}

if (is_numeric($_GET["del"]))
{
    $file_id = (int)$_GET["del"];
    if (is_array($_SESSION["f"][$file_id]))
    {
        @unlink($_SESSION["f"][$file_id]["path"]);
        unset($_SESSION["f"][$file_id]);
    }
}

if ($_GET["action"] == "swap")
{
    $one = (int)$_GET["n"];
    $two = (int)$_GET["m"];
    if (is_array($_SESSION["f"][$one]) && is_array($_SESSION["f"][$two]))
    {
        $tmp = $_SESSION["f"][$one];
        $_SESSION["f"][$one] = $_SESSION["f"][$two];
        $_SESSION["f"][$two] = $tmp;
    }
}

if (is_array($_SESSION["f"]) && count($_SESSION["f"]) > 0)
{
    printf("<h4>Загруженные файлы:</h4>\n");
    $n = 0;
    foreach ($_SESSION["f"] as $i => $file)
    {
        $f = file_get_contents($file["path"]);
        printf("%d) %s [размер: %d байт]", ++$n, $file["name"], strlen($f));
        file_exists($file["path"])
            ? printf(" [<a href='joiner?del=%d'>удалить</a>]", $i)
            : printf(" [удален]");
        if ($n != 1)
            printf(" [<a href='joiner?action=swap&n=%d&m=%d'>вверх</a>]", $i, $i - 1);
        if (end($_SESSION["f"]) != $file)
            printf(" [<a href='joiner?action=swap&n=%d&m=%d'>вниз</a>]", $i, $i + 1);
        substr($f, 0, 3) != "\x1B\x4C\x4A"
            ? printf(" - осторожно, файл не скомпилирован")
            : printf(" - %s", $f[4] == "\x02" ? "luajit 2.1" : ($f[4] != "\x01" ? "luajit 2.0" : "unknown luajit version"));
        printf("<br />\n");
    }
    printf("<p><a href='joiner?action=join'>Склеить файлы</a> | <a href='joiner?action=discard'>Отменить действие и удалить файлы</a></p>\n");
}

if ($_GET["action"] == "join" && is_array($_SESSION["f"]) && count($_SESSION["f"]) > 0)
{
    $files = count($_SESSION["f"]);

    if ($files > 100 || $files < 1)
        die("Вы можете склеить не более 100 файлов за раз");

    $type = 1; // temporarily
    $stub = file_get_contents(sprintf("type_%d.luac", $type));

    $proto_pos = array(0x348, 0x9B, 5);
    $proto_size = array(0x63F, 0xAA, 0xC8);
    $table_size = array(0x982, 0x144, 0xCC);
    $table_begin = array(0x985, 0x147, 0xCF);
    $ending = array("\x02\xFE\x03\x00", "\x00", "\x00");

    $stub[$table_size[$type - 1]] = chr($files + 1);
    $res = substr($stub, 0, $table_begin[$type - 1]);

    foreach ($_SESSION["f"] as $filedata)
    {
        $file = file_get_contents($filedata["path"]);

        for ($i = 0; $i < strlen($file); $i++)
            $file[$i] = chr(0xFF - ord($file[$i]));

        int_uleb128(strlen($file) + 5, $file_size, $size);
        $proto_size[$type - 1] += (strlen($file) + $size);

        $res .= $file_size;
        $res .= $file;
    }

    $stub = $res . $ending[$type - 1];

    int_uleb128($proto_size[$type - 1] - 1, $new_size);
    $res = substr($stub, 0, $proto_pos[$type - 1]);
    $res .= $new_size;
    $res .= substr($stub, $proto_pos[$type - 1] + 2);
    $stub = $res;

    $path = "files/joined-" . md5(rand(111111111, 999999999)) . ".luac";
    file_put_contents($path, $stub);

    foreach ($_SESSION["f"] as $file)
        @unlink($file["path"]);
    unset($_SESSION["f"]);

    $_SESSION["j"] = $path;
    die("Файлы склеены: <a href='joiner?dl'>Скачать</a> | <a href='joiner?del'>Удалить с сервера</a>");
}

$files = count($_SESSION["f"]);
$num = array("первый", "второй", "третий", "четвертый", "пятый", "шестой", "седьмой", "восьмой", "девятый", "десятый");
$pos = $files >= 10 ? sprintf("%d-й", $files + 1) : $num[$files];

?>

<form enctype="multipart/form-data" method="POST" action="joiner">
    Выберите файл (lua или luac): <input name="f" type="file" />
    <br /><br />
    <input type="submit" style="padding: 10px; padding-left: 120px; padding-right: 120px;" value="Загрузить <?=$pos?> файл" />
</form>

<p>Forum post: <a href="https://blast.hk/threads/38714/post-376714">https://blast.hk/threads/38714/post-376714</a></p>