<?php

namespace Miaoxing\WechatPay\Controller\Admin;

use Miaoxing\Logistics\Service\Logistics;
use plugins\mall\services\Order;

class WechatQrcodeOrders extends \miaoxing\plugin\BaseController
{
    public function newAction()
    {
        $selfPickupId = Logistics::ID_SELF_PICKUP;

        return get_defined_vars();
    }

    public function createAction($req)
    {
        // 1. 检查商品价格是否正确
        if (!wei()->isNumber($req['amount']) || $req['amount'] <= 0) {
            return $this->err('订单价格必须是大于0的数字');
        }

        // 2. 查找或创建微信二维码专属商品
        $product = wei()->product()->findOrInit(['name' => '微信二维码专属商品']);
        if ($product->isNew()) {
            $ret = $product->create([
                'quantity' => 1000000000,
                'price' => $req['amount'],
                'images' => [
                    '/plugins/wechat-pay/images/qrcode.png',
                ],
                'startTime' => '0000-00-00 00:00:00',
                'endTime' => '9999-12-31 23:59:59',
                'visible' => false,
                'detail' => '请勿更改或删除',
            ]);
            if ($ret['code'] !== 1) {
                return $this->ret($ret);
            }

            $firstSku = $product->getFirstSku();
        } else {
            $firstSku = $product->getFirstSku();
            $firstSku->save([
                'price' => $req['amount'],
            ]);
        }

        // 3. 根据商品创建创建订单
        $order = wei()->order();
        $ret = $order->createFromSkus([[
            'skuId' => $firstSku['id'],
            'quantity' => 1,
        ]], [
            'payType' => 'wechatPayV3',
        ], [
            'source' => Order::SOURCE_OFFLINE,
            'createPayData' => false,
            'requireAddress' => false,
        ]);
        if ($ret['code'] !== 1) {
            return $this->ret($ret);
        }

        // 4. 返回订单供生成二维码
        return $this->suc([
            'message' => '生成成功',
            // TODO 通过wechatV3PayApi encode/decode
            'wechatProductId' => '-' . $order['id'],
            'orderId' => $order['id'],
        ]);
    }
}
