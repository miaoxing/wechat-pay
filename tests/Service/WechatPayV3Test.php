<?php

namespace MiaoxingTest\WechatPay\Service;

class WechatPayV3Test extends \Miaoxing\Plugin\Test\BaseTestCase
{
    /**
     * @dataProvider providerForVerifyNotify
     */
    public function testVerifyNotify($result, $options, $requestContent, $orderNo = '', $outOrderNo = '')
    {
        $wechatPay = $this->getPaymentService($options);

        wei()->request->setContent($requestContent);

        $this->assertEquals($result, $wechatPay->verifyNotify());

        if ($result) {
            $this->assertEquals($orderNo, $wechatPay->getOrderNo());
            $this->assertEquals($outOrderNo, $wechatPay->getOutOrderNo());
        }
    }

    public function providerForVerifyNotify()
    {
        return [
            [
                'result' => true,
                'options' => [
                    'appId' => 'wx2421b1c4370ec43b',
                    'mchId' => '10000100',
                    'appKey' => 'AQSjjZcQwCf1EjJutgIxdsnGCDXA7gAz',
                ],
                'requestContent' => '<xml>
   <appid><![CDATA[wx2421b1c4370ec43b]]></appid>
   <attach><![CDATA[支付测试]]></attach>
   <bank_type><![CDATA[CFT]]></bank_type>
   <fee_type><![CDATA[CNY]]></fee_type>
   <is_subscribe><![CDATA[Y]]></is_subscribe>
   <mch_id><![CDATA[10000100]]></mch_id>
   <nonce_str><![CDATA[5d2b6c2a8db53831f7eda20af46e531c]]></nonce_str>
   <openid><![CDATA[oUpF8uMEb4qRXf22hE3X68TekukE]]></openid>
   <out_trade_no><![CDATA[1409811653]]></out_trade_no>
   <result_code><![CDATA[SUCCESS]]></result_code>
   <return_code><![CDATA[SUCCESS]]></return_code>
   <sign><![CDATA[0C6C5D39ABFE57F913AF40B8F761E575]]></sign>
   <sub_mch_id><![CDATA[10000100]]></sub_mch_id>
   <time_end><![CDATA[20140903131540]]></time_end>
   <total_fee>1</total_fee>
   <trade_type><![CDATA[JSAPI]]></trade_type>
   <transaction_id><![CDATA[1004400740201409030005092168]]></transaction_id>
</xml>',
                'orderNo' => '1409811653',
                'outOrderNo' => '1004400740201409030005092168',
            ],
            [
                'result' => false,
                'options' => [
                    'appId' => 'wx2421b1c4370ec43b',
                    'mchId' => '10000100',
                    'appKey' => 'AQSjjZcQwCf1EjJutgIxdsnGCDXA7gAz',
                ],
                'requestContent' => '<xml>
   <appid><![CDATA[wx2421b1c4370ec43b]]></appid>
   <attach><![CDATA[支付测试]]></attach>
   <bank_type><![CDATA[CFT]]></bank_type>
   <fee_type><![CDATA[CNY]]></fee_type>
   <is_subscribe><![CDATA[Y]]></is_subscribe>
   <mch_id><![CDATA[10000100]]></mch_id>
   <nonce_str><![CDATA[5d2b6c2a8db53831f7eda20af46e531c]]></nonce_str>
   <openid><![CDATA[oUpF8uMEb4qRXf22hE3X68TekukE]]></openid>
   <out_trade_no><![CDATA[1409811653]]></out_trade_no>
   <result_code><![CDATA[FAIL]]></result_code>
   <return_code><![CDATA[SUCCESS]]></return_code>
   <sign><![CDATA[8E86D1481CE9C006B779DE1AE298E030]]></sign>
   <sub_mch_id><![CDATA[10000100]]></sub_mch_id>
   <time_end><![CDATA[20140903131540]]></time_end>
   <total_fee>1</total_fee>
   <trade_type><![CDATA[JSAPI]]></trade_type>
   <transaction_id><![CDATA[1004400740201409030005092168]]></transaction_id>
</xml>',
            ],
        ];
    }

    protected function getPaymentService(array $options = [])
    {
        $wechatPay = new \services\payments\WechatPayV3(['wei' => $this->wei] + $options);

        return $wechatPay;
    }

    public function testVerifyReturn()
    {
        $this->assertTrue($this->getPaymentService()->verifyReturn());
    }

    public function testSubmit()
    {
        $this->assertSame([], $this->getPaymentService()->submit([]));
    }

    /**
     * @dataProvider providerForCreatePayData
     */
    public function testCreatePayData($options, $testData, $unifiedOrderResult, $result)
    {
        $wechatPay = $this->getPaymentService($options);

        $order = wei()->order()->setData([
            'name' => '订单1',
            'id' => 'w123456789',
            'amount' => '100.00',
        ]);

        $wechatPayApiV3Mock = $this->getMockBuilder('\plugins\wechatPay\services\WechatPayApiV3')->getMock();
        $wechatPayApiV3Mock->expects($this->any())
            ->method('unifiedOrder')
            ->will($this->returnValue($unifiedOrderResult));
        wei()->wechatPayApiV3 = $wechatPayApiV3Mock;

        $this->assertEquals($result, $wechatPay->createPayData($order, $testData));
    }

    public function providerForCreatePayData()
    {
        return [
            [
                'options' => [
                    'appId' => 'wx2421b1c4370ec43b',
                    'mchId' => '10000100',
                    'appKey' => 'AQSjjZcQwCf1EjJutgIxdsnGCDXA7gAz',
                ],
                'testData' => [
                    'timestamp' => '1422931172',
                    'nonceStr' => 'ZgRbT5idCXFK5tIo',
                ],
                'unifiedOrderResult' => [
                    'code' => 1,
                    'message' => '统一下单成功',
                ],
                'result' => [
                    'code' => 1,
                    'message' => '统一下单成功',
                    'js' => [
                        'appId' => 'wx2421b1c4370ec43b',
                        'timeStamp' => '1422931172',
                        'nonceStr' => 'ZgRbT5idCXFK5tIo',
                        'package' => 'prepay_id=',
                        'signType' => 'MD5',
                        'paySign' => '146891DDFEA70C042EFF034E40DBFAB7',
                    ],
                    'type' => 'js',
                ],
            ],
            [
                'options' => [
                    'appId' => 'wx2421b1c4370ec43b',
                    'mchId' => '10000100',
                    'appKey' => 'AQSjjZcQwCf1EjJutgIxdsnGCDXA7gAz',
                ],
                'testData' => [
                    'timestamp' => '1422931172',
                    'nonceStr' => 'ZgRbT5idCXFK5tIo',
                ],
                'unifiedOrderResult' => [
                    'code' => -1,
                    'message' => '统一下单失败',
                ],
                'result' => [
                    'code' => -1,
                    'message' => '统一下单失败',
                ],
            ],
        ];
    }
}
