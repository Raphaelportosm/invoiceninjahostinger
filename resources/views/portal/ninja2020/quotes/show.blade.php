@extends('portal.ninja2020.layout.app')
@section('meta_title', ctrans('texts.entity_number_placeholder', ['entity' => ctrans('texts.quote'), 'entity_number' => $quote->number]))

@push('head')
    <meta name="pdf-url" content="{{ asset($quote->pdf_file_path(null, 'url', true)) }}">
    <script src="{{ asset('js/vendor/pdf.js/pdf.min.js') }}"></script>

    <meta name="show-quote-terms" content="{{ $settings->show_accept_quote_terms ? true : false }}">
    <meta name="require-quote-signature" content="{{ $client->company->account->hasFeature(\App\Models\Account::FEATURE_INVOICE_SETTINGS) && $settings->require_quote_signature }}">

    @include('portal.ninja2020.components.no-cache')

    <script src="{{ asset('vendor/signature_pad@2.3.2/signature_pad.min.js') }}"></script>
@endpush

@section('body')

    @if(!$quote->isApproved() && $client->getSetting('custom_message_unpaid_invoice'))
        @component('portal.ninja2020.components.message')
            {{ $client->getSetting('custom_message_unpaid_invoice') }}
        @endcomponent
    @endif

    @if($quote->status_id === \App\Models\Quote::STATUS_SENT)
        <div class="mb-4">
            @include('portal.ninja2020.quotes.includes.actions', ['quote' => $quote])
        </div>
    @elseif($quote->status_id === \App\Models\Quote::STATUS_APPROVED)
        <p class="text-right text-gray-900 text-sm mb-4">{{ ctrans('texts.approved') }}</p>
    @else
        <p class="text-right text-gray-900 text-sm mb-4">{{ ctrans('texts.quotes_with_status_sent_can_be_approved') }}</p>
    @endif

    @include('portal.ninja2020.components.entity-documents', ['entity' => $quote])
    @include('portal.ninja2020.components.pdf-viewer', ['entity' => $quote])
    @include('portal.ninja2020.invoices.includes.terms', ['entities' => [$quote], 'entity_type' => ctrans('texts.quote')])
    @include('portal.ninja2020.invoices.includes.signature')
@endsection

@section('footer')
    <script src="{{ asset('js/clients/quotes/approve.js') }}"></script>
@endsection
