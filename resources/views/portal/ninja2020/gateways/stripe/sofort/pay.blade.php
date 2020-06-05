@extends('portal.ninja2020.layout.app')
@section('meta_title', ctrans('texts.pay_now'))

@push('head')
    <meta name="stripe-publishable-key" content="{{ $gateway->getPublishableKey() }}">
    <meta name="return-url" content="{{ $return_url }}">
    <meta name="amount" content="{{ $amount }}">
@endpush

@section('body')
<div class="container mx-auto">
    <div class="grid grid-cols-6 gap-4">
        <div class="col-span-6 md:col-start-2 md:col-span-4">
            <div class="alert alert-failure mb-4" hidden id="errors"></div>
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        {{ ctrans('texts.pay_now') }}
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500" translate>
                        {{ ctrans('texts.complete_your_payment') }}
                    </p>
                </div>
                <form action="#" method="POST" id="pay-now">
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 flex items-center">
                        <dt class="text-sm leading-5 font-medium text-gray-500 mr-4">
                            {{ ctrans('texts.sofort') }} ({{ ctrans('texts.bank_transfer') }})
                        </dt>
                        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ ctrans('texts.amount') }}: <span class="font-bold">{{ $amount }}</span>
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 flex justify-end">
                        <button class="button button-primary">
                            {{ ctrans('texts.pay_now') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('footer')
    <script src="https://js.stripe.com/v3/"></script>
    <script src="{{ asset('js/clients/payments/process-sofort.js') }}"></script>
@endpush