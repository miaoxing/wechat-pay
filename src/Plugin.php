<?php

namespace Miaoxing\WechatPay;

use Miaoxing\Payment\Service\Payment;

class Plugin extends \Miaoxing\Plugin\BasePlugin
{
    protected $name = '微信支付';

    protected $description = '包含微信支付,商户相关功能';

    public function onAddressManagerRender()
    {
        if (wei()->ua->isWeChat() && wei()->setting('orders.enableWechatAddress')) {
            $this->view->display('@wechat-pay/addressManagerRender.php');
        }
    }

    public function onPreFindPayments(Payment $payments)
    {
        if (!wei()->ua->isWeChat()) {
            // TODO 更好的判断
            $payments->andWhere("name != '微信支付'");
        }
    }
}
