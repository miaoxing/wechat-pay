<?php $view->layout() ?>

<?= $block->css() ?>
<style>
  .sidebar {
    display: none;
  }

  .sidebar + .main-content {
    margin-left: 0;
  }
</style>
<?= $block->end() ?>

<div class="page-header">
  <h1>
    微信二维码订单
  </h1>
</div>

<div class="row">
  <div class="col-10 offset-1 col-sm-6 offset-sm-3">
    <form class="js-order-qrcode-form form-horizontal" method="post" role="form">
      <div class="input-group input-group-lg">
        <input type="tel" class="js-amount form-control" name="amount" placeholder="请输入订单金额">
        <span class="input-group-append">
          <button class="btn btn-secondary btn-primary" type="submit">生成二维码</button>
        </span>
      </div>
      <!-- /input-group -->
    </form>
    <div class="js-qrcode-container display-none mt-4">
      <img class="js-qrcode img-fluid" src="">

      <div class="mt-4">
        <a class="js-download-qrcode btn btn-primary btn-lg btn-block" href="" download="微信二维码订单">下载</a>
      </div>
    </div>
  </div>
</div>

<?= $block->js() ?>
<script>
  // 设置提示为居中
  $.tips.defaults.valign = 'middle';
  require(['plugins/app/js/bootbox', 'plugins/admin/js/form'], function (bootbox) {
    var isBorn = false;
    var orderId = null;
    $('.js-order-qrcode-form').ajaxForm({
      url: $.url('admin/wechat-qrcode-orders/create'),
      dataType: 'json',
      success: function (ret) {
        $.msg(ret);
        if (ret.code !== 1) {
          return;
        }

        orderId = ret.orderId;
        isBorn = true;

        // 重现渲染二维码
        var img = $.url('admin/wechat-qrcode-products/generate-qrcode', {
          size: 6,
          logoSize: 60,
          wechatProductId: ret.wechatProductId
        });
        $('.js-qrcode').attr('src', img);
        $('.js-download-qrcode').attr('href', img);
        $('.js-qrcode-container').fadeIn();
      }
    });

    var ship = function () {
      $.ajax({
        url: $.url('admin/orders/ship'),
        data: {
          id: orderId,
          logisticsId: <?= $selfPickupId ?>,
          _format:'json'
        },
        success: function (ret) {
          $.msg(ret);
        }
      });
    };

    // 定时查询订单是否支付成功

    setInterval(function () {
      if (!isBorn) {
        return;
      }

      $.ajax({
        url: $.url('admin/orders/show?_format=json'),
        data: {
          id: orderId
        },
        success: function (ret) {
          if (ret.code === 1 && ret.data.paid == '1') {
            isBorn = false;
            bootbox.confirm({
              buttons: {
                confirm: {
                  label: '确认',
                  className: 'btn-success'
                },
                cancel: {
                  label: '取消',
                  className: 'btn-secondary'
                }
              },
              callback: function (result) {
                if (result) {
                  ship();
                  bootbox.hideAll();
                } else {
                  bootbox.hideAll();
                }
              },
              className: 'text-lg',
              message: '订单支付成功,10秒后自动关闭并确认发货,点击确定马上关闭并确认发货,点击取消马上关闭并取消发货'
            });
            setTimeout(function () {
              ship();
              bootbox.hideAll();
            }, 10000);
          }
        }
      });
    }, 3000);
  });
</script>
<?= $block->end() ?>
