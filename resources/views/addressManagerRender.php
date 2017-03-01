<script type="text/html" class="js-address-wechat-tpl">
  <button class="js-address-wechat-select btn btn-success btn-fluid flex-grow-1" type="button">
    从微信选择地址
  </button>
</script>

<?= $block('js') ?>
<script>
  (function () {
    var $doc = $(document);

    // 增加微信按钮
    $doc.on('address:renderList', function (e, address) {
      address.$list.find('.js-address-new').after($('.js-address-wechat-tpl').html());

      // 选择微信地址
      $doc.off('click.wechatPay').on('click.wechatPay', '.js-address-wechat-select', function () {
        $.ajax({
          url: $.url('wechat-addresses/sign'),
          dataType: 'json',
          success: function (ret) {
            if (ret.code !== 1) {
              $.msg(ret);
              return;
            }

            WeixinJSBridge.invoke('editAddress', ret.data, function (res) {
              if (res.err_msg == 'edit_address:ok') {
                createAddress(res, address);
              } else if (res.err_msg == 'edit_address:fail') {
                // 忽略返回
              } else {
                $.log(JSON.stringify(res));
                alert('很抱歉,获取地址失败,请稍后再试');
              }
            });
          }
        });
      });
    });

    function createAddress(res, address) {
      $.ajax({
        url: $.url('addresses/create'),
        type: 'post',
        dataType: 'json',
        loading: true,
        data: {
          name: res.userName,
          contact: res.telNumber,
          province: res.proviceFirstStageName,
          city: res.addressCitySecondStageName,
          area: res.addressCountiesThirdStageName,
          address: res.addressDetailInfo,
          zipcode: res.addressPostalCode,
          areaId: res.nationalCode,
          source: 2
        },
        success: function (ret) {
          $.msg(ret, function () {
            if (ret.code === 1) {
              address.hideList();
              address.reloadList();
            }
          });
        }
      });
    }
  })();
</script>
<?= $block->end() ?>
