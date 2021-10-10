/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

class ProcessFPXPay {
    constructor(key, stripeConnect) {
        this.key = key;
        this.errors = document.getElementById('errors');
        this.stripeConnect = stripeConnect;
    }

    setupStripe = () => {
        this.stripe = Stripe(this.key);

        if(this.stripeConnect)
            this.stripe.stripeAccount = stripeConnect;
        let elements = this.stripe.elements();
        let style = {
            base: {
                // Add your base input styles here. For example:
                padding: '10px 12px',
                color: '#32325d',
                fontSize: '16px',
            },
        };
        this.fpx = elements.create('fpxBank', {style: style, accountHolderType: 'individual',});
        this.fpx.mount("#fpx-bank-element");
        return this;
    };

    handle = () => {
        document.getElementById('pay-now').addEventListener('click', (e) => {
            document.getElementById('pay-now').disabled = true;
            document.querySelector('#pay-now > svg').classList.remove('hidden');
            document.querySelector('#pay-now > span').classList.add('hidden');

            this.stripe.confirmFpxPayment(
                document.querySelector('meta[name=pi-client-secret').content,
                {
                    payment_method: {
                        fpx: this.fpx,
                    },
                    return_url: document.querySelector(
                        'meta[name="return-url"]'
                    ).content,
                }
            );
        });
    };
}

const publishableKey = document.querySelector(
    'meta[name="stripe-publishable-key"]'
)?.content ?? '';

const stripeConnect =
    document.querySelector('meta[name="stripe-account-id"]')?.content ?? '';

new ProcessFPXPay(publishableKey, stripeConnect).setupStripe().handle();
