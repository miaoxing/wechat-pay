<?php

namespace MiaoxingTest\WechatPay\Service;

class WechatPayTest extends \Miaoxing\Plugin\Test\BaseTestCase
{
    /**
     * @dataProvider providerForVerifyNativePay
     */
    public function testVerifyNativePay($options, $postData)
    {
        $wechatPay = $this->getPaymentService();

        $wechatPay->setOption($options);

        $this->assertNotFalse($wechatPay->verifyNativePay($postData));
    }

    public function providerForVerifyNativePay()
    {
        return [
            [
                'options' => [
                    'appKey' => '8jUmluPW7PGmPXtwDxnagFI2BlA9FE45aiToD8g0fAP1oswAjAHmTvwZkCJpQMjXrzcMY6jtDaMUwNaOIB6olqKypJqAvgxoDGTa4rHauML4MzJKjsVD8sNtkCdIRPNd',
                ],
                'postData' => '<xml><OpenId><![CDATA[odD__tqEg0FqgI9h_Qm-pIB4YSAw]]></OpenId>
<AppId><![CDATA[wxf3d22a007388393b]]></AppId>
<IsSubscribe>1</IsSubscribe>
<ProductId><![CDATA[1]]></ProductId>
<TimeStamp>1396528390</TimeStamp>
<NonceStr><![CDATA[oyweaBJEUSPmhSPj]]></NonceStr>
<AppSignature><![CDATA[344ac3bee55bba4cbce6f355508ce07c9d36ded1]]></AppSignature>
<SignMethod><![CDATA[sha1]]></SignMethod>
</xml>',
            ],
            [
                'options' => [
                    'appKey' => '8jUmluPW7PGmPXtwDxnagFI2BlA9FE45aiToD8g0fAP1oswAjAHmTvwZkCJpQMjXrzcMY6jtDaMUwNaOIB6olqKypJqAvgxoDGTa4rHauML4MzJKjsVD8sNtkCdIRPNd',
                ],
                'postData' => '<xml><OpenId><![CDATA[odD__tqEg0FqgI9h_Qm-pIB4YSAw]]></OpenId>
<AppId><![CDATA[wxf3d22a007388393b]]></AppId>
<IsSubscribe>1</IsSubscribe>
<ProductId><![CDATA[1]]></ProductId>
<TimeStamp>1396582500</TimeStamp>
<NonceStr><![CDATA[IkHxQY6rGtAib1f0]]></NonceStr>
<AppSignature><![CDATA[abc310d13f8cf6295ce98ab121141d07d8ece9f0]]></AppSignature>
<SignMethod><![CDATA[sha1]]></SignMethod>
</xml>',
            ],
        ];
    }

    /**
     * @dataProvider providerForNativePayUrl
     */
    public function testCreateNativePayUrl($url, $signData)
    {
        $wechatPay = $this->getPaymentService();

        $wechatPay->setOption([
            'appId' => $signData['appid'],
            'appKey' => $signData['appkey'],
        ]);

        $nativePayUrl = $wechatPay->createNativePayUrl($signData['productid'], $signData);

        $this->assertEquals($nativePayUrl, $url);
    }

    public function providerForNativePayUrl()
    {
        return [
            [
                'url' => 'weixin://wxpay/bizpayurl?appid=wxf8b4f85f3a794e77&noncestr=adssdasssd13d&productid=123456&sign=18c6122878f0e946ae294e016eddda9468de80df&timestamp=189026618',
                'signData' => [
                    'appid' => 'wxf8b4f85f3a794e77',
                    'appkey' => '2Wozy2aksie1puXUBpWD8oZxiD1DfQuEaiC7KcRATv1Ino3mdopKaPGQQ7TtkNySuAmCaDCrw4xhPY5qKTBl7Fzm0RgR3c0WaVYIXZARsxzHV2x7iwPPzOz94dnwPWSn',
                    'noncestr' => 'adssdasssd13d',
                    'productid' => '123456',
                    'timestamp' => '189026618',
                ],
            ],
            [
                'url' => 'weixin://wxpay/bizpayurl?appid=wxf8b4f85f3a794e77&noncestr=eRv4hlifJ2ebLoLn&productid=234234&sign=73bd13007a583563ce03a1cdd0c17bf978fa4b3c&timestamp=1395903140',
                'signData' => [
                    'appid' => 'wxf8b4f85f3a794e77',
                    'appkey' => '2Wozy2aksie1puXUBpWD8oZxiD1DfQuEaiC7KcRATv1Ino3mdopKaPGQQ7TtkNySuAmCaDCrw4xhPY5qKTBl7Fzm0RgR3c0WaVYIXZARsxzHV2x7iwPPzOz94dnwPWSn',
                    'productid' => '234234',
                    'timestamp' => '1395903140',
                    'noncestr' => 'eRv4hlifJ2ebLoLn',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForVerifyNotify
     */
    public function testVerifyNotify($postData, $queries, $options, $result)
    {
        $wechatPay = $this->getPaymentService();

        $wechatPay->setOption($options);

        $GLOBALS['HTTP_RAW_POST_DATA'] = $postData;
        $gets = &wei()->request->getParameterReference('get');
        $gets = $queries;

        $notifyResult = $wechatPay->verifyNotify();

        $this->assertSame($notifyResult, $result);
    }

    public function providerForVerifyNotify()
    {
        return [
            [
                'postData' => '<xml>
     <OpenId><![CDATA[111222]]></OpenId>
     <AppId><![CDATA[wwwwb4f85f3a797777]]></AppId>
     <IsSubscribe>1</IsSubscribe>
     <TimeStamp>1369743511</TimeStamp>
     <NonceStr><![CDATA[jALldRTHAFd5Tgs5]]></NonceStr>
     <AppSignature><![CDATA[42cfef0b6e13c611352c2b0cdd2e825d58a03813]]></AppSignature>
     <SignMethod><![CDATA[sha1]]></SignMethod></xml>',
                'queries' => [
                    'discount' => '0',
                    'fee_type' => '1',
                    'bank_billno' => '206064184488',
                    'bank_type' => '0',
                    'input_charset' => 'GBK',
                    'notify_id' => 'WE37gwCoFBcAKdkH34Y1nW94r_vao2ljmwE3oAHEeAP690xSVhRleOMfhsgjwVGDpluT-vdS79kbDbkDnjYg4qsmTdSjuJxl',
                    'out_trade_no' => '843254536943809900',
                    'partner' => '1900000109',
                    'product_fee' => '1',
                    'sign_type' => 'MD5',
                    'time_end' => '20130606015331',
                    'total_fee' => '1',
                    'trade_mode' => '1',
                    'trade_state' => '0',
                    'transaction_id' => '1900000109201306060282555397',
                    'transport_fee' => '0',
                    'sign' => '8F55C19AD70BA7673158090BCFE8F6DF',
                ],
                'options' => [
                    'appKey' => '8jUmluPW7PGmPXtwDxnagFI2BlA9FE45aiToD8g0fAP1oswAjAHmTvwZkCJpQMjXrzcMY6jtDaMUwNaOIB6olqKypJqAvgxoDGTa4rHauML4MzJKjsVD8sNtkCdIRPNd',
                ],
                'result' => true,
            ],
        ];
    }

    /**
     * @dataProvider providerForCreateJsPayJson
     */
    public function testCreateJsPayJson($options, $params, $json, $jsonResult)
    {
        $wechatPay = $this->getPaymentService();

        $wechatPay->setOption($options);

        $payData = $wechatPay->createJsPayJson($params, $json);

        $this->assertEquals(json_decode($jsonResult, true), $payData);
    }

    public function providerForCreateJsPayJson()
    {
        return [
            [
                'options' => [
                    'appId' => 'wxf8b4f85f3a794e77',
                    'appKey' => '2Wozy2aksie1puXUBpWD8oZxiD1DfQuEaiC7KcRATv1Ino3mdopKaPGQQ7TtkNySuAmCaDCrw4xhPY5qKTBl7Fzm0RgR3c0WaVYIXZARsxzHV2x7iwPPzOz94dnwPWSn',
                    'partnerId' => '1900000109',
                    'partnerKey' => '8934e7d15453e97507ef794cf7b0519d',
                ],
                'params' => [
                    'body' => 'test',
                    'total_fee' => '1',
                    'input_charset' => 'GBK',
                    'notify_url' => 'htttp://www.baidu.com',
                    'out_trade_no' => 'Z6bJkHVtD0Ood6PS',
                    'spbill_create_ip' => '127.0.0.1',
                ],
                'json' => [
                    'timeStamp' => '1395449191',
                    'nonceStr' => 'xV2aGOGYJy2OKfVy',
                ],
                'jsonResult' => '{"appId":"wxf8b4f85f3a794e77","package":"bank_type=WX&body=test&fee_type=1&input_charset=GBK&notify_url=htttp%3A%2F%2Fwww.baidu.com&out_trade_no=Z6bJkHVtD0Ood6PS&partner=1900000109&spbill_create_ip=127.0.0.1&total_fee=1&sign=C78BA309C6DF1C30E9F51BF7449B2CC8","timeStamp":"1395449191","nonceStr":"xV2aGOGYJy2OKfVy","paySign":"4a21de9d75a6d8af9a4eae771135faa8479cb26f","signType":"sha1"}',
            ],
            [
                'options' => [
                    'appId' => 'wxf8b4f85f3a794e77',
                    'appKey' => '2Wozy2aksie1puXUBpWD8oZxiD1DfQuEaiC7KcRATv1Ino3mdopKaPGQQ7TtkNySuAmCaDCrw4xhPY5qKTBl7Fzm0RgR3c0WaVYIXZARsxzHV2x7iwPPzOz94dnwPWSn',
                    'partnerId' => '1900000109',
                    'partnerKey' => '8934e7d15453e97507ef794cf7b0519d',
                ],
                'params' => [
                    'body' => 'test',
                    'total_fee' => '1',
                    'input_charset' => 'GBK',
                    'notify_url' => 'htttp://www.baidu.com',
                    'out_trade_no' => 'NOACtRJ9e0SJSrzm',
                    'spbill_create_ip' => '127.0.0.1',
                ],
                'json' => [
                    'timeStamp' => '1395456065',
                    'nonceStr' => 'cY0VkCNlcOxAPiRH',
                ],
                'jsonResult' => '{"appId":"wxf8b4f85f3a794e77","package":"bank_type=WX&body=test&fee_type=1&input_charset=GBK&notify_url=htttp%3A%2F%2Fwww.baidu.com&out_trade_no=NOACtRJ9e0SJSrzm&partner=1900000109&spbill_create_ip=127.0.0.1&total_fee=1&sign=7F346782FBD5BD96B08CA0189CBC169B","timeStamp":"1395456065","nonceStr":"cY0VkCNlcOxAPiRH","paySign":"374ca0af6142e520c0bf34b7921e08b9e652ce59","signType":"sha1"}',
            ],
        ];
    }

    protected function getPaymentService()
    {
        $wechatPay = new \Miaoxing\Payment\Payment\WeChatPay(['wei' => $this->wei]);

        return $wechatPay;
    }
}
