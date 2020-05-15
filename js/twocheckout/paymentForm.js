const $jq = jQuery.noConflict();
let cardElementLoaded = false;

document.observe('dom:loaded', function () {
    $jq('#co-shipping-method-form .button').click(function () {
        isDomReadyFor2PayJs();
    });
});

function isDomReadyFor2PayJs() {
    if (!cardElementLoaded) {
        if ($jq('#card-element').length) {
            cardElementLoaded = true;
        }
        setTimeout(function () {
            isDomReadyFor2PayJs();
        }, 333);
    } else {
        prepareTwoPayJs();
    }
}

function prepareTwoPayJs() {

    let jsPaymentClient = new TwoPayClient(tcoSellerId);
    $jq('#loadingInfo').remove();
    let component = jsPaymentClient.components.create('card');
    component.mount('#card-element');
    payment.save = payment.save.wrap(
        function (callOriginal) {

            if (payment.currentMethod !== 'twocheckout') {
                return callOriginal();
            }
            const billingDetails = {name: $jq('#tco_name').val()};

            $jq('.validation-message').hide();
            if (!billingDetails.name.length) {
                $jq('.validation-message').show();
                return;
            }
            tcoStartLoading();
            jsPaymentClient.tokens.generate(component, billingDetails).then((response) => {
                if (response && response.hasOwnProperty('token')) {
                    $jq('#ess_token').val(response.token);
                    tcoStopLoading();
                    return callOriginal();
                } else {
                    alert('Your payment can not be processed. Please try again or contact our team!');
                    return false;
                }
            }).catch((error) => {
                tcoStopLoading();
                alert(error);
                console.error(error);
            });

        }
    );
}

function tcoStartLoading() {
    let btn = $jq('#payment-buttons-container .button');
    btn.attr('disabled', true);
    $jq('#payment-please-wait').show();
    $jq('#tcoApiForm #wait').show();
    $jq('#payment-buttons-container').css('opacity', .5);
}

function tcoStopLoading() {
    let btn = $jq('#payment-buttons-container .button');
    btn.attr('disabled', false);
    $jq('#payment-please-wait').hide();
    $jq('#tcoApiForm #wait').hide();
    $jq('#payment-buttons-container').css('opacity', 1);
}
