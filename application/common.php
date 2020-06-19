<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use app\index\tool\MailHelper;

function json_error($msg): \think\response\Json
{
    return json(compact('msg'), 400);
}

function json_success($msg): \think\response\Json
{
    return json(compact('msg'));
}

function json_data($data, $msg = 'OK'): \think\response\Json
{
    return json(compact('msg', 'data'));
}

function is_date(string $date): bool
{
    if (!strlen($date)) {
        return false;
    }
    return date('Y-m-d', strtotime($date)) === $date;
}

function is_time(string $date): bool
{
    if (!strlen($date)) {
        return false;
    }
    return date('Y-m-d H:i:s', strtotime($date)) === $date;
}

function time_to_date($time)
{
    if (is_time($time)) {
        return date('Y-m-d', strtotime($time));
    } elseif ($time != '0000-00-00 00:00:00') {
        return date('Y-m-d', $time);
    }
    return '-';
}


/**
 * 日期拼接时分秒
 * @param $date
 * @return array
 * User: TaoQ
 * Date: 2019/5/9
 */
function data_to_datatime($date)
{
    $arr = [
        explode(' - ', $date)[0].' 00:00:00',
        explode(' - ', $date)[1].' 23:59:59'
    ];
    return $arr;
};

/**
 * 是否为日期范围
 * @param string $date
 * @return bool
 */
function is_date_range(string $date): bool
{
    if (!strlen($date)) {
        return false;
    }
    $arr = explode(' - ', $date);
    if (2 != count($arr)) {
        return false;
    }
    if (!is_date($arr[0])) {
        return false;
    }
    if (!is_date($arr[1])) {
        return false;
    }
    if (strtotime($arr[0]) > strtotime($arr[1])) {
        return false;
    }
    return true;
}

/**
 * 防止null错误助手
 * @param $obj
 * @param string $default
 * @return \app\index\tool\Optional
 */
function optional($obj, $default = ''): \app\index\tool\Optional
{
    return new \app\index\tool\Optional($obj, $default);
}

/**
 * 随机一个cdn域名，比如  https://qiniu1.ac.vip
 * @return string
 * @throws Exception
 */
function qiniu_domains(): string
{
    static $flag = 0;
    $arr = explode(',', env('QINIU_DOMAINS'));
    $url = $arr[$flag];
    $flag++;
    if ($flag >= (count($arr) - 1)) {
        $flag = 0;
    }
    return $url;
}

/**
 * 获取 域名 例如：http://alpha.e.ac.vip
 * @return string
 * User: TaoQ
 * Date: 2019/5/8
 */
function shop_domain(): string
{
    return env('SHOP_DOMAIN');
}

/**
 * 获取 域名 例如：http://alpha.api.ac.vip
 * @return string
 * User: TaoQ
 * Date: 2019/5/8
 */
function huaban_api_url(): string
{
    return env('HUABAN_API_URL');
}


function upload_file($fileRealPath): string
{
    $fileRealPath = realpath($fileRealPath);
    if (!is_file($fileRealPath)) {
        throw new \Exception('文件不存在，无法上传');
    }
    //初始化
    $ch = curl_init('https://comapi.ac.vip/index/file/upload');
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new \CURLFile($fileRealPath)]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    if (false === $output) {
        return '';
    }
    $arr = json_decode($output, true);
    if (JSON_ERROR_NONE !== json_last_error()) {
        throw new \Exception('上传图片comapi接口出错');
    }
    if (!isset($arr['url'])) {
        throw new \Exception('上传图片comapi接口出错');
    }
    return $arr['url'];
}

/**
 * 生成随机字符串
 * @param int $length 需要生成字符串的长度
 * @param int $type  类型：1->小写字母，2->大写字母，3->数字，4->大小写字母混合，5->大小写字母及数字组合,6->数字、小写字母组合, 7->大写字母、数字组合
 * @return string
 * User: TaoQ
 * Date: 2019/4/10
 */
function rand_str($length = 6, $type = 5)
{
    $randStr = '';
    switch($type){
        case 1:
            $char = range(chr(97),chr(122));//小写字母
            break;
        case 2:
            $char = range(chr(65),chr(90));//大写字母
            break;
        case 3:
            $char = range(0,9);//数字
            break;
        case 4:
            $char = array_merge(range(chr(97),chr(122)),range(chr(65),chr(90)));//大小写字母组合
            break;
        case 6:
            $char = array_merge(range(chr(97),chr(122)),range(0,9));//小写字母、数组组合
            break;
        case 7:
            $char = array_merge(range(chr(65),chr(90)),range(0,9));//大写字母、数组组合
            break;
        default:
            $char = array_merge(range(chr(97),chr(122)),range(chr(65),chr(90)),range(0,9));//大小写字母及数字组合
            break;
    }
    //删除容易混淆的字母及数字
    if($type >= 4){
        foreach($char as $k =>$z){
            if(in_array($z, array('0','O','o','I','l'))) unset($char[$k]);
        }
    }
    //重置数组索引，避免取到空的情况
    $char = array_values($char);
    $arr_count = count($char)-1;
    for($i = 0; $i < $length; $i++){
        $randStr .= $char[mt_rand(0,$arr_count)];
    }
    return $randStr;
}

/**
 * 验证电子邮箱
 * @param $email
 * @return bool
 * User: TaoQ
 * Date: 2019/4/12
 */
function is_email(string $email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return true;
}

/**
 * 验证手机号码
 * @param $phone
 * @return bool
 * User: TaoQ
 * Date: 2019/4/12
 */
function is_phone(string $phone): bool
{
    $preg_phone='/^1[0-9]\d{9}$/ims';
    if (!preg_match($preg_phone, $phone)) {
        return false;
    }
    return true;
}

/**
 * 验证身份证号码
 * @param $idCard
 * @return bool
 * User: TaoQ
 * Date: 2019/4/12
 */
function is_idCard(string $idCard): bool
{
    $preg_card="/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/";
    if (!preg_match($preg_card, $idCard)) {
        return false;
    }
    return true;
}

/**
 * 验证银行帐号
 * @param $bankCard
 * @return bool
 * User: TaoQ
 * Date: 2019/4/12
 */
function is_bankCard(string $bankCard): bool
{
    $preg_bankcard='/^(\d{15}|\d{16}|\d{19})$/isu';
    if (!preg_match($preg_bankcard, $bankCard)) {
        return false;
    }
    return true;
}

function get_page(): int
{
    $page = (int)request()->get('page');
    return $page > 1 ? $page : 1;
}

function cdn_path(string $url): string
{
    $arr = explode(',', env('QINIU_DOMAINS'));
    foreach ($arr as $domain) {
        if (0 === strpos($url, $domain)) {
            $url = str_replace($domain, '', $url);
        }
    }
    return $url;
}

function get_price($price): string
{
    return number_format($price, 2, '.', '');
}

/**
 * 导出Excel
 * @param $spreadsheet   数据
 * @param string $format  格式:excel2003 = xls, excel2007 = xlsx
 * @param string $savename  保存的文件名
 * @return bool
 * User: TaoQ
 * Date: 2019/4/23
 */
function exportExcel($spreadsheet, $format = 'xls', $savename = 'export') {
    if (!$spreadsheet) return false;
    if ($format == 'xls') {
        //输出Excel03版本
        header('Content-Type:application/vnd.ms-excel');
        $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
    } elseif ($format == 'xlsx') {
        //输出07Excel版本
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
    }
    //输出名称
    header('Content-Disposition: attachment;filename="'.$savename.'.'.$format.'"');
    //禁止缓存
    header('Cache-Control: max-age=0');
    $writer = new $class($spreadsheet);
    $filePath = env('runtime_path')."temp/".time().microtime(true).".tmp";
    $writer->save($filePath);
    readfile($filePath);
    unlink($filePath);
    die;
}

function get_shop_id()
{
    return \app\index\tool\Auth::shop() ? \app\index\tool\Auth::shop()->shop_id : 0;
}

function get_shop_user_id()
{
    return \app\index\tool\Auth::shopUser() ? \app\index\tool\Auth::shopUser()->user_id : 0;
}

function get_user_id()
{
    return \app\index\tool\Auth::user() ? \app\index\tool\Auth::user()->user_id : 0;
}

function get_moderator_id()
{
    $userMod = \app\index\model\UserModerator::where('shop_id', get_shop_id())->find();
    return $userMod ? $userMod->id : 0;
}

/**
 * 版主的id
 * User: TaoQ
 * Date: 2019/11/7
 */
function get_copyright_owner_id()
{
    return \app\index\tool\Auth::shop() ? \app\index\tool\Auth::shop()->copyright_owner_id : 0;
}

function has_permission($key): bool
{
    $key = explode(',', $key);
    foreach ($key as $value) {
        if (\app\index\service\PermissionService::hasPermission($value)) {
            return true;
        }
    }
    return false;
}

function check_permission($key)
{
    if (!\app\index\service\PermissionService::hasPermission($key)) {
        abort(403);
    }
}

function get_rand()
{
    $num = rand(0, 99);
    $one = 0;
    if ($num < 10) {
        $num = $one.$num;
    }
    return $num;
}

function is_china_phone(string $phone): bool
{
    if (!is_numeric($phone)) {
        return false;
    }
    if (11 !== strlen($phone)) {
        return false;
    }
    if ('1' !== $phone{0}) {
        return false;
    }
    return true;
}

function rand_num($n=7)
{
    $str="1234567890";
    str_shuffle($str);
    $number=substr(str_shuffle($str),1,$n);
    return $number;
}

function time_diff($begin_time,$end_time)
{
    if($begin_time < $end_time){
        $start_time = $begin_time;
        $end_time = $end_time;
    }else{
        $start_time = $end_time;
        $end_time = $begin_time;
    }

    //计算天数
    $time_diff = $end_time-$start_time;
    $days = intval($time_diff/86400);
    //计算小时数
    $remain = $time_diff%86400;
    $hours = intval($remain/3600);
    //计算分钟数
    $remain = $remain%3600;
    $mins = intval($remain/60);
    //计算秒数
    $secs = $remain%60;
    $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
    return $res;
}

/**
*@desc 封闭curl的调用接口，get的请求方式。
*/
function doCurlGetRequest($url,$timeout = 5){
    if($url == "" || $timeout <= 0){
        return false;
    }
    $con = curl_init((string)$url);
    curl_setopt($con, CURLOPT_HEADER, false);
    curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);

    return curl_exec($con);
}

/**
 * 远程下载文件到本地
 * @param string $url
 * @param string $localFilePath
 * @throws Exception
 * User: TaoQ
 * Date: 2019/6/14
 */
function download_file(string $url, string $localFilePath)
{
    $fp = fopen($url, 'rb');
    if (false === $fp) {
        throw new \Exception('下载远程文件失败');
    }
    set_time_limit(0);
    if (!is_readable($localFilePath)) {
        @unlink($localFilePath);
    }
    while (!feof($fp)) {
        file_put_contents($localFilePath, fread($fp, 1048576), FILE_APPEND);
    }
    fclose($fp);
}

/**
 * 物权登记方法
 * @param $data
 * @return string
 * User: TaoQ
 * Date: 2019/9/6
 */
function encryption($data)
{
    $data       = json_encode($data,JSON_UNESCAPED_SLASHES);
    $secret_key = config('huaban.copyright.secret_key');
    $aesKey     = file_get_contents($secret_key);
    $iv         = 'z123456876467252';
    $data_key   = @openssl_encrypt($data,"AES-256-CBC",$aesKey,OPENSSL_RAW_DATA,$iv);
    $msgData    = base64_encode($data_key);
    return $msgData;
}

/**
 * curl_request 模拟POST提交
 * @param  string  $url          提交地址
 * @param  string  $post         post数据(不填则为GET)
 * @param  string  $cookie       提交的$cookies
 * @param  integer $returnCookie 是否返回$cookies
 * @return mixed
 */
function curl_request($url,$post=[],$cookie='',$returnCookie=0)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
    curl_setopt($curl,CURLOPT_HTTPHEADER,array(
        'Authorization:Bearer '.config('app.product_freight.freight_token')
    ));
    if($post) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if($cookie) {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    if($returnCookie){
        list($header, $body) = explode("\r\n\r\n", $data, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
        $info['cookie']  = substr($matches[1][0], 1);
        $info['content'] = $body;
        return $info;
    }else{
        return $data;
    }
}

function exception_email($email, $title, $exception)
{
    $helper = new MailHelper($email);
    $helper->exceptionMessage($title, $exception);
    $helper->send();
}

function cash_email($email, $title, $options)
{
    $helper = new MailHelper($email);
    $helper->cashOutMessage($title, $options);
    $helper->send();
}

/**
 * Generates an UUID
 *
 * @author     Anis uddin Ahmad
 * @param      string  an optional prefix
 * @return     string  the formatted uuid
 */
function uuid($prefix = '')
{
    $chars = md5(uniqid(mt_rand(), true));
    $uuid  = substr($chars,0,8);
    $uuid .= substr($chars,8,4);
    $uuid .= substr($chars,12,4);
    $uuid .= substr($chars,16,4);
    $uuid .= substr($chars,20,12);
    return $prefix . $uuid;
}

function cut_str($str,$len,$suffix="......"){
    if(function_exists('mb_substr')){
        if(strlen($str) > $len){
            $str= mb_substr($str,0,$len).$suffix;
        }
        return $str;
    }else{
        if(strlen($str) > $len){
            $str= substr($str,0,$len).$suffix;
        }
        return $str;
    }
}


function getAddress($area_id=0)
{
    if($area_id > 0 ){
        return \app\index\model\City::where('area_id', $area_id)->find()->area_name;
    }
    return '';
}

/**
 * 全角转半角
 * @param $str
 * @return string
 * User: TaoQ
 * Date: 2019/12/3
 */
function make_semiangle($str)
{
    $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
        '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
        'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
        'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
        'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
        'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
        'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
        'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
        'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
        'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
        'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
        'ｙ' => 'y', 'ｚ' => 'z',
        '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
        '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
        '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
        '》' => '>',
        '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
        '：' => ':', '。' => '.', '、' => ',', '，' => ',', '、' => '.',
        '；' => ';', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
        '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
        '　' => ' ');

    return strtr($str, $arr);
}
