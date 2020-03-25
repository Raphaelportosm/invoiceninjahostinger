/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

class ProcessStripePayment {
    constructor(key, usingToken) {
        this.key = key;
        this.usingToken = usingToken;
    }

    setupStripe() {
        this.stripe = Stripe(this.key);
        this.elements = this.stripe.elements();

        return this;
    }

    createElement() {
        this.cardElement = this.elements.create("card");

        return this;
    }

    mountCardElement() {
        this.cardElement.mount("#card-element");

        return this;
    }

    completePaymentUsingToken() {
        let payNowButton = document.getElementById("pay-now-with-token");

        this.stripe
            .handleCardPayment(payNowButton.dataset.secret, {
                payment_method: payNowButton.dataset.token
            })
            .then(result => {
                if (result.error) {
                    return this.handleFailure(result.error.message);
                }

                return this.handleSuccess(result);
            });
    }

    completePaymentWithoutToken() {
        let payNowButton = document.getElementById("pay-now");
        let cardHolderName = document.getElementById("cardholder-name");

        this.stripe
            .handleCardPayment(payNowButton.dataset.secret, this.cardElement, {
                payment_method_data: {
                    billing_details: { name: cardHolderName.value }
                }
            })
            .then(result => {
                if (result.error) {
                    return this.handleFailure(result.error.message);
                }

                return this.handleSuccess(result);
            });
    }

    handleSuccess(result) {
        document.querySelector(
            'input[name="gateway_response"]'
        ).value = JSON.stringify(result.paymentIntent);

        let tokenBillingCheckbox = document.querySelector(
            'input[name="token-billing-checkbox"]'
        );

        if (tokenBillingCheckbox) {
            document.querySelector('input[name="store_card"]').value =
                tokenBillingCheckbox.checked;
        }

        document.getElementById("server-response").submit();
    }

    handleFailure(message) {
        let errors = document.getElementById("errors");

        errors.textContent = "";
        errors.textContent = message;
        errors.hidden = false;
    }

    handle() {
        this.setupStripe();

        if (this.usingToken) {
            document
                .getElementById("pay-now-with-token")
                .addEventListener("click", () => {
                    return this.completePaymentUsingToken();
                });
        }

        if (!this.usingToken) {
            this.createElement().mountCardElement();

            document.getElementById("pay-now").addEventListener("click", () => {
                return this.completePaymentWithoutToken();
            });
        }
    }
}

const publishableKey = document.querySelector(
    'meta[name="stripe-publishable-key"]'
).content;

const usingToken = document.querySelector('meta[name="using-token"]').content;

new ProcessStripePayment(publishableKey, usingToken).handle();
