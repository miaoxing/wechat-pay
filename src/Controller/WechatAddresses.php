<?php

namespace Miaoxing\WechatPay\Controller;

class WechatAddresses extends \miaoxing\plugin\BaseController
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
