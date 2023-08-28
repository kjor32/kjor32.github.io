<?php

require_once "config.php";

$confirmed = false;

if (USERNAME && PASSWORD)
{
    $data = $sql->query("select * from local_admins where username = ?", USERNAME)->fetchArray();
    if (!$data["username"] || !$data["password"]) {
        $errmsg = "Incorrect username";
        session_destroy();
    } elseif (!password_verify(PASSWORD, $data["password"])) {
        $errmsg = "Incorrect password";
        session_destroy();
    } else {
        if (isset($_GET["logout"]))
        {
            if ($_GET["logout"] == $data["hash"]) {
                session_destroy();
                exit("You have logged out (<a href='?'>Go back</a>)");
            } else {
                exit(sprintf("Are you sure you want to log out of your account? <a href='?logout=%s'>LOG OUT</a> or <a href='?'>Go back</a>", $data["hash"]));
            }
        }
        $confirmed = true;
        $_SESSION["username"] = USERNAME;
        $_SESSION["password"] = PASSWORD;
        $data["hash"] = rand(111111111, 999999999);
        $sql->query("update local_admins set hash = ?, ip = ?, time = ? where id = ?", $data["hash"], IP_ADDRESS, time(), $data["id"]);
    }
}

if (!$confirmed)
{
    ?>

<form method="post">
    <table>
        <tr>
            <td colspan="2" style="color: #dd0000">
                <?=$errmsg?>
            </td>
        </tr>
        <tr>
            <td>Username:</td>
            <td><input type="text" name="username" value="<?=USERNAME?>" /></td>
        </tr>
        <tr>
            <td>Password:</td>
            <td><input type="password" name="password" /></td>
        </tr>
        <tr>
            <td colspan="2">
                <button type="submit" style="width: 100%; height: 30px">Log in</button>
            </td>
        </tr>
    </table>
</form>

    <?php
    exit;
}

?>

<h3>You are confirmed as being authorized as <?=$data["username"]?></h3>

<p><a href="?logs">Logs</a> | <a href="?bans">Bans</a> | <a href="?admins">Admins</a> | <a href="?invite">Invite</a> | <a href="?logout=<?=$data['hash']?>">Log out</a></p>

<?php

if (isset($_GET["logs"]) || !count($_GET))
{
    $unique = [];
    $logs = $sql->query("select * from local_logs")->fetchAll();
    
    foreach ($logs as $log) {
        $unique[$log["ip"]]++;
    }
    
    printf("<h4>Rows: %d<br />Unique IPs: %d<br />The last 100 logs:</h4>\n", count($logs), count($unique));
    $logs = $sql->query("select * from local_logs order by id desc limit 100")->fetchAll();
    
    foreach ($logs as $log)
    {
        $datetime = date("d.m.Y H:i:s", $log["time"]);
        printf("<p>%d | %s | %s | %s%s</p>\n", $log["id"], $log["ip"], $datetime, $log["domain"], $log["uri"]);
    }
}

elseif (isset($_GET["bans"]))
{
    $bans = $sql->query("select * from local_bans order by id desc")->fetchAll();
    if (!count($bans))
        echo "<h4>No bans</h4>";
    else
    {
        foreach ($bans as $ban)
        {
            $datetime = date("d.m.Y H:i:s", $ban["timestamp"]);
            printf("<p>%d | %s | %s | %s</p>\n", $ban["id"], $ban["ip"], $datetime, $ban["comment"]);
        }
    }
}

elseif (isset($_GET["admins"]))
{
    $admins = $sql->query("select * from local_admins order by id desc")->fetchAll();
    
    foreach ($admins as $a)
    {
        $time = date("d.m.Y H:i:s", $a["time"]);
        $regtime = date("d.m.Y H:i:s", $a["regtime"]);
        printf("<p>%d | %s | L-IP: %s | R-IP: %s | recent activity: %s | registration date: %s</p>\n", $a["id"], $a["username"], $a["ip"], $a["regip"], $time, $regtime);
    }
}

elseif (isset($_GET["invite"]))
{
    if ($_GET["invite"] == "add")
    {
        $invite_id = rand(111111111, 999999999) . rand(111111111, 999999999) . rand(111111111, 999999999);
        $sql->query("insert into local_invites (invite, creator) values (?, ?)", $invite_id, $data["id"]);
        printf("<p>invitation code: %s</p>", $invite_id);
    }
    
    elseif (is_numeric($_GET["invite"]))
    {
        $sql->query("delete from local_invites where invite = ?", $_GET["invite"]);
        printf("<p>an invitation has been deleted</p>");
    }
    
    $invites = $sql->query("select * from local_invites order by id desc")->fetchAll();
    foreach ($invites as $invite) {
        printf("<p>%d | %s | %s [<a href='?invite=%s'>delete</a>]</p>", $invite["id"], $invite["invite"], $invite["creator"], $invite["invite"]);
    }
}

?>