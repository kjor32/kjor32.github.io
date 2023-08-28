<?php

function path_nofile($path)
{
	if (preg_match("/(.*)[\\\]/", $path, $match))
		return $match[1];
}

function path_noext($path)
{
	if (preg_match("/(.*)\./", $path, $match))
		return $match[1];
}

function basename_noext($path)
{
	if (preg_match("/.*[\\\](.*)\./", $path, $match))
		return $match[1];
}

function file_extension($path)
{
	return strtolower(end(explode(".", $path)));
}

function getdir($path, $level = 0)
{
	$a = explode("\\", str_replace("/", "\\", $path));
	for ($i = 0; $i < count($a) - $level; $i++)
		$result .= $a[$i] . "\\";
	return substr($result, 0, -1);
}

function err($string)
{
	// print(base64_decode("PHRpdGxlPkx1YUpJVCBTY2FubmVyPC90aXRsZT4KPHN0eWxlPgphOmxpbmsgewogICAgY29sb3I6IGJsdWU7CiAgICB0ZXh0LWRlY29yYXRpb246IG5vbmU7Cn0KCmE6dmlzaXRlZCB7CiAgICBjb2xvcjogYmx1ZTsKICAgIHRleHQtZGVjb3JhdGlvbjogbm9uZTsKfQoKYTpob3ZlciB7CiAgICBjb2xvcjogcmVkOwogICAgdGV4dC1kZWNvcmF0aW9uOiBub25lOwp9CgphOmFjdGl2ZSB7CiAgICBjb2xvcjogYmx1ZTsKICAgIHRleHQtZGVjb3JhdGlvbjogbm9uZTsKfQo8L3N0eWxlPgo="));
	die(sprintf("%s (<a href='?'>Go back</a>)<meta http-equiv=\"refresh\" content=\"5; URL='?'\" />", $string));
}

function uleb128_int($data, $pos, &$result, &$amount = NULL)
{
	$bytes = array();
	for ($i = $pos; $i < strlen($data); $i++)
	{
		$bytes[] = ord($data[$i]);
		if (ord($data[$i]) < 0x80)
			break;
	}
	$result = 0;
	for ($i = count($bytes) - 1; $i > 0; $i--)
		$result = ($result + $bytes[$i] - 1) * 0x80;
	$result += $bytes[0];
	$amount = count($bytes);
	return $result;
}

function int_uleb128($int, &$res, &$amount = NULL)
{
    $a = array($int);
    while ($int != 0)
    {
        $int = floor($int / 0x80);
        $a[] = $int;
    }
    $b = array($a[count($a) - 2]);
    for ($i = count($a) - 2; $i > 0; $i--)
        $b[] = $a[$i - 1] - (($a[$i] - 1) * 0x80);
    $res = "";
    foreach (array_reverse($b) as $k => $v)
        $res .= chr($v);
    $amount = count($b);
    return $res;
}

function ordint($int)
{
	$a = array(1 => 'st', 2 => 'nd', 3 => 'rd');
	if ($int < 1 || $int > 3)
		return $int . 'th';
	return $int . $a[$int];
}

function insert_bytes($data, $pos, $bytes)
{
	$a = explode(' ', $bytes);
	foreach ($a as $i => $val)
		$data[$i + $pos] = chr(sprintf("0x%s", $val));
	return $data;
}

function crypt_string($string, $sign = false)
{
	if (!strlen($string))
		return "(decrypt{222,129,222,27,254,65,46,89,64,19,67,129,149,199})";
	$rand = rand(0, 255);
	if ($sign)
		$result = sprintf("--[[%s]]", str_replace(array("[", "]"), "", $string));
	$result .= sprintf("(decrypt{%d", strlen($string) + $rand);
	for ($i = 0; $i < strlen($string); $i++)
	{
		$result .= sprintf(",%d,%d", ord($string[$i]) + $rand, $rand);
		$rand = rand(0, 255);
	}
	$rand = rand(10, 20);
	for ($i = 0; $i < $rand; $i++)
		$result .= sprintf(",%d", rand(0, 255));
	return $result . "})";
}

function skip_comment($string, $begin)
{
	$end = substr($string, $begin, 4) == "--[[" ? "]]" : PHP_EOL;
	return strpos($string, $end, $begin) + 2;
}

function getbyte($string, $pos, $format = NULL)
{
	if (strtolower($format) == 'hex')
		return sprintf("0x%X", ord($string[$pos]));
	if ($format)
		return sprintf($format[0] == '%' ? $format : '%'.$format, ord($string[$pos]));
	return ord($string[$pos]);
}

function bsearch($data, $byte, $start = 0)
{
	if ($start > strlen($data) || $start < 0)
		return -1;
	for ($i = $start; $i < strlen($data); $i++)
	{
		if ($data[$i] == $byte)
			return $i;
	}
	return -1;
}

function hexToString($data, $start = 0, $end = 0, $spaces = false)
{
	if ($start > strlen($data) || $end < 0 || $start < 0)
		return -1;
	if (!$end)
		$end = strlen($data);
	$result = "";
	if ($spaces)
	{
		$str = false;
		for ($i = $start; $i < $end; $i++)
		{
			$byte = ord($data[$i]);
			if (is_printable($byte))
			{
				if (!$str)
				{
					$result .= chr(0x22);
					$str = true;
				}
				$result .= sprintf("%s%s", $byte == 0x22 ? "\\" : "", $data[$i]);
			} else
			{
				if ($str)
				{
					$result .= chr(0x22);
					$result .= " ";
					$str = false;
				}
				$result .= sprintf("%02X ", $byte);
			}
		}
		return substr($result, 0, -1);
	}
	for ($i = $start; $i < $end; $i++)
		$result .= sprintf("%02X", ord($data[$i]));
	return $result;
}

function patternSearch($data, $pattern, $len, $start = 0)
{
	if ($start > strlen($data) || $start < 0)
		return -1;
	$pattern = str_replace(' ', '', $pattern);
	$pattern = str_replace('?', '\w', $pattern);
	for ($i = $start; $i < strlen($data); $i++)
	{
		$string = hexToString($data, $i, $i + $len);
		if (preg_match('/' . $pattern . '/iU', $string))
			return $i;
	}
	return -1;
}

function is_printable($byte)
{
	$allowed_chars_table = array(0xb8, 0x95);
	if (($byte >= 0x20 && $byte <= 0x7e) || ($byte >= 0xc0 && $byte <= 0xff) || in_array($byte, $allowed_chars_table))
		return true;
	return false;
}