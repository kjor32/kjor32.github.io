<?php

define("LUA_FULL_PATH", 0);
define("LUA_PATH_NOEXT", 1);
define("LUA_PATH_NOFILE", 2);
define("LUA_PATH_FILENAME", 3);
define("LUA_PATH_FILE_BASENAME", 4);
define("LUA_PATH_EXTENSION", 5);

class Lua
{
    public $path = NULL;
    public $data = NULL;

    public function __construct($path)
    {
        if (!file_exists($path))
            return false;
        $this->path = $path;
        $this->data = file_get_contents($path);
        return true;
    }

    public function isCompiled()
    {
        return substr($this->data, 0, 3) == "\x1B\x4C\x4A";
    }

    public function version()
    {
        if (!$this->isCompiled())
            return false;
        return ($this->data[3] == "\x02" ? "2.1" : ($this->data[3] == "\x01" ? "2.0" : "unknown"));
    }

    public function protos()
    {
        if (!$this->isCompiled())
            return false;
        $protos = array();
        for ($i = 5; $i < strlen($this->data); $i++)
        {
            if ($this->data[$i] == "\x00")
                break;
            $protos[] = $this->pinfo($i, $end);
            $i += $end;
        }
        return $protos;
    }

    public function pinfo($pos, &$end = NULL)
    {
        if (!$this->isCompiled())
            return false;
        uleb128_int($this->data, $pos, $size, $bsize);
        $proto = array(
            "pos" => $pos,
            "size" => $size,
            "fullsize" => $size + $bsize
        );
        $end = $proto["fullsize"] - 1;
        $proto["flags"] = ord($this->data[$pos + $bsize]);
        $proto["params"] = ord($this->data[$pos + $bsize + 1]);
        $proto["framesize"] = ord($this->data[$pos + $bsize + 2]);
        $proto["globals"] = ord($this->data[$pos + $bsize + 3]);
        $pos += ($bsize + 4);
        uleb128_int($this->data, $pos, $proto["numkgc"], $bsize);
        $pos += $bsize;
        uleb128_int($this->data, $pos, $proto["numkn"], $bsize);
        $pos += $bsize;
        uleb128_int($this->data, $pos, $proto["numbc"], $bsize);
        $proto["ins"] = $pos + $bsize;
        return $proto;
    }

    public function scan()
    {
        if (!$this->isCompiled())
            return false;
        $scan = array(
            "version" => $this->version(),
            "flag" => ord($this->data[4]),
            "protos" => array()
        );
        $check = true;
        for ($i = 5; $i < strlen($this->data); $i++)
        {
            $byte = ord($this->data[$i]);
            if (!$check)
            {
                if ($byte === 0 || ord($this->data[$i - 1]) < 0x80)
                {
                    $check = true;
                    $i += ($size - 1);
                }
                else if ($byte < 0x80)
                {
                    $check = true;
                    /*if (ord($this->data[$i + 1]) === 2)
                        $i += ($size - 1);
                    else*/
                        $i += $size;
                }
                continue;
            }
            if ($byte === 0)
                break;
            $size = $this->prototype_size($i);
            $scan["protos"][] = array("pos" => $i, "size" => $size);
            $check = false;
        }
        return $scan;
    }

    public function prototype_size($pos)
    {
        if (!$this->isCompiled())
            return false;
        $bytes = array();
        for ($i = $pos; $i < strlen($this->data); $i++)
        {
            $byte = ord($this->data[$i]);
            $bytes[] = $byte;
            if ($byte < 0x80)
                break;
        }
        if (count($bytes) == 1)
            return $bytes[0];
        if (count($bytes) > 5)
        {
            printf("\nERROR: you're doing something wrong... position: %X\n", $pos);
            return false;
        }
        $result = 0;
        for ($i = count($bytes) - 1; $i > 0; $i--)
            $result = ($result + $bytes[$i] - 1) * 0x80;
        $result += $bytes[0];
        return $result;
    }

    public function getpath($type = LUA_FULL_PATH)
    {
        switch ($type)
        {
            case LUA_PATH_NOEXT:
                $regex = "/(.*)\./";
                break;
            case LUA_PATH_NOFILE:
                $regex = "/(.*)[\\\]/";
                break;
            case LUA_PATH_FILENAME:
                $regex = "/.*[\\\](.*)/";
                break;
            case LUA_PATH_FILE_BASENAME:
                $regex = "/.*[\\\](.*)\./";
                break;
            case LUA_PATH_EXTENSION:
                return strtolower(end(explode(".", $this->path)));
            default:
                $regex = "/(.*)/";
        }
        if ($type != LUA_FULL_PATH)
        {
            if (preg_match($regex, $this->path, $match))
                return $match[1];
        }
        return $this->path;
    }

    public function compile($version = "2.1", $saveas = null)
    {
        $luajit_path = sprintf("%s\\luajit %s", getdir(__DIR__, 2), $version);
        if (!file_exists($luajit_path) || !file_exists($luajit_path . "\\luajit.exe"))
            return -1;
        if (!$saveas)
            $saveas = sprintf("%s-compiled.luac", $this->getpath(LUA_PATH_NOEXT));
        system(sprintf("cd \"%s\" && luajit.exe -b \"%s\" \"%s\"", $luajit_path, $this->path, $saveas));
        if (file_exists($saveas))
            return $saveas;
    }

    public function patch()
    {
        if (!$this->isCompiled() || $this->version() != "2.1")
            return false;
        $a = explode(" ", "1B 4C 4A 02 02 3B 00 00 00 00 00 00 0D 61 39 56 18 55 61 89 42 42 16 46 17 54 70 10 58 60 10 10 01 75 10 F0 C0 00 01 00 02 00 C0 00 D0 00 01 00 C4 00 01 00 02 00 01 00 00 02 00 00");
        foreach ($a as $i => $val)
            $this->data[$i] = chr(sprintf("0x%s", $val));
        return file_put_contents($this->path, $this->data);
    }

    public function pack()
    {
        if (!$this->isCompiled())
            return false;
        $php_path = sprintf("%s\\bin\\php5\\php.exe", getdir(__DIR__, 2));
        $packer_path = sprintf("%s\\bin\\packer.php", getdir(__DIR__, 2));
        if (!file_exists($php_path) || !file_exists($packer_path))
            return false;
        $packed_path = sprintf("%s-packed.luac", $this->getpath(LUA_PATH_NOEXT));
        system(sprintf("\"%s\" \"%s\" \"%s\"", $php_path, $packer_path, $this->path));
        if (file_exists($packed_path))
        {
            @unlink($this->path);
            @rename($packed_path, str_replace('-compiled', '', $this->path));
            return true;
        }
        return false;
    }
}