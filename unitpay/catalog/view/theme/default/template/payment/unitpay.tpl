<div class="buttons">
  <form method="POST" action="<?php echo $merchant_url; ?>" id='paymentForm'>
  <div class="pull-right">
    <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="btn btn-primary" data-loading-text="<?php echo $text_loading; ?>" />
  </div>
  </form>
</div>
<script type="text/javascript"><!--
  $('#button-confirm').on('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $.ajax({
      type: 'get',
      url: 'index.php?route=payment/unitpay/confirm',
      cache: false,
      success: function() {
        $('#paymentForm').submit();
      }
    });
  });
  //--></script>