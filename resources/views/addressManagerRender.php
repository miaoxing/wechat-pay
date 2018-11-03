<script type="text/html" class="js-address-wechat-tpl">
  <button class="js-address-wechat-select btn btn-success btn-fluid flex-grow-1" type="button">
    从微信选择地址
  </button>
</script>

<?= $block->js() ?>
<script>
  (function () {
    var $doc = $(document);

    // 增加微信按钮
    $doc.on('address:renderList', function (e, address) {
      address.$list.find('.js-address-new').after($('.js-address-wechat-tpl').html());

      require(['plugins/wechat/js/wx'], function (wx) {
        // 选择微信地址
        $doc.off('click.wechatPay').on('click.wechatPay', '.js-address-wechat-select', function () {
          wx.load(function () {
            wx.openAddress({
              success: function (res) {
                createAddress(res, address);
              }
            });
          });
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
          province: res.provinceName,
          city: res.cityName,
          area: res.countryName,
          address: res.detailInfo,
          zipcode: res.postalCode,
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
