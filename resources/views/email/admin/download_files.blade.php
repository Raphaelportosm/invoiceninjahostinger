@component('email.template.master', ['design' => 'light'])

@slot('header')
    @component('email.components.header', ['p' => '', 'logo' => $url])
    	@lang('texts.download')
    @endcomponent

@endslot

@slot('greeting')
@endslot

@lang('texts.download_timeframe')

@slot('signature')
    InvoiceNinja (contact@invoiceninja.com)
@endslot

@slot('footer')
    @component('email.components.footer', ['url' => 'https://invoiceninja.com', 'url_text' => '&copy; InvoiceNinja'])
        For any info, please visit InvoiceNinja.
    @endcomponent
@endslot

@endcomponent