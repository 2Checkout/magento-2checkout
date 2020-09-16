const $jq = jQuery.noConflict();
let tcoCardElementLoaded = false;
let tcoJsPaymentClient, tcoComponent;

document.observe('dom:loaded', function () {
    billing.onComplete = billing.onComplete.wrap(function (callOriginalFunction) {
        isDomReadyFor2PayJs();
        return callOriginalFunction();
    });

    payment.save = payment.save.wrap(
        function (callOriginal) {

            if (payment.currentMethod !== 'twocheckout') {
                return callOriginal();
            }
            let firstname = $jq('input[name ="billing[firstname]"]').val(),
                lastname = $jq('input[name ="billing[lastname]"]').val(),
                customerName = firstname + ' ' + lastname,
                selectedBillingAddress = $jq('#billing-progress-opcheckout address').html().split('<br>');

            // there is no input that we use to take the selected billing address so we need
            // to parse the DOM and retrieve it, with a fallback on the default customer name
            if (typeof selectedBillingAddress === 'object'
                && selectedBillingAddress.length
                && selectedBillingAddress[0].length) {
                customerName = selectedBillingAddress[0];
            }
            const billingDetails = {name: customerName};

            $jq('.validation-message').hide();
            if (!billingDetails.name.length) {
                $jq('.validation-message').show();
                return;
            }
            tcoStartLoading();
            tcoJsPaymentClient.tokens.generate(tcoComponent, billingDetails).then(function (response) {
                if (response && response.hasOwnProperty('token')) {
                    $jq('#ess_token').val(response.token);
                    tcoStopLoading();
                    return callOriginal();
                } else {
                    alert('Your payment can not be processed. Please try again or contact our team!');
                    return false;
                }
            }).catch(function (error) {
                tcoStopLoading();
                console.error('API ' + error);
                if (error == 'Error: Target window is closed') {
                    location.reload();
                }
                alert(error);
            });

        }
    );

    if (typeof shippingMethod !== 'undefined') {
        shippingMethod.onComplete = shippingMethod.onComplete.wrap(function (callShippingMethodFunction) {
            isDomReadyFor2PayJs();
            return callShippingMethodFunction();
        });
    }
});

function isDomReadyFor2PayJs() {

    if (!tcoCardElementLoaded) {
        if ($jq('#card-element').length) {
            tcoCardElementLoaded = true;
        }
        setTimeout(function () {
            isDomReadyFor2PayJs();
        }, 333);
    } else {
        prepareTwoPayJs();
    }
}

function prepareTwoPayJs() {
    $jq('#card-element').html('');
    tcoJsPaymentClient = new TwoPayClient(tcoSellerId);
    $jq('#loadingInfo').remove();
    tcoComponent = tcoJsPaymentClient.components.create('card');
    tcoComponent.mount('#card-element');
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
