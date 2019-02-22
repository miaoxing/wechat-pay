/* global Bloodhound */
define(['plugins/product/js/numeric', 'plugins/app/js/bootbox', 'plugins/app/libs/artTemplate/template.min'], function (numeric, bootbox, template) {
  var self = {};

  self.container = $('.product-qrcode-generator');
  self.qrcode = self.container.find('.product-qrcode');
  self.data = [];

  // 最后生成二维码的时间
  self.generateTime = null;

  /**
   * 选择的商品数据
   */
  self.products = {};

  self.newAction = function (options) {
    $.extend(self, options);

    // 显示商品
    for (var i in self.data) {
      if ({}.hasOwnProperty.call(self.data, i)) {
        self.addProduct(self.data[i]);
      }
    }

    // 初始化搜索建议引擎
    var bestProducts = new Bloodhound({
      datumTokenizer: function (d) {
        return Bloodhound.tokenizers.whitespace(d.value);
      },
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      remote: {
        url: $.url('admin/products.json?search=%QUERY', {rows: 10}),
        ajax: {
          global: false,
          success: function () {
            // ignore default tips
          }
        },
        filter: function (result) {
          return result.data;
        }
      }
    });
    bestProducts.initialize();

    // 商品搜索框增加搜索建议
    $('.product-typeahead')
      .typeahead(null, {
        name: 'best-products',
        source: bestProducts.ttAdapter(),
        displayKey: 'name',
        templates: {
          empty: '<div class="empty-product-message">没有找到相关商品</div>',
          suggestion: template.compile($('#product-tpl').html())
        }
      })
      .on('typeahead:selected', function (event, suggestion) {
        self.addProduct(suggestion);
      });

    self.container.on('click', '.remove-product', function () {
      $(this).parents('li:first').fadeOut(function () {
        $(this).remove();
      });

      self.generateQrcode();
    });

    // 选择商品属性
    self.container.on('click', '.sku-selectors li', function () {
      var $this = $(this);
      $this.parent().find('.active').removeClass('active');
      $this.addClass('active');

      self.updatePriceAndQuantity($this.parents('form'));

      self.generateQrcode();
    });

    // 增加商品数量
    self.container.on('click', '.add-quantity', function () {
      var input = $(this).prev();
      var quantity = numeric.add(input.val(), 1);
      var max = parseInt(input.parent().parent().find('.sku-quantity').html(), 10);
      input.val(Math.min(quantity, max));

      self.generateQrcode();
    });

    // 减少商品数量
    self.container.on('click', '.sub-quantity', function () {
      var input = $(this).next();
      input.val(Math.max(1, numeric.sub(input.val(), 1)));

      self.generateQrcode();
    });

    // 定时查询生成的商品是否支付成功
    var INTERVAL_TIME = 3000;
    setInterval(function () {
      if (!self.generateTime) {
        return;
      }

      var products = self.getProducts();

      $.ajax({
        url: $.url('admin/wechat-qrcode-products/check-order'),
        dataType: 'json',
        data: {
          products: products,
          generateTime: self.generateTime
        },
        success: function (ret) {
          if (ret.code !== 1) {
            return;
          }

          if (!ret.paid) {
            return;
          }

          self.generateTime = null;
          bootbox.alert({
            className: 'text-lg',
            message: '您生成的商品已支付成功,5秒后自动关闭'
          });
          var CLOSE_TIME = 5000;
          setTimeout(function () {
            bootbox.hideAll();
          }, CLOSE_TIME);
        }
      });
    }, INTERVAL_TIME);
  };

  self.addProduct = function (product) {
    self.products[product.id] = product;

    var listItem = template.render('qrcode-list-item-tpl', {
      product: product,
      template: template
    });

    $(listItem)
      .prependTo('.product-list-group')
      .fadeIn();

    self.generateQrcode();
  };

  self.updatePriceAndQuantity = function (form) {
    var productId = form.data('id');
    var product = self.products[productId];

    // 获取已选择的参数
    var attrIds = [];
    form.find('.sku-selectors li.active').each(function () {
      attrIds.push($(this).data('id').toString());
    });

    // 当前选择的规格的总库存
    var quantity = 0;

    // 符合当前选择的规格
    var validSkus = [];

    if (attrIds.length === 0) {
      quantity = product.skus[0].quantity;
    } else {
      for (var i in product.skus) {
        if (self.arrayInArray(product.skus[i].attrIds, attrIds)) {
          quantity += parseInt(product.skus[i].quantity, 10);
          validSkus.push(product.skus[i]);
        }
      }
    }

    // 更新库存
    form.find('.sku-quantity').html(quantity);

    // 如果所选数量超过库存,更改为库存数量
    var inputQuantity = parseInt(form.find('.quantity').val(), 10);
    if (inputQuantity > quantity) {
      form.find('.quantity').val(quantity);
    }

    // 更新价格范围
    var displayPrice = form.find('.product-price-range');

    // 一个都没有选中,说明取消了选择
    if (validSkus.length === 0) {
      validSkus = product.skus;
    }

    if (validSkus.length === 1) {
      displayPrice.html(validSkus[0].price);
    } else {
      var min = parseFloat(validSkus[0].price);
      var max = min;
      for (var j in validSkus) {
        if ({}.hasOwnProperty.call(validSkus, j)) {
          var price = parseFloat(validSkus[j].price);
          if (price < min) {
            min = price;
          }
          if (price > max) {
            max = price;
          }
        }
      }

      if (min === max) {
        displayPrice.html(min.toFixed(2));
      } else {
        displayPrice.html(min.toFixed(2) + '~' + max.toFixed(2));
      }
    }

    // 如果只剩下一个规格,说明已经选完了
    form.find('.skuId').val(validSkus[0].id);
  };

  self.generateQrcode = function () {
    // 获取选中的商品
    var products = self.getProducts();

    // 重现渲染二维码
    var img = $.url('admin/wechat-qrcode-products/generate-qrcode', {
      size: 4,
      logoSize: 60,
      products: products
    });
    self.qrcode.attr('src', img);
    self.container.find('.download-qrcode').attr('href', img);
    self.generateTime = self.formatDate(new Date());
  };

  // 判断一个数组是否在另一个数组中
  self.arrayInArray = function (container, array) {
    for (var i in array) {
      if ($.inArray(array[i], container) === -1) {
        return false;
      }
    }
    return true;
  };

  // 获取所有选中商品的信息
  self.getProducts = function () {
    var products = [];
    self.container.find('.product-form').each(function () {
      var data = $(this).serializeArray();
      var obj = {};
      for (var i in data) {
        if ({}.hasOwnProperty.call(data, i)) {
          obj[data[i]['name']] = data[i]['value'];
        }
      }
      products.push(obj);
    });
    return products;
  };

  self.formatDate = function (date) {
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    var hours = date.getHours();
    var minutes = date.getMinutes();
    var seconds = date.getSeconds();
    // eslint-disable-next-line no-magic-numbers
    if (month < 10) {
      month = '0' + month.toString();
    }
    return year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
  };

  return self;
});
