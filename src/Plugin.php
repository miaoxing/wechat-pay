<?php

namespace Miaoxing\WechatPay;

class Plugin extends \miaoxing\plugin\BasePlugin
{
    protected $name = '微信支付';

    protected $description = '包含微信支付,商户相关功能';

    public function onAddressManagerRender()
    {
        if (wei()->ua->isWeChat() && wei()->setting('orders.enableWechatAddress')) {
            $this->view->display('wechatPay:addressManagerRender.php');
        }
    }
}
