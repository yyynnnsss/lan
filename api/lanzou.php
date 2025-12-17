<?php
/*
 * @package lanzouyunapi
 * @author wzdc
 * @repair LsPro
 * @version 1.6.1
 * @Date 2025-10-15
 * @link https://github.com/wzdc/lanzouyunapi
 */


header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, HEAD');
header("Access-Control-Allow-Headers: *");
header("Access-Control-Max-Age: 2592000");
header('Content-Type: application/json; charset=utf-8');

//配置 （开启缓存需要安装apcu扩展）
$config = array(
    "cache"        => false,  //文件链接缓存
    "cacheexpired" => 2000,  //文件链接缓存时间
    "foldercache"  => false,  //缓存文件夹参数
    "auto-switch"  => true,  //自动切换获取方式
    "mode"        => "pc",  //请求方式 (pc/mobile)
    "experimental" => false, // 实验性功能
);

error_reporting(0);

if(!isset($_REQUEST["url"]) || !$_REQUEST["url"]) exit(response(-4,"缺少参数",null));

$id = preg_match("/^(?:https?:\/\/)?[aA-zZ0-9.-]+\.com\/(?:tp\/)?(.+)/",$_REQUEST["url"],$id) ? $id[1] : $_REQUEST["url"];
if(!$id) exit(response(-4,"参数错误",null));

$pw = $_REQUEST["pw"] ?? $_REQUEST["pwd"] ?? ""; //密码，支持pw和pwd
$type = $_REQUEST["type"] ?? ""; 
$page = (isset($_REQUEST["page"]) && (int)$_REQUEST["page"] > 1) ? (int)$_REQUEST["page"] : 1;
$fid = preg_match("/^[^?]+/",$id,$fid) ? $fid[0] : null;

$ch = curl_init();
$mobileua = ["User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36"];
$desktopua = ["User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36"];

if($config["cache"] && $data3=apcu_fetch("file".$fid)) {
    header("X-APCu-Cache: HIT");
    response(200,"成功",removeTempUrl($data3));
} else if($config["foldercache"] && $data3=apcu_fetch("folder".$fid)) {
    $parameter = $data3[1];
    $parameter["pg"] = $page;
    $t = $parameter["t"] - $data3[2] - time() + $page;
    if($page!=1 && $t>=0) sleep($t);
    if($pw) $parameter["pwd"] = $pw;
    f($data3[0],$parameter);
} else if($config["mode"] == "mobile") {
    mobile();    
} else {
    pc();
}

function removeTempUrl($data) {
    if (is_array($data)) {
        unset($data['temp_url']);
        if (isset($data['list']) && is_array($data['list'])) {
            foreach ($data['list'] as &$item) {
                if (is_array($item)) {
                    unset($item['temp_url']);
                }
            }
        }
    }
    return $data;
}

function mobile() { 
    global $id,$pw,$ch,$mobileua;
    $data = preg_replace('/<!--.*?-->/s', '', request("https://www.lanzouf.com/$id","GET",null,$mobileua,"data",$ch));
    if(!$data) exit(response(-3,"获取失败",null)); 
    
    $js = preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $data, $js) ? trim(implode("\n", $js[1])) : "";
    if(strpos($js,"/filemoreajax.php")) exit(folder($data,$js));
    
    $data2 = null;
    $datar = $data;
    
    if(preg_match("/(?<=')\?.+(?=')/",$js,$url)) {
        $url = $url[0];
    } else if (
        (
            (preg_match('/https?:\/\/waf\.woozooo\.com\/tp\/.+\.js/',$data,$jstpurl) &&
             preg_match('/(?<=tp\/)[\w?&=]+/', request($jstpurl[0],"GET",null,$mobileua,"data"), $id2)) || 
            preg_match('/(?<=tp\/)[\w?&=]+/', $data, $id2) || 
            (($redirecturl = request("https://www.lanzouf.com/$id","GET",null,["User-Agent: MicroMessenger"],"info",$ch)["redirect_url"]) &&
             preg_match('/(?<=i\.com\/)[\w?&=]+/',request($redirecturl, "GET", null, $mobileua, "info")["redirect_url"],$id2))
        ) &&
        $data2 = preg_replace('/<!--.*?-->/s', '', request("https://www.lanzouf.com/tp/".$id2[0], "GET", null, $mobileua, "data", $ch))
    ) {
        $datar = $data2;
        $js = preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $data2, $js) ? trim(implode("\n", $js[1])) : null;
        $url = preg_match("/(?<=')\?.+(?=')/",$js,$url) ? $url[0] : null;
    }
    
    $error = preg_match("/<\/div><\/div>(.+)<\/div>/",$data,$error) ? $error[1] : "获取失败";
    if(!$js) exit(response(-2,$error,null));
    
    $info = getFileInfo($data, $data2, 'mobile');
                  
    if($url) {
        $fileid = preg_match('/(?<=\?f=)\d+/',$datar,$fileid) ? (int)$fileid[0] : null;
        $dom = preg_match("/(?<=')https?:\/\/.+(?=')/",$datar,$dom) ? $dom[0] : null;
        $info = array("fid" => $fileid) + $info;
        $info["temp_url"] = $dom.$url;
        $info = getAdditionalFileInfo($info, $data, $data2, 'mobile');
        getPermanentUrl($info);
    } else {
        geturl($js,$info,$error,$pw);
    }
}

function pc() { 
    global $id,$pw,$ch,$desktopua;
    $data = preg_replace('/<!--.*?-->/s', '', request("https://www.lanzouf.com/$id","GET",null,$desktopua,"data",$ch));
    if(!$data) exit(response(-3,"获取失败",null));
    
    $js = preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $data, $js) ? trim(implode("\n", $js[1])) : "";
    $error = preg_match("/<\/div><\/div>(.+)<\/div>/",$data,$error) ? $error[1] : "获取失败";
    
    if(strpos($js,"/filemoreajax.php")) exit(folder($data,$js));
    
    if(preg_match('/<iframe\b[^>]* src="(.+?)"/',$data,$src)) {
        $data2 = request("https://www.lanzouf.com".$src[1],"GET",null,$desktopua,"data",$ch);
        $js = preg_match('/https?:\/\/waf\.woozooo\.com\/pc\/.+\.js/',$data2,$jsurl) ? request($jsurl[0],"GET",null,$desktopua,"data") : $data2;
    }
    
    if(!$js) exit(response(-2,$error,null));
    
    $info = getFileInfo($data, null, 'pc');
    geturl($js,$info,$error,$pw);
}

function getPermanentUrl($info) {
    global $config,$fid;
    
    if(!isset($info["temp_url"]) || empty($info["temp_url"])) {
        response(1,"获取临时链接失败",removeTempUrl($info));
        return;
    }
    
    $tempUrl = $info["temp_url"];
    $redirectUrl = getRedirectUrl($tempUrl);
    
    if($redirectUrl) {
        $permanentUrl = "https://developer.lanzoug.com/file/" . basename($redirectUrl);
        
        if(validatePermanentUrl($permanentUrl)) {
            $info["url"] = $permanentUrl;
            $info["url_type"] = "permanent";
        } else {
            $info["url"] = $redirectUrl;
            $info["url_type"] = "redirect";
        }
    } else {
        $info["url"] = $tempUrl;
        $info["url_type"] = "temporary";
    }
    
    if(preg_match('/https?:\/\/([^\/]+)/', $info["url"], $server)) {
        $info["server"] = $server[1];
    }
    
    if(preg_match("/&e=(.+?)&/",$info["url"],$endtime) || preg_match("~^(?:[^/]*\/){4}([^/]*)~",$info["url"],$endtime)) {
        if(!preg_match("/\d{10}/",$endtime[1]) && ctype_xdigit($endtime[1])) {
            $endtime[1] = hexdec($endtime[1]);
        }
        $info["expire_time"] = date('Y-m-d H:i:s', $endtime[1]);
        $info["expire_timestamp"] = $endtime[1];
        $t = $endtime[1] - time();
        if($t > 0) {
            $config["cacheexpired"] = $t;
            $info["expire_in"] = $t;
        }
    }
    
    if($config["cache"]) {
        apcu_store("file$fid",$info,$config["cacheexpired"]);
        header("X-APCu-Cache: MISS");
    }
    
    response(200,"成功",removeTempUrl($info));
}

function getRedirectUrl($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false, //不自动重定向
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
        CURLOPT_REFERER => 'https://www.lanzouf.com/',
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if($httpCode == 302 || $httpCode == 301) {
        if(preg_match('/Location:\s*(.*?)\s*/i', $response, $matches)) {
            return trim($matches[1]);
        }
    }
    
    return null;
}

function validatePermanentUrl($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200 || $httpCode == 302 || $httpCode == 301;
}

function getFileInfo($data, $data2 = null, $mode = 'pc') {
    $info = [];
    
    if($mode == 'mobile') {
        $info["name"] = $data2 && (preg_match('/<title>(.+)<\/title>/',$data2,$filename) 
                      || preg_match('/<div class="md">(.+) <span class="mtt">/',$data2,$filename)) 
                      || preg_match('/<div class="(?:md|appname)">(.*?) ?</',$data,$filename) 
                      ? htmlspecialchars_decode($filename[1]) : null;
                      
        $info["size"] = preg_match('/>下载 *\( *(.+) \)<\/a>/', $data,  $filesize)
                     || ($data2 && preg_match('/mtt">\( (.+) \)/', $data2, $filesize))
                     || preg_match('/(?:文件)?大小：(.*?)(?:\||$)/',$data,$filesize)
                     ? $filesize[1] : null;
                     
        $info["user"] = preg_match('/(?<=分享者:<\/span>).+(?= )/U',$data,$username) 
                     || preg_match('/(?<=<div class="user-name">).+(?=<)/U',$data,$username) 
                     || $data2 && preg_match('/(?<=发布者:<\/span>).+(?= )/U',$data2,$username)
                     ? $username[0] : null;
        
        $info["time"] = preg_match('/(?<=<span class="mt2"><\/span>).*?(?=<span class="mt2">)/', $data, $filetime) 
                     || preg_match('/(?<=<span class="appinfotime">).*?(?=<)/', $data, $filetime)
                     || $data2 && preg_match('/(?<=\<span class="mt2">时间:<\/span>).*?(?=\<span class="mt2">)/', $data2, $filetime)
                     ? trim($filetime[0]) : null;
        
        $info["desc"] = preg_match('/<div class="appdes">([\s\S]+?)<\/div>/', $data, $filedesc)
                      || $data2 && preg_match('/<div class="mdo">([\s\S]+?)<\/div>/', $data2, $filedesc) && !strpos($filedesc[1], "<span>")
                      ? htmlspecialchars_decode(trim(strip_tags(str_replace("<br /> ","\n",$filedesc[1])))) : "";
        
    } else {
        $info["name"] = preg_match('/<div class="n_box_3fn" [^>]+>(.*?)<\/div>/',$data,$filename)
                      || preg_match('/<div style="font[^>]+>(.*?)<\/div>/',$data,$filename)
                      ? htmlspecialchars_decode($filename[1]) : null;
                      
        $info["size"] = preg_match('/<div class="n_filesize">大小：(.+?)<\/div>/',$data,$filesize)
                     || preg_match('/文件大小：<\/span>(.+?)</',$data,$filesize)
                     ? $filesize[1] : null;
                     
        $info["user"] = preg_match('/<span class="user-name">(.+?)<\/span>/',$data,$username)
                     || preg_match('/<font>(.+?)<\/font>/',$data,$username)
                     ? $username[1] : null;
        
        $info["time"] = preg_match('/<span class="n_file_infos">(.+?)<\/span> <span class="n_file_infos">/',$data,$filetime)
                     || preg_match('/<span class="p7">上传时间：<\/span>(.*?)<br>/',$data,$filetime)
                     ? $filetime[1] : null;
        
        $info["desc"] = preg_match('/(?<=<div class="n_box_des">).+?(?=<\/div>)/',$data,$filedesc)
                      || preg_match('/(?<=文件描述：<\/span><br>\n).+(?=\t)/',$data,$filedesc)
                      ? htmlspecialchars_decode(strip_tags(str_replace("<br /> ","\n",$filedesc[0]))) : "";
    }
    
    if($info["name"]) {
        $info["extension"] = pathinfo($info["name"], PATHINFO_EXTENSION);
    }
    
    if($info["size"]) {
        $info["size_bytes"] = convertToBytes($info["size"]);
    }
    
    if($info["time"]) {
        $info["time_standard"] = Text_conversion_time($info["time"]);
    }
    
    return $info;
}

function getAdditionalFileInfo($info, $data, $data2 = null, $mode = 'pc') {
    if($mode == 'mobile') {
        if(preg_match('/(?<=下载：<\/span>)\d+/', $data, $downloads) || 
           ($data2 && preg_match('/(?<=下载：<\/span>)\d+/', $data2, $downloads))) {
            $info["downloads"] = (int)$downloads[0];
        }
    } else {
        if(preg_match('/下载次数：<\/span>(\d+)/', $data, $downloads)) {
            $info["downloads"] = (int)$downloads[1];
        }
    }
    
    $info["status"] = "正常";
    if(strpos($data, "文件不存在") !== false || strpos($data, "文件已取消") !== false) {
        $info["status"] = "失效";
    } else if(strpos($data, "违规") !== false) {
        $info["status"] = "违规";
    }
    
    $info["need_password"] = (strpos($data, "输入密码") !== false || 
                             strpos($data, "pwd") !== false ||
                             strpos($data, "此文件需下载提取密码") !== false);
    
    return $info;
}

function convertToBytes($size) {
    $units = array('B', 'K', 'M', 'G', 'T');
    $unit = preg_replace('/[^a-zA-Z]/', '', $size);
    $number = floatval(preg_replace('/[^0-9.]/', '', $size));
    
    $index = array_search(strtoupper($unit), $units);
    if ($index !== false) {
        return (int)($number * pow(1024, $index));
    }
    
    return 0;
}

function folder($data,$js) {
    global $id,$pw,$page,$config,$fid;
        
    if(!preg_match("/(?<=data : {)[\s\S]*?(?=},)/",$js,$arr)) { 
        exit(response(-2,"获取失败",null));
    }
    
    foreach(explode("\n",$arr[0]) as $v) {
        if(preg_match("/'(.+)':([\d]+),?$|'(.+)':'(.*)',?$/",$v,$kv) && ($kv[1] || $kv[3])) {
            if($kv[1]) {
                $parameter[$kv[1]] = $kv[2];
            } else {
                $parameter[$kv[3]] = $kv[4];
            }
        } else if(preg_match("/'(.*)':(.*?),/",$v,$kv) && $kv[1]) {
            preg_match("/".$kv[2]."\s*?=\s*?'(.*)'|".$kv[2]."\s*?=\s*?(\d+)/",$js,$value);
            $parameter[$kv[1]] = $value[1] ?? "";
        }
    }
    
    $info = array("fid" => (int)$parameter["fid"],"uid" => (int)$parameter["uid"]);

    if(preg_match("/document\.title\s*=\s*(.*);/",$js,$var) && preg_match("/".$var[1]."\s*=\s*'(.*)'/",$js,$name)) {
        $info["name"] = htmlspecialchars_decode($name[1]); 
    } else if(preg_match("/class=\"b\">(.*?)</",$data,$name)) {
        $info["name"] = htmlspecialchars_decode(trim($name[1]));
    } else {
        $info["name"] = null;
    }
    
    if(preg_match("/(?<=说<\/span>)[\s\S]*?(?=<\/div>)/",$data,$d) && $d[0]) {
        $info["desc"] = strip_tags(htmlspecialchars_decode($d[0]));
    } else {
        $info["desc"] = '';
    }
    
    $parameter["pg"] = $page;
    $parameter["pwd"] = $pw;
    $t_end = $parameter["t"] - time(); 
    if($config["foldercache"] && $t_end > 0) apcu_store("folder$fid",[$info,$parameter,$t_end],$t_end);
    
    if(strpos($js,"document.getElementById('pwd').value;") && !$pw) {
        $info["list"] = null;
        exit(response(2,"请输入密码",$info));
    }
    
    if($config["experimental"] && $page == 2) {
        $parameter["pg"] = 0;
    } else if($page != 1) {
        sleep($page);
    }
    
    f($info,$parameter);
    return "";
}

function response($code,$msg,$data) {
    global $config,$type;
    
    if ($msg === "成功") {
        $code = 200;
    }
    
    if($config["auto-switch"] && !in_array($code,array(-4,200,2)) && $msg!="密码不正确"){
        $config["auto-switch"] = 0;
        if($config["mode"] == "mobile") pc();
        else mobile();
        exit;
    }
    
    if (is_array($data)) {
        $data = removeTempUrl($data);
    }
    
    $res=array("code"=>$code, "msg"=>$msg, "data"=>$data);
    
    switch ($type) {
        case 'xml':
            header('Content-Type: application/xml');
            echo arrayToXml($res);
            break;
        case 'down':
            if(isset($data["url"]) && $data["url"]) header("Location: ".$data["url"]);
            break;
        default:
            echo json_encode($res,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            break;
    }
    
    return "";
}

function arrayToXml($arr,$dom=0,$item=0){
    if(!$dom){
        $dom = new DOMDocument("1.0"); 
    } 
    if(!$item){ 
        $item = $dom->createElement("root"); 
        $dom->appendChild($item); 
    } 
    foreach ($arr as $key=>$val){ 
        $itemx = $dom->createElement(is_string($key)?$key:"item"); 
        $item->appendChild($itemx); 
        if (!is_array($val)){ 
            if(is_bool($val)) $val = $val ? 1 : 0;
            $text = $dom->createTextNode((string)$val); 
            $itemx->appendChild($text); 
        } else { 
            arrayToXml($val,$dom,$itemx); 
        } 
    } 
    return $dom->saveXML(); 
}

function f($info,$parameter) {
    global $config,$desktopua,$ch;
    $json = json_decode(request('https://www.lanzouf.com/filemoreajax.php',"post",$parameter,$desktopua,"data",$ch),true);
    curl_close($ch);
    if(is_array($json["text"])) {
        foreach ($json["text"] as $v) {
            if($v["id"] != "-1") {
                $file_info = array(
                    "id"   => $v["id"],
                    "ad"   => (bool)$v["t"],
                    "name" => htmlspecialchars_decode($v["name_all"]),
                    "size" => $v["size"],
                    "time" => $v["time"],
                    "icon" => $v["p_ico"] ? "https://image.woozooo.com/image/ico/".$v["ico"]."?x-oss-process=image/auto-orient,1/resize,m_fill,w_100,h_100/format,png" : null,
                );
                
                $file_info["extension"] = pathinfo($file_info["name"], PATHINFO_EXTENSION);
                $file_info["size_bytes"] = convertToBytes($file_info["size"]);
                $file_info["time_standard"] = Text_conversion_time($file_info["time"]);
                
                $info["list"][] = $file_info;
            }
        }
        $info["have_page"] = count($json["text"]) >= 50;
        $info["total_files"] = count($info["list"]);
        response(200,"成功",$info);
    } else {
        $info["list"] = null;
        $info["have_page"] = false;
        $info["total_files"] = 0;
        $config["auto-switch"] = 0;
        response(-1,$json["info"],$info);
    }
}

function request($url, $method = 'GET', $postdata = array(), $headers = array(),$responsetype = "all",$curl = null) {
    
    $headers[] = "Referer: https://www.lanzouf.com/";
    $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9";
    $headers[] = "Accept-Encoding: gzip, deflate, br";
    $headers[] = "Accept-Language: zh-CN,zh;q=0.9,zh-HK;q=0.8,zh-TW;q=0.7";
    $headers[] = "Cache-Control: max-age=0";
    $headers[] = "Connection: keep-alive";
    $headers[] = "X-Forwarded-For: ".rand(1,255).".".rand(1,255).".".rand(1,255).".".rand(1,255);
    
    if(!$curl) {
        $curl = curl_init();
        $internalCurl = true;
    }
    
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
    
    if ( strtoupper($method) == 'POST') { 
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postdata));
    } else {
        curl_setopt($curl, CURLOPT_HTTPGET, true);
    }
    
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_NOBODY, ($responsetype == "info"));
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    
    $data = array('data' => curl_exec($curl),'info' => curl_getinfo($curl));
    if(isset($internalCurl)) curl_close($curl);
    return $data[$responsetype] ?? $data;
}

function geturl($js,$info,$error,$pw) {
    global $desktopua,$ch,$id;
    
    $fileid = extractFileId($js, $id);
    $sign = extractSign($js);
    
    if(!$fileid || !$sign) {
        exit(response(-2,$error,null));
    }
    
    $info = array("fid" => $fileid) + $info;
    
    $needPassword = checkNeedPassword($js);
    $info["need_password"] = $needPassword;
    
    if($needPassword && !$pw) {
        $info["url"] = null;
        exit(response(2,"请输入密码",$info)); 
    }
    
    $postData = buildPostData($sign, $pw, $js);
    $json = sendDownloadRequest($fileid, $postData, $desktopua, $ch);
    
    if($json["zt"] == 1) {
        if(isset($json["inf"]) && $json["inf"]) { 
	        $info["name"] = $json["inf"];
	    }
	    $info["temp_url"] = $json["dom"].'/file/'.$json["url"];
	    curl_close($ch);
        getPermanentUrl($info);
    } else {
        $info["url"] = null;
        response(-1,$json['inf'] ?? "获取失败",$info);
    }
}

function extractFileId($data, $id) {
    if(preg_match("/(?<=file=)\d+/", $data, $fileid)) {
        return (int)$fileid[0];
    }
    
    if(preg_match("/ajaxm\.php\?file=(\d+)/", $data, $fileid)) {
        return (int)$fileid[1];
    }
    
    if(preg_match("/(\d+)/", $id, $fileid)) {
        return (int)$fileid[1];
    }
    
    return null;
}

function extractSign($data) {
    $data = preg_replace("/\/\/.*|\/\*[\s\S]*\*\/|function woio[\s\S]*?}/","",$data);
    
    if(preg_match("/(?<='sign':')([^']+)(?=')/", $data, $sign)) {
        return $sign[1];
    }
    
    if(preg_match("/var\s+vidksek\s*=\s*'([^']+)'/", $data, $sign)) {
        return $sign[1];
    }
    
    if(preg_match_all("/(?<=')[\w]{50,}+(?=')/", $data, $longStrings)) {
        $lengths = array_map("strlen", $longStrings[0]);
        $maxIndex = array_search(max($lengths), $lengths);
        return $longStrings[0][$maxIndex];
    }
    
    return null;
}

function checkNeedPassword($data) {
    return (strpos($data, "输入密码") !== false || 
            strpos($data, "pwd") !== false ||
            strpos($data, "此文件需下载提取密码") !== false ||
            strpos($data, "document.getElementById('pwd').value") !== false);
}

function buildPostData($sign, $pw, $data) {
    $postData = array(
        'action' => 'downprocess',
        'sign' => $sign,
        'p' => $pw
    );
    
    if(preg_match("/(?<='websign':')([^']+)(?=')/", $data, $websign)) {
        $postData['websign'] = $websign[1];
    }
    
    if(preg_match("/(?<='websignkey':')([^']+)(?=')/", $data, $websignkey)) {
        $postData['websignkey'] = $websignkey[1];
    }
    
    if(preg_match("/var\s+kdns\s*=\s*(\d+)/", $data, $kd)) {
        $postData['kd'] = $kd[1];
    }
    
    return $postData;
}

function sendDownloadRequest($fileid, $postData, $headers, $curl) {
    $json = json_decode(request(
        "https://www.lanzouf.com/ajaxm.php?file=$fileid",
        "post",
        $postData,
        $headers,
        "data",
        $curl
    ), true);
    
    return $json;
}

function Text_conversion_time($str) {
    if(!$str) {
        return $str;
    } else if (preg_match("/^\d+(?=\s*秒)/",$str,$i)) {
        return date("Y-m-d", time() - $i[0]);
    } else if (preg_match("/^\d+(?=\s*分钟)/",$str,$i)) {
        return date("Y-m-d", time() - $i[0] * 60);
    } else if (preg_match("/^\d+(?=\s*小时)/",$str,$i)) {
        return date("Y-m-d", time() - $i[0] * 60 * 60);
    } else if (preg_match("/^\d+(?=\s*天)/", $str,$i)) {
        return date("Y-m-d", time() - $i[0] * 24 * 60 * 60);
    } else if (strpos($str,"昨天") !== false) {
        return date("Y-m-d", time() -  24 * 60 * 60);
    } else if (strpos($str,"前天") !== false) {
        return date("Y-m-d", time() - 2 * 24 * 60 * 60);
    } else {
        return $str;
    }
}
?>