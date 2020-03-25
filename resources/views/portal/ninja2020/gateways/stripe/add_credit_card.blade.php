@extends('portal.ninja2020.layout.app')
@section('meta_title', ctrans('texts.add_credit_card'))

@push('head')
    <meta name="stripe-publishable-key" content="{{ $gateway->getPublishableKey() }}">
@endpush

@section('header')
    {{ Breadcrumbs::render('payment_methods.add_credit_card') }}
@endsection

@section('body')
    <form action="{{ route('client.payment_methods.store') }}" method="post" id="server_response">
        @csrf
        <input type="hidden" name="company_gateway_id" value="{{ $gateway->gateway_id }}">
        <input type="hidden" name="gateway_type_id" value="1">
        <input type="hidden" name="gateway_response" id="gateway_response">
        <input type="hidden" name="is_default" id="is_default">
    </form>
    <div class="container mx-auto">
        <div class="grid grid-cols-6 gap-4">
            <div class="col-span-6 md:col-start-2 md:col-span-4">
                <div class="alert alert-failure mb-4" hidden id="errors"></div>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ ctrans('texts.add_credit_card') }}
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500" translate>
                            {{ ctrans('texts.authorize_for_future_use') }}
                        </p>
                    </div>
                    <div>
                        <dl>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 flex items-center">
                                <dt class="text-sm leading-5 font-medium text-gray-500 mr-4">
                                    {{ ctrans('texts.name') }}
                                </dt>
                                <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                                    <input class="input" id="cardholder-name" type="text" placeholder="{{ ctrans('texts.name') }}">
                                </dd>
                            </div><div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm leading-5 font-medium text-gray-500">
                                    {{ ctrans('texts.credit_card') }}
                                </dt>
                                <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                                    <div id="card-element"></div>
                                </dd>
                            </div>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm leading-5 font-medium text-gray-500">
                                    {{ ctrans('texts.save_as_default') }}
                                </dt>
                                <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                                    <input type="checkbox" class="form-check" name="proxy_is_default"
                                           id="proxy_is_default"/>
                                </dd>
                            </div>
                            <div class="bg-white px-4 py-5 flex justify-end">
                                <button
                                    type="button"
                                    id="card-button"
                                    data-secret="{{ $intent->client_secret }}"
                                    class="button button-primary">
                                    {{ ctrans('texts.save') }}
                                </button>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
    <script src="https://js.stripe.com/v3/"></script>
    <script src="{{ asset('js/clients/payment_methods/authorize-stripe-card.js') }}"></script>
@endpush
