<?php
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
@ignore_user_abort(0);
@set_time_limit(0);
$cookie = "STOKEN=6f6d50bc4a5cf043f492197f592580d61c257476d312eb1a9cfd637e0e5a049b; BDUSS=JYcjROdVZLfnJ0dXFUMkQyZEZqU35jaWp6NHZLYWtPLTM4cDM2SVcxUzlCcUphQVFBQUFBJCQAAAAAAAAAAAEAAAAx90egubfO0bKp18rUtNb6ytYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAL15elq9eXpaMX";
$UA     = "netdisk;6.0.0.12;PC;PC-Windows;10.0.15063;WindowsBaiduYunGuanJia";

if(isset($_GET['code'])){
    $decode = encrypt(trim($_GET['code']),'D','Download-pan');
    $urlArgs = parse_url($decode);
    $host = $urlArgs['host'];
    $requestUri = $urlArgs['path'];
    if (strpos($host,'baidupcs')===false){
        header("HTTP/1.1 403 Forbidden");
        die("403");
    }
    if (isset($urlArgs['query'])) {
        $requestUri .= '?' . $urlArgs['query'];
    }

    $protocol = ($urlArgs['scheme'] == 'http') ? 'tcp' : 'ssl';
    $port = $urlArgs['port'];

    if (empty($port)) {
        $port = ($protocol == 'tcp') ? 80 : 443;
    }

    $header = "{$_SERVER['REQUEST_METHOD']} {$requestUri} HTTP/1.1\r\nHost: {$host}\r\n";

    unset($_SERVER['HTTP_HOST']);
    $_SERVER['HTTP_CONNECTION'] = 'close';

    if ($_SERVER['CONTENT_TYPE']) {
        $_SERVER['HTTP_CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
    }
    $_SERVER['HTTP_REFERER'] = '';
    $_SERVER['HTTP_USER_AGENT'] = $UA;
    $_SERVER['HTTP_COOKIE'] = $cookie;
    foreach ($_SERVER as $x => $v) {
        if (substr($x, 0, 5) !== 'HTTP_') {
            continue;
        }
        $x = strtr(ucwords(strtr(strtolower(substr($x, 5)), '_', ' ')), ' ', '-');
        $header .= "{$x}: {$v}\r\n";
    }

    $header .= "\r\n";

    $remote = "{$protocol}://{$host}:{$port}";

    $opts = array(
        'http'=>array(
            'method'=>"GET",
            'header'=>"Accept-language: zh\r\n" .
                "Cookie: $cookie\r\n".
                "User-Agent:$UA \r\n"
        )
    );
    $context = stream_context_create($opts);
    stream_context_set_option($context, 'ssl', 'verify_host', false);

    $p = stream_socket_client($remote, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $context);

    if (!$p) {
        exit;
    }

    fwrite($p, $header);

    $pp = fopen('php://input', 'r');

    while ($pp && !feof($pp)) {
        fwrite($p, fread($pp, 1024));
    }

    fclose($pp);

    $header = '';

    $x = 0;
    $len = false;
    $off = 0;

    while (!feof($p)) {
        if ($x == 0) {
            $header .= fread($p, 1024);

            if (($i = strpos($header, "\r\n\r\n")) !== false) {
                $x = 1;
                $n = substr($header, $i + 4);
                $header = substr($header, 0, $i);
                $header = explode("\r\n", $header);
                foreach ($header as $m) {
                    if (preg_match('!^\\s*content-length\\s*:!is', $m)) {
                        $len = trim(substr($m, 15));
                    }
                    header($m);
                }
                $off = strlen($n);
                echo $n;
                flush();
            }
        } else {
            if ($len !== false && $off >= $len) {
                break;
            }
            $n = fread($p, 1024);
            $off += strlen($n);
            echo $n;
            flush();
        }
    }

    fclose($p);
    return;
}
function encrypt($string,$operation,$key='')
{
    $src = array("/","+","=");
    $dist = array("_a","_b","_c");
    if($operation=='D'){$string = str_replace($dist,$src,$string);}
    $key=md5($key);
    $key_length=strlen($key);
    $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
    $string_length=strlen($string);
    $rndkey=$box=array();
    $result='';
    for($i=0;$i<=255;$i++)
    {
        $rndkey[$i]=ord($key[$i%$key_length]);
        $box[$i]=$i;
    }
    for($j=$i=0;$i<256;$i++)
    {
        $j=($j+$box[$i]+$rndkey[$i])%256;
        $tmp=$box[$i];
        $box[$i]=$box[$j];
        $box[$j]=$tmp;
    }
    for($a=$j=$i=0;$i<$string_length;$i++)
    {
        $a=($a+1)%256;
        $j=($j+$box[$a])%256;
        $tmp=$box[$a];
        $box[$a]=$box[$j];
        $box[$j]=$tmp;
        $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
    }
    if($operation=='D')
    {
        if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8)) // www.jbxue.com
        {
            return substr($result,8);
        }
        else
        {
            return false;
        }
    }
    else
    {
        $rdate = str_replace('=','',base64_encode($result));
        $rdate = str_replace($src,$dist,$rdate);
        return $rdate;
    }
}