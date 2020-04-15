@extends('portal.ninja2020.layout.app')
@section('meta_title', ctrans('texts.view_invoice'))

@push('head')
    <meta name="pdf-url" content="{{ asset($invoice->pdf_url()) }}">
    <script src="{{ asset('js/vendor/pdf.js/pdf.min.js') }}"></script>
@endpush

@section('header')
    {{ Breadcrumbs::render('invoices.show', $invoice) }}
@endsection

@section('body')

    @if($invoice->isPayable() && !empty($client->getSetting('custom_message_unpaid_invoice')))
        @component('portal.ninja2020.components.message')
            {!! CustomMessage::client($client)
                ->company($client->company)
                ->entity($invoice)
                ->message($client->getSetting('custom_message_unpaid_invoice')) !!}
        @endcomponent
    @endif

    @if(!$invoice->isPayable() && !empty($client->getSetting('custom_message_paid_invoice')))
        @component('portal.ninja2020.components.message')
            {!! CustomMessage::client($client)
                ->company($client->company)
                ->entity($invoice)
                ->message($client->getSetting('custom_message_paid_invoice')) !!}
        @endcomponent
    @endif

    @if($invoice->isPayable())
        <form action="{{ route('client.invoices.bulk') }}" method="post">
            @csrf
            <div class="bg-white shadow sm:rounded-lg mb-4" translate>
                <div class="px-4 py-5 sm:p-6">
                    <div class="sm:flex sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                {{ ctrans('texts.unpaid') }}
                            </h3>
                            <div class="mt-2 max-w-xl text-sm leading-5 text-gray-500">
                                <p translate>
                                    {{ ctrans('texts.invoice_still_unpaid') }}
                                    <!-- This invoice is still not paid. Click the button to complete the payment. -->
                                </p>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-0 sm:ml-6 sm:flex-shrink-0 sm:flex sm:items-center">
                            <div class="inline-flex rounded-md shadow-sm">
                                <input type="hidden" name="invoices[]" value="{{ $invoice->hashed_id }}">
                                <input type="hidden" name="action" value="payment">
                                <button class="button button-primary">@lang('texts.pay_now')</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <div class="flex items-center justify-between">
        <section class="flex items-center">
            <div class="items-center" style="display: none" id="pagination-button-container">
                <button class="input-label focus:outline-none hover:text-blue-600 transition ease-in-out duration-300" id="previous-page-button" title="Previous page">
                    <svg class="w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button class="input-label focus:outline-none hover:text-blue-600 transition ease-in-out duration-300" id="next-page-button" title="Next page">
                    <svg class="w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
            <span class="text-sm text-gray-700 ml-2">{{ ctrans('texts.page') }}: 
                <span id="current-page-container"></span>
                <span>{{ strtolower(ctrans('texts.of')) }}</span>
                <span id="total-page-container"></span>
            </span>
        </section>
        <div x-data="{ open: false }" @keydown.escape="open = false" @click.away="open = false" class="relative inline-block text-left">
            <div>
                <button @click="open = !open" class="flex items-center text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                </svg>
                </button>
            </div>
            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg">
                <div class="rounded-md bg-white shadow-xs">
                <div class="py-1">
                    <a target="_blank" href="{{ asset($invoice->pdf_url()) }}" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900">{{ ctrans('texts.open_in_new_tab') }}</a>
                </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-center">
        <canvas id="pdf-placeholder" class="shadow rounded-lg bg-white mt-4 p-4"></canvas>
    </div>
@endsection

@section('footer')
    <script src="{{ asset('js/clients/shared/pdf.js') }}"></script>
@endsection
