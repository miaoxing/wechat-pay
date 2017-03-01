<?php

namespace Miaoxing\WechatPay\Service;

use SimpleXMLElement;

/**
 * 微信支付V3接口
 *
 * @property \Wei\Logger $logger
 * @property \Wei\Http $http
 * @method \Wei\Http http(array $options = [])
 * @link http://pay.weixin.qq.com/wiki/doc/api/index.php
 */
class WechatPayApiV3 extends \miaoxing\plugin\BaseService
{
    protected $baseUrl = 'https://api.mch.weixin.qq.com';

    /**
     * 公众账号ID
     *
     * @var string
     */
    protected $appId;

    /**
     * 商户号
     *
     * @var string
     */
    protected $mchId;

    /**
     * 加密的密钥
     *
     * @var string
     */
    protected $appKey;

    /**
     * 证书 apiclient_cert.pem 的路径
     *
     * @var string
     */
    protected $sslCertFile;

    /**
     * 证书 apiclient_key.pem 的路径
     *
     * @var string
     */
    protected $sslKeyFile;

    /**
     * 错误提示
     *
     * @var array
     */
    protected $messages = [
        'NOAUTH' => '商户无此接口权限',
        'NOTENOUGH' => '余额不足',
        'ORDERPAID' => '商户订单已支付',
        'ORDERCLOSED' => '订单已关闭',
    ];

    /**
     * 统一下单
     *
     * @param array $data
     * @return array
     */
    public function unifiedOrder(array $data)
    {
        return $this->call('pay/unifiedorder', $data);
    }

    /**
     * 转换短链接
     *
     * @param string $longUrl
     * @return array
     */
    public function shortUrl($longUrl)
    {
        $data = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'long_url' => $longUrl,
            'nonce_str' => $this->generateNonceStr(),
        ];
        $data['sign'] = $this->sign($data);

        return $this->call('tools/shorturl', $data);
    }

    /**
     * 申请退款
     *
     * @param array $data
     * @return array
     */
    public function refund(array $data)
    {
        return $this->call('secapi/pay/refund', $data, true);
    }

    /**
     * 发放现金红包
     *
     * @param array $data
     * @return array
     */
    public function sendRedPack(array $data)
    {
        $mchId = $this->mchId;
        $data += [
            'nonce_str' => $this->generateNonceStr(),
            'mch_billno' => $mchId . date('Ymd') . (1000000000 + $data['id']),
            'mch_id' => $mchId,
            'wxappid' => $this->appId,
            'nick_name' => '',
            'send_name' => '', // send_name字段必填，并且少于32字符
            're_openid' => $data['re_openid'],
            'total_amount' => $data['total_amount'],
            'min_value' => $data['total_amount'],
            'max_value' => $data['total_amount'],
            'total_num' => 1,
            'wishing' => '', // wishing字段为必填,并且少于128个字符
            'client_ip' => wei()->request->getIp(),
            'act_name' => '', // act_name字段必填,并且少于32个字符
            'remark' => '', // remark字段为必填,并且少于256字符
        ];
        unset($data['id']);
        $data['sign'] = $this->sign($data);

        return $this->call('mmpaymkttransfers/sendredpack', $data, true);
    }

    /**
     * 企业支付
     *
     * @param array $data
     * @return array
     */
    public function transfers(array $data)
    {
        $data += [
            'mch_appid' => $this->appId,
            'mchid' => $this->mchId,
            'nonce_str' => $this->generateNonceStr(),
            'partner_trade_no' => $data['partner_trade_no'],
            'openid' => $data['openid'],
            'check_name' => 'NO_CHECK',
            'amount' => (int) $data['amount'],
            'desc' => $data['desc'],
            'spbill_create_ip' => wei()->request->getServer('SERVER_ADDR', '127.0.0.1'),
        ];
        $data['sign'] = $this->sign($data);

        return $this->call('mmpaymkttransfers/promotion/transfers', $data, true);
    }

    /**
     * 调用微信支付相关接口
     *
     * @param string $url
     * @param array $data
     * @param bool $useCert 是否使用证书
     * @param int $retry 出现SYSTEMERROR错误的重试次数
     * @return array
     */
    public function call($url, $data, $useCert = false, $retry = 1)
    {
        $this->logger->info('Wechat pay data', [
            'url' => $url,
            'data' => $data,
        ]);
        $xml = $this->arrayToXmlString($data);

        // 设置证书
        if ($useCert) {
            if (!is_file($this->sslCertFile) || !is_file($this->sslKeyFile)) {
                return ['code' => -1, 'message' => '请先配置证书'];
            }
            $curlOptions = [
                CURLOPT_SSLCERT => $this->sslCertFile,
                CURLOPT_SSLKEY => $this->sslKeyFile,
            ];
        } else {
            $curlOptions = [];
        }

        $http = $this->http([
            'url' => $this->baseUrl . '/' . $url,
            'method' => 'post',
            'data' => $xml,
            'throwException' => false,
            'curlOptions' => $curlOptions,
        ]);

        if (!$http->isSuccess()) {
            $e = $http->getErrorException();

            return ['code' => $e->getCode(), 'message' => $e->getMessage()];
        }

        $ret = $this->parseData($http->getResponse());
        if ($ret['code'] !== 1) {
            $this->logger->warning($ret['message'], $ret);
        }

        // 根据文档要求,如果是SYSTEMERROR,用相同参数重新调用
        // https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
        if ($ret['err_code'] == 'SYSTEMERROR' && $retry > 0) {
            $this->logger->warning(sprintf('微信支付返回SYSTEMERROR,重试%s次', $retry), [
                'url' => $url,
                'data' => $data,
                'ret' => $ret,
            ]);
            --$retry;

            return $this->call($url, $data, $useCert, $retry);
        } else {
            return $ret;
        }
    }

    /**
     * 解析接口返回,或支付结果通知的数据
     *
     * @param string $data
     * @return array
     */
    public function parseData($data)
    {
        $data = $this->xmlToArray($data);

        // 处理返回结果
        if (!isset($data['return_code']) || $data['return_code'] != 'SUCCESS') {
            $data['code'] = -1;
            $data['message'] = '很抱歉，接口出错：' . (isset($this->messages[$data['return_msg']]) ? $this->messages[$data['return_msg']] : $data['return_msg']);

            return $data;
        }

        // 处理业务结果
        if (!isset($data['result_code']) || $data['result_code'] != 'SUCCESS') {
            $data['code'] = -2;
            $data['message'] = '很抱歉，微信出错：' . $data['err_code_des'];

            return $data;
        }

        $data['code'] = 1;
        $data['message'] = $data['return_msg'];

        return $data;
    }

    /**
     * 签名算法
     *
     * @param array $data
     * @return string
     * @link http://pay.weixin.qq.com/wiki/doc/api/index.php?chapter=4_3
     */
    public function sign(array $data)
    {
        $sign = wei()->wechatApi->generateSign($data) . '&key=' . $this->appKey;

        return strtoupper(md5($sign));
    }

    /**
     * 校验微信POST过来的原生支付数据是否合法
     *
     * @param string $data
     * @return \SimpleXMLElement|false
     */
    public function verifyNativePay($data)
    {
        $data = $this->xmlToArray($data);

        $sign = $data['sign'];
        unset($data['sign']);

        $generatedSign = $this->sign($data);
        if ($sign == $generatedSign) {
            return $data;
        } else {
            $this->logger->info('原生支付校验失败', $data + ['generatedSign' => $generatedSign]);

            return false;
        }
    }

    /**
     * 生成原生支付的XML数据,返回给微信
     *
     * // 通讯错误,如签名校验失败,数据格式错误
     * $api->responseNativePay([
     *     'return_code' => 'FAIL',
     *     'return_msg' => '签名校验失败',
     * ]);
     *
     * // 业务逻辑错误,如订单不存在,生成prepay_id失败
     * $api->responseNativePay([
     *     'result_code' => 'FAIL',
     *     'err_code_des' => '订单不存在',
     * ]);
     *
     * // 校验成功,直接返回预支付ID
     * $api->responseNativePay([
     *     'prepay_id' => $prePayResult['prepay_id'],
     * ]);
     *
     * @param array $data
     * @return string
     */
    public function responseNativePay($data = [])
    {
        // return_msg和err_code_des为空字符的话,提示系统功能繁忙,去掉则能显示支付页面
        $defaults = [
            'return_code' => 'SUCCESS',
            //'return_msg' => '',
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => $this->generateNonceStr(),
            'prepay_id' => '',
            'result_code' => 'SUCCESS',
            //'err_code_des' => ''
        ];
        $data += $defaults;
        $data['sign'] = $this->sign($data);
        $this->logger->info('Response native pay data', $data);

        return $this->arrayToXmlString($data);
    }

    /**
     * 生成指定长度的随机字符串
     *
     * @param int $length
     * @return string
     * @see \Miaoxing\Payment\Payment\WeChatPay::generateNonceStr
     */
    public function generateNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; ++$i) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

    /**
     * Convert XML string to array
     *
     * @param string $xml
     * @return array
     */
    public function xmlToArray($xml)
    {
        // Do not output libxml error messages to screen
        $useErrors = libxml_use_internal_errors(true);
        $array = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_use_internal_errors($useErrors);

        // Fix the issue that XML parse empty data to new SimpleXMLElement object
        return array_map('strval', (array) $array);
    }

    /**
     * Convert array to XML element
     *
     * @param array $array
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement
     * @see \Wei\WeChatApp::arrayToXml
     */
    public function arrayToXml(array $array, SimpleXMLElement $xml = null)
    {
        if ($xml === null) {
            $xml = new SimpleXMLElement('<xml/>');
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (isset($value[0])) {
                    foreach ($value as $subValue) {
                        $subNode = $xml->addChild($key);
                        $this->arrayToXml($subValue, $subNode);
                    }
                } else {
                    $subNode = $xml->addChild($key);
                    $this->arrayToXml($value, $subNode);
                }
            } else {
                // Wrap cdata for non-numeric string
                if (is_numeric($value)) {
                    $xml->addChild($key, $value);
                } else {
                    $child = $xml->addChild($key);
                    $node = dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection($value));
                }
            }
        }

        return $xml;
    }

    /**
     * Convert array to XML string
     *
     * @param array $array
     * @return string
     */
    public function arrayToXmlString($array)
    {
        return $this->arrayToXml($array)->asXML();
    }
}
