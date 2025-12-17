<?php

class Pan123Downloader {
    private $token;
    private $username;
    private $password;
    private $supported_domains = ['123865.com', '123pan.com', '123684.com'];
    
    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
        $this->login();
    }
    
    private function getTimestamp() {
        return strval(time() * 1000);
    }
    
    private function crc32($data) {
        return hash('crc32', $data);
    }
    
    private function hexToInt($hex_str) {
        return hexdec($hex_str);
    }
    
    private function encode123($url, $way, $version, $timestamp) {
        $a = intval(10000000 * mt_rand(1, 10000000) / 10000);
        $u = "adefghlmyijnopkqrstubcvwsz";
        $time_long = intval($timestamp) / 1000;
        $time_str = date('YmdHi', $time_long);
        $g = "";
        for ($i = 0; $i < strlen($time_str); $i++) {
            $digit = intval($time_str[$i]);
            if ($digit == 0) {
                $g .= $u[0];
            } else {
                $g .= $u[$digit - 1];
            }
        }
        
        $y = strval($this->hexToInt($this->crc32($g)));
        $final_crc_input = "{$time_long}|{$a}|{$url}|{$way}|{$version}|{$y}";
        $final_crc = strval($this->hexToInt($this->crc32($final_crc_input)));
        
        return "?{$y}={$time_long}-{$a}-{$final_crc}";
    }
    
    private function login() {
        $login_data = [
            "passport" => $this->username,
            "password" => $this->password,
            "remember" => true
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://login.123pan.com/api/user/sign_in",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($login_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response && $http_code == 200) {
            $result = json_decode($response, true);
            if (isset($result['code']) && $result['code'] == 200) {
                $this->token = $result['data']['token'] ?? '';
                return true;
            }
        }
        
        return false;
    }
    
    private function getShareInfo($share_key, $password = '') {
        $api_url = "https://www.123pan.com/b/api/share/get?limit=100&next=1&orderBy=share_id&orderDirection=desc&shareKey={$share_key}&SharePwd={$password}&ParentFileId=0&Page=1";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Referer: https://www.123pan.com/',
                'Origin: https://www.123pan.com'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response && $http_code == 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    private function getDownloadUrlAndroid($file_info) {
        if (!$this->token) {
            return null;
        }
        
        $post_data = [
            'driveId' => 0,
            'etag' => $file_info['Etag'] ?? '',
            'fileId' => $file_info['FileId'] ?? '',
            'fileName' => $file_info['FileName'] ?? '',
            's3keyFlag' => $file_info['S3KeyFlag'] ?? '',
            'size' => $file_info['Size'] ?? 0,
            'type' => 0
        ];
        
        $timestamp = $this->getTimestamp();
        $encrypted_params = $this->encode123('/b/api/file/download_info', 'android', '55', $timestamp);
        $api_url = "https://www.123pan.com/b/api/file/download_info" . $encrypted_params;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'App-Version: 55',
                'platform: android',
                'Authorization: Bearer ' . $this->token,
                'User-Agent: Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36',
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response && $http_code == 200) {
            $result = json_decode($response, true);
            if (isset($result['code']) && $result['code'] == 0 && isset($result['data'])) {
                return $result['data']['DownloadUrl'] ?? $result['data']['DownloadURL'] ?? null;
            }
        }
        
        return null;
    }
    
    private function extractShareKey($link) {
        $patterns = [
            '/\/s\/(.*?)\.html/',
            '/\/s\/([^\/\s]+)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $link, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    private function isValidShareLink($link) {
        foreach ($this->supported_domains as $domain) {
            if (strpos($link, "{$domain}/s/") !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function formatSize($size_bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $i < count($units); $i++) {
            if ($size_bytes < 1024.0) {
                return sprintf("%.2f %s", $size_bytes, $units[$i]);
            }
            $size_bytes /= 1024.0;
        }
        return sprintf("%.2f PB", $size_bytes);
    }
    
    public function parseShareLink($link, $share_password = '') {
        $result = [
            'code' => 200,
            'message' => 'success',
            'data' => null
        ];
        
        if (!$this->isValidShareLink($link)) {
            $result['code'] = 400;
            $result['message'] = '无效的分享链接格式';
            return $result;
        }
        
        $share_key = $this->extractShareKey($link);
        if (!$share_key) {
            $result['code'] = 400;
            $result['message'] = '无法提取分享密钥';
            return $result;
        }
        
        $share_data = $this->getShareInfo($share_key, $share_password);
        
        if (!$share_data || !isset($share_data['code']) || $share_data['code'] != 0) {
            $error_msg = $share_data['message'] ?? '请求失败';
            $result['code'] = 500;
            $result['message'] = "获取分享信息失败: {$error_msg}";
            return $result;
        }
        
        if (!isset($share_data['data']['InfoList'])) {
            $result['code'] = 500;
            $result['message'] = '返回数据格式错误';
            return $result;
        }
        
        $info_list = $share_data['data']['InfoList'];
        
        foreach ($info_list as $file_info) {
            $file_type = $file_info['Type'] ?? 0;
            $file_name = $file_info['FileName'] ?? '';
            
            if ($file_type != 0) {
                continue;
            }
            
            $download_url = $this->getDownloadUrlAndroid($file_info);
            
            if ($download_url) {
                $result['data'] = [
                    "name" => $file_name,
                    "size" => $file_info['Size'] ?? 0,
                    "down" => $download_url,
                    "key" => $share_key
                ];
                return $result;
            }
        }
        
        $result['code'] = 404;
        $result['message'] = '没有成功获取到文件的直链<br><br>请检查账号密码是否正常<br><br>同时请检查您输入的Url是否正确';
        return $result;
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, HEAD');

try {
    $username = $_GET['user'] ?? '';
    $password = $_GET['pass'] ?? '';
    $share_url = $_GET['url'] ?? '';
    $share_password = $_GET['pwd'] ?? '';
    

    if (empty($username) || empty($password)) {
        echo json_encode([
            'code' => 400,
            'message' => '缺少账号密码参数<br>请联系管理员在“index.js”文件中进行配置'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    if (empty($share_url)) {
        echo json_encode([
            'code' => 400,
            'message' => '缺少必要参数: url'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    $downloader = new Pan123Downloader($username, $password);
    $result = $downloader->parseShareLink($share_url, $share_password);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'code' => 500,
        'message' => '服务器内部错误: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>