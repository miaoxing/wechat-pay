<?php

namespace Miaoxing\WechatPay\Controller;

class WechatAddresses extends \Miaoxing\Plugin\BaseController
{
    public function signAction()
    {
        $wechatPay = wei()->payment()->createCurrentWechatPayService();

        $data = $wechatPay->createAddressData([
            'url' => $this->request->getReferer(),
        ]);

        return $this->ret($data);
    }
}
