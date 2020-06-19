<?php


namespace app\index\service;


use GuzzleHttp\Client;

class HuabanApiService
{
    private static $client = null;

    private static function getClient(): Client
    {
        if (null === static::$client) {
            static::$client = new Client();
        }
        return static::$client;
    }

    /**
     * 发送商家入驻短信
     * @param string $phone 手机号码
     * @param string $code 验证码
     * @param string $country 国家代码，默认86
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @document https://gitlab.ac.vip/charles/api.ac.vip/blob/develop/doc/%E7%9F%AD%E4%BF%A1/%E7%AC%AC%E4%B8%89%E6%96%B9%E5%95%86%E5%AE%B6/%E7%94%B3%E8%AF%B7%E5%85%A5%E9%A9%BB%E6%97%B6%E7%9F%AD%E4%BF%A1.md
     */
    public static function merchantApplicationCode(string $phone, string $code, string $country = '86'): bool
    {
        $url = env('HUABAN_API_URL') . '/sms/merchant/application-code';
        $response = static::getClient()->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
            ],
            'form_params' => compact('phone', 'code', 'country')
        ]);
        return 200 === $response->getStatusCode();
    }

    /**
     * 商户找回密码短信
     * @param string $phone   手机号码
     * @param string $code    验证码
     * @param string $country 国家代码  默认86
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/4/23
     */
    public static function merchantResetPasswordCode(string $phone, string $code, string $country = '86'): bool
    {
        $url = env('HUABAN_API_URL') . '/sms/merchant/reset-password-code';
        $response = static::getClient()->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
            ],
            'form_params' => compact('phone', 'code', 'country')
        ]);
        return 200 === $response->getStatusCode();
    }

    /**
     * 商家入驻成功后的短信提醒
     * @param string $country   国家代码
     * @param string $phone     手机号码
     * @param string $name      店铺名称
     * @param string $password  初始密码
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/4/23
     */
    public static function merchantApplicationSuccess(string $phone, string $name, string $password, string $country = "86"): bool
    {
        $url = env('HUABAN_API_URL') . '/sms/merchant/application-success';
        $response = static::getClient()->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
            ],
            'form_params' => compact('country', 'phone', 'name', 'password')
        ]);
        return 200 === $response->getStatusCode();
    }

    /**
     * App 订单发货app 推送
     * @param int $order_id
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/9/9
     */
    public static function orderLogisticsPush(int $order_id): bool
    {
        $url = env('HUABAN_API_URL') . '/order/logistics-push';
        $response = static::getClient()->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
            ],
            'form_params' => [
                'id' => $order_id
            ]
        ]);

        return 200 === $response->getStatusCode();
    }

    /**
     * 溯源二维码 加入队列生成
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/9/10
     */
    public static function traceSourceAddApply(array $data): array
    {
        $result = [];
//        $url = 'http://api.ac.local/trace-source/add-apply'; // 用于做测试
        $url = env('HUABAN_API_URL') . '/trace-source/add-apply';
        try {
            $response = static::getClient()->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
                ],
                'form_params' => $data
            ]);
            $body = (string)$response->getBody();
            $result = json_decode($body, true);
            $result['code'] = $response->getStatusCode();
        } catch (\Exception $exception) {
            $result['code'] = $exception->getCode();
            $arr = json_decode((string)$exception->getResponse()->getBody(), true);
            switch (400 == $exception->getCode()) {
                case 400:
                    $result['message'] = isset($arr['message']) ? $arr['message'] : '';
                    break;
                default:
                    $result['message'] = '服务器错误，' . $exception->getCode();
                    break;
            }
        }
        return $result;
    }

    /**
     * 检测并消费运营平台登录商家后台token
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/9/27
     */
    public static function checkLoginToken($token): array
    {
        $result = [];
//        $url = 'http://api.ac.local/token/check-mall-login?token='.$token; // 用于做测试
        $url = env('HUABAN_API_URL') . '/token/check-mall-login?token='.$token;
        try {
            $response = static::getClient()->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
                ],
                'form_params' => []
            ]);
            $body = (string)$response->getBody();
            $result = json_decode($body, true);
            $result['code'] = $response->getStatusCode();
        } catch (\Exception $exception) {
            $result['code'] = $exception->getCode();
            $arr = json_decode((string)$exception->getResponse()->getBody(), true);
            switch (400 == $exception->getCode()) {
                case 400:
                    $result['message'] = isset($arr['message']) ? $arr['message'] : '';
                    break;
                default:
                    $result['message'] = '服务器错误，' . $exception->getCode();
                    break;
            }
        }
        return $result;
    }

    /**
     * 检测并消费版主后台登录商家后台token
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/9/27
     */
    public static function checkModeratroLoginToken($token): array
    {
        $result = [];
        $url = env('HUABAN_API_URL') . '/token/check-moderator-login?token='.$token;
        try {
            $response = static::getClient()->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
                ],
                'form_params' => []
            ]);
            $body = (string)$response->getBody();
            $result = json_decode($body, true);
            $result['code'] = $response->getStatusCode();
        } catch (\Exception $exception) {
            $result['code'] = $exception->getCode();
            $arr = json_decode((string)$exception->getResponse()->getBody(), true);
            switch (400 == $exception->getCode()) {
                case 400:
                    $result['message'] = isset($arr['message']) ? $arr['message'] : '';
                    break;
                default:
                    $result['message'] = '服务器错误，' . $exception->getCode();
                    break;
            }
        }
        return $result;
    }

    /**
     * 查看物流信息
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/11/20
     */
    public static function deliveryDetail(array $data): array
    {
        $result = [];
        $url = env('HUABAN_API_URL') . '/delivery/poll?company=' . $data['company'] . '&number=' . $data['number'];
        try {
            $response = static::getClient()->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
                ],
                'form_params' => []
            ]);
            $body = (string)$response->getBody();
            $result = json_decode($body, true);
            $result['code'] = $response->getStatusCode();
        } catch (\Exception $exception) {
            $result['code'] = $exception->getCode();
            $arr = json_decode((string)$exception->getResponse()->getBody(), true);
            switch (400 == $exception->getCode()) {
                case 400:
                    $result['message'] = isset($arr['message']) ? $arr['message'] : '';
                    break;
                default:
                    $result['message'] = '服务器错误，' . $exception->getCode();
                    break;
            }
        }
        return $result;
    }

    /**
     * 订单退款同意接口
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/12/11
     */
    public static function orderRefundAgree(array $data): array
    {
        $result = [];
        $url = env('HUABAN_API_URL') . '/order/merchant-confirm-refund';
        try {
            $response = static::getClient()->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
                ],
                'form_params' => $data
            ]);
            $body = (string)$response->getBody();
            $result = json_decode($body, true);
            $result['code'] = $response->getStatusCode();
        } catch (\Exception $exception) {
            $result['code'] = $exception->getCode();
            $arr = json_decode((string)$exception->getResponse()->getBody(), true);
            switch (400 == $exception->getCode()) {
                case 400:
                    $result['message'] = isset($arr['message']) ? $arr['message'] : '';
                    break;
                default:
                    $result['message'] = '服务器错误，' . $exception->getCode();
                    break;
            }
        }
        return $result;
    }

    /**
     * 订单退款拒绝接口
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/12/11
     */
    public static function orderRefundRefuse(array $data): array
    {
        $result = [];
        $url = env('HUABAN_API_URL') . '/order/merchant-deny-refund';
        try {
            $response = static::getClient()->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
                ],
                'form_params' => $data
            ]);
            $body = (string)$response->getBody();
            $result = json_decode($body, true);
            $result['code'] = $response->getStatusCode();
        } catch (\Exception $exception) {
            $result['code'] = $exception->getCode();
            $arr = json_decode((string)$exception->getResponse()->getBody(), true);
            switch (400 == $exception->getCode()) {
                case 400:
                    $result['message'] = isset($arr['message']) ? $arr['message'] : '';
                    break;
                default:
                    $result['message'] = '服务器错误，' . $exception->getCode();
                    break;
            }
        }
        return $result;
    }

    /**
     * 物权绑定接口
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/12/26
     */
    public static function sendOrderGoods(array $data): array
    {
        $result = [];
        $url = env('HUABAN_API_URL') . '/copyright/send-order-goods';
        try {
            $response = static::getClient()->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('HUABAN_API_TOKEN'),
                ],
                'form_params' => $data
            ]);
            $body = (string)$response->getBody();
            $result = json_decode($body, true);
            $result['code'] = $response->getStatusCode();
        } catch (\Exception $exception) {
            $result['code'] = $exception->getCode();
            $arr = json_decode((string)$exception->getResponse()->getBody(), true);
            switch (400 == $exception->getCode()) {
                case 400:
                    $result['message'] = isset($arr['message']) ? $arr['message'] : '';
                    break;
                default:
                    $result['message'] = '服务器错误，' . $exception->getCode();
                    break;
            }
        }
        return $result;
    }
}