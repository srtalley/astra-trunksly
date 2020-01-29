//version: 1.2.1
jQuery(function($) {
  $(document).ready(function(){
    //show the trunskly plus popup
    $('.trunksly-plus-popup, a[href="#trunksly-plus-popup"]').on('click', function(event) {
      event.preventDefault();
      showEDSModal(this);
    });

    //Remove model on close or clicking outside the box
    $('body').on('click', '.trunksly-plus-modal-background',function(){
      hideDSModal(this);
    });

    $('body').on('click', '.trunksly-plus-close',function(){
      hideDSModal(this);
    });

    //update the single product page "pay with credit card" link with the quantity selected
    if($('.pay-with-cc-product-page').length) {
      $('.single-product .quantity input[type="number"].qty').on('change', function() {
        var new_product_quantity = $(this).val();
        $('.pay-with-cc-product-page a.single_add_to_cart_button').each(function(){
          var current_url = $(this).attr('href');
          current_url = current_url.replace(/((\?|&)quantity\=)[0-9]*/, '$1' + new_product_quantity);
          $(this).attr('href', current_url);
        })
      });
    }

    if($('.woocommerce-checkout').length) {
      var pay_by_cc = param('pay_by_cc');
      if(pay_by_cc == '1') {
        if($('#payment_method_stripe').length){
          $('#payment_method_stripe').click();
        }
      }
    }

    // check if the stripe payment request buttons are visible. If so, hide the pay by 
    // credit card button
    var product_page = $('.single-product');
    if($(product_page).length != 0) {
      stripe_payment_request_check_interval_times_run = 0;
      var stripe_payment_request_check_interval = setInterval(function() {
        stripe_payment_request_check_interval_times_run += 1;
        if(stripe_payment_request_check_interval_times_run <= 3) {
          hide_pay_with_credit_card_button();
        } else {
          clearInterval(stripe_payment_request_check_interval);
        }
      }, 1500);
    }

  }); // end document ready

  // get URL values
  function param(name) {
    return (location.search.split(name + '=')[1] || '').split('&')[0];
  }
  
  // hide pay with credit card button function 
  function hide_pay_with_credit_card_button() {
    if($('#wc-stripe-payment-request-wrapper').is(':visible')) {
      $('.pay-with-cc-product-page').fadeOut();
    }
  }

  function showEDSModal(){

    $('.trunksly-plus-modal').addClass('trunksly-plus-modal-show-bg');

  } // end function showEDSModal

  function hideDSModal(){

    $('.trunksly-plus-modal').removeClass('trunksly-plus-modal-show-bg');

  } // end function hideDSModal
  

});
