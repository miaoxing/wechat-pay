<?php

namespace Miaoxing\WechatPay\Controller\Admin;

use plugins\cart\services\Cart;

class WechatQrcodeProducts extends \miaoxing\plugin\BaseController
{
    /**
     * 选择商品,展示原生支付二维码
     */
    public function newAction($req)
    {
        $products = wei()->product()->findAll(['id' => $req['productIds']]);

        $data = [];
        foreach ($products as $product) {
            $data[] = $product->toArray() + [
                    'skus' => $product->getSkus()->toArray(),
                ];
        }

        return get_defined_vars();
    }

    /**
     * 根据提交的商品信息,生成微信原生支付的二维码
     */
    public function generateQrcodeAction($req)
    {
        // 1. 通过微信支付服务,创建原生支付URL
        $wechatPay = wei()->payment()->createCurrentWechatPayService();

        // 2. 兼容老的products方法
        if (isset($req['products'])) {
            $productId = $wechatPay->encodeProductId($req['products']);
        } else {
            $productId = $req['wechatProductId'];
        }

        // 3. 生成二维码信息
        $req['text'] = $wechatPay->createNativePayUrl($productId);

        // 4. 附加默认服务号的头像
        $account = wei()->wechatAccount->getCurrentAccount();
        $req['logo'] = ltrim($account['headImg'], '/');

        // 5. 内部跳转为二维码生成URL
        $this->app->forward('qrcode', 'show');
    }

    public function checkOrderAction($req)
    {
        // 1. 获取生成二维码之后产生的订单
        $orders = wei()->order()
            ->paid()
            ->andWhere('payTime > ?', $req['generateTime'])
            ->findAll();

        // 2. 如果订单的商品数量和二维码的一样,认为是二维码被支付了
        foreach ($orders as $order) {
            $products = [];
            /** @var Cart $cart */
            foreach ($order->getCarts() as $cart) {
                $products[] = $cart->toArray(['skuId', 'quantity']);
            }

            $equal = $req['products'] == $products;
            $this->logger->info('Check qrcode order result', [
                $req['products'],
                $products,
                $equal,
            ]);

            if ($equal) {
                return $this->suc([
                    'paid' => true,
                ]);
            }
        }

        return $this->suc(['paid' => false]);
    }
}
