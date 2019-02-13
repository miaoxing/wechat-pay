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
  <div class="pull-right">
    <a class="btn btn-default" href="<?= $url('admin/products/index') ?>">返回商品列表</a>
  </div>
  <h1>微信二维码商品</h1>
</div>
<!-- /.page-header -->

<div class="row product-qrcode-generator">
  <div class="col-12 col-sm-5 offset-sm-1">
    <h3 class="product-qrcode-generator-title">步骤一，选择商品</h3>

    <div class="well">
      <form class="form-inline" role="form">
        <div class="form-group product-form-group">
          <input type="text" class="form-control product-typeahead" name="search" placeholder="请输入商品名称搜索">
          <div class="clearfix"></div>
        </div>
      </form>
    </div>
    <ul class="list-group product-list-group list-unstyled">

    </ul>
  </div>
  <div class="col-12 col-sm-5">
    <h3 class="product-qrcode-generator-title">步骤二，扫描或下载二维码</h3>
    <img class="product-qrcode img-responsive center" src="">

    <div class="center qrcode-actions">
      <a class="btn btn-primary btn-block download-qrcode" href="" download="商品二维码">下载</a>
    </div>
  </div>
  <!-- PAGE CONTENT ENDS -->
</div><!-- /.col -->
<!-- /.row -->

<script id="qrcode-list-item-tpl" type="text/html">
  <li class="list-group-item">
    <%== template.render('product-tpl', product) %>
    <div class="media-actions">
      <a href="javascript:;" title="删除" class="text-muted remove-product">
        <i class="fa fa-times-circle-o"></i>
      </a>
    </div>
    <form class="form-horizontal product-form" data-id="<%= product.id %>" method="post" role="form">
      <%== template.render('sku-selector-tpl', product) %>
    </form>
  </li>
</script>

<?php require $this->getFile('@product/admin/products/richInfo.php') ?>
<?php require $this->getFile('@product/admin/skus/selector.php') ?>

<?= $block->js() ?>
<script>
  require([
    'plugins/wechat-pay/js/admin/wechat-qrcode-products',
    'css!plugins/wechat-pay/css/admin/wechat-qrcode-products',
    'css!comps/typeahead.js-bootstrap3.less/typeahead',
    'dataTable',
    'form',
    'template',
    'jquery-deparam',
    'comps/typeahead.js/dist/typeahead.bundle.min'
  ], function (qrcode) {
    qrcode.newAction({
      data: <?= json_encode($data) ?>
    });
  });
</script>
<?= $block->end() ?>
