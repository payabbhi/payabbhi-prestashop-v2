document.addEventListener('DOMContentLoaded', function(event) {

  var conditionsForm = document.getElementById('conditions-to-approve');
  var submitButton = document.getElementById('payment-confirmation');
  var customSubmitButton = document.createElement('button');
  var baseClass = 'btn btn-primary center-block ';
  customSubmitButton.id = 'pay-button';

  if (!submitButton) {
    return;
  }

  var style =
    '\
    <style>\
    #pay-button.shown{\
      display: block;\
    }\
    #pay-button.not-shown{\
      display: none;\
    }\
    #pay-button.shown+#payment-confirmation {\
      display:none !important;\
    }\
    </style>';

  customSubmitButton.innerHTML = 'Pay via Payabbhi' + style;
  customSubmitButton.className = baseClass + 'not-shown';
  conditionsForm.insertAdjacentElement('afterend', customSubmitButton);

  var intervalId = null;

  var contactNumber = (function() {
    for (var i in prestashop.customer.addresses) {
      var address = prestashop.customer.addresses[i];
      if (address.phone !== '') {
        return address.phone;
      }
      if (address.phone_mobile !== '') {
        return address.phone_mobile;
      }
    }
    return '';
  })();

  // Pay button handler
  customSubmitButton.addEventListener('click', function(event) {
    var defaults = window.checkout_vars;
    var customer = prestashop.customer;
    options = {
      name: prestashop.shop.name,
      amount: defaults.amount,
      description: defaults.description,
      name: defaults.name,
      access_id: defaults.key,
      prefill: {
        name: customer.firstname + ' ' + customer.lastname,
        email: customer.email,
        contact: contactNumber,
      },
      order_id: defaults.payabbhi_order_id,
      notes: {
        merchant_order_id: defaults.cart_id
      },
      handler: function(obj) {
        clearInterval(intervalId);

        // Search payment form with validation action
        var form = document.querySelector(
          'form[id=payment-form][action$="payabbhi/validation"]'
        );

        var action = form.getAttribute('action');

        form.setAttribute(
          'action',
          action + '?payment_id=' + obj.payment_id
        );

        let payment_signature = document.createElement("INPUT");
        Object.assign(payment_signature, {"type" : "hidden", "name" : "payment_signature", "value" : obj.payment_signature});

        form.appendChild(payment_signature);

        submitButton.getElementsByTagName('button')[0].click();
      },
    };
    var checkout = new Payabbhi(options);
    checkout.open();
  });

  var parent = document.querySelector('#checkout-payment-step');

  parent.addEventListener(
    'change',
    function(e) {
      var target = e.target;
      var type = target.type;

      // This will toggle between PrestaShop and Custom 'PAY' button
      if (
        (target.getAttribute('data-module-name') && type === 'radio') ||
        type === 'checkbox'
      ) {
        var selected = this.querySelector('input[data-module-name="payabbhi"]')
          .checked;

        if (selected) {
          customSubmitButton.className = baseClass + 'shown';
        } else {
          customSubmitButton.className = baseClass + 'not-shown';
        }

        // This will disable/enable 'PAY' button based on conditon checked/unchecked
        customSubmitButton.disabled = !!document.querySelector(
          'input[name^=conditions_to_approve]:not(:checked)'
        );
      }
    },
    true
  );
});
