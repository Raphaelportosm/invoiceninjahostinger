@extends('portal.default.layouts.master')
@section('header')

@stop
@section('body')
<main class="main">
    <div class="container-fluid">
{!! Former::framework('TwitterBootstrap4'); !!}

{!! Former::horizontal_open()
    ->id('payment_form')
    ->route('client.payments.process')
    ->method('POST');    !!}

{!! Former::hidden('hashed_ids')->id('hashed_ids')->value($hashed_ids) !!}
{!! Former::hidden('company_gateway_id')->id('company_gateway_id') !!}
{!! Former::hidden('payment_method_id')->id('payment_method_id') !!}

{!! Former::close() !!}

		<div class="row" style="padding-top: 30px;">
            <div class="col d-flex justify-content-center">
                <div class="card w-50 p-10">
                    <div class="card-header">
                        {{ ctrans('texts.payment')}}
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach($invoices as $invoice)
                                <a class="list-group-item list-group-item-action flex-column align-items-start" href="javascript:void(0);">
                                    <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mr-4"># {{ $invoice->number }}</h5>
                                    <small>{{ $invoice->due_date }}</small>
                                    </div>
                                <p class="mb-1 pull-right">{{ $invoice->balance }}</p>
                                <small>
                                    @if($invoice->po_number)
                                    {{ $invoice->po_number }}
                                    @elseif($invoice->public_notes)
                                    {{ $invoice->public_notes }}
                                    @else
                                    {{ $invoice->invoice_date}}
                                    @endif

                                </small>
                                </a>
                            @endforeach
                        </div>

                        <div class="py-md-5">
                            <ul class="list-group">
                                <li class="list-group-item d-flex list-group-item-action justify-content-between align-items-center"><strong>{{ ctrans('texts.total')}}</strong>
                                    <h3><span class="badge badge-primary badge-pill"><strong>{{ $formatted_total }}</strong></span></h3>
                                </li>
                            </ul>
                        </div>


                        <div class="btn-group pull-right" role="group">
                        <button class="btn btn-primary dropdown-toggle" id="pay_now" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ ctrans('texts.pay_now') }}</button>
                        <div class="dropdown-menu" aria-labelledby="pay_now">
                            @foreach($payment_methods as $payment_method)
                                <a class="dropdown-item" onClick="paymentMethod({{ $payment_method['company_gateway_id'] }}, {{ $payment_method['gateway_type_id'] }})">{{$payment_method['label']}}</a>
                            @endforeach
                        </div>
                        </div>

                    </div>
                </div>
            </div>
		</div>
    </div>
</main>










<!-- Terms Modal -->
<div class="modal fade" id="terms_modal" tabindex="-1" role="dialog" aria-labelledby="terms_modal_ttle" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="terms_modal_ttle">{{ ctrans('texts.terms') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        {!! $invoice->terms !!}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ ctrans('texts.close') }}</button>
        <button type="button" class="btn btn-primary" id="terms_accepted">{{ trans('texts.agree_to_terms', ['terms' => trans('texts.invoice_terms')]) }}</button>
      </div>
    </div>
  </div>
</div>

<!-- Authorization / Signature Modal -->
<div class="modal fade" id="signature_modal" tabindex="-1" role="dialog" aria-labelledby="authorizationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="terms_modal_ttle">{{ ctrans('texts.authorization') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="height:400px;">
                <div>
                    {{ trans('texts.sign_here') }}
                </div>
                <div id="signature"></div><br/>
      </div>
      <div class="modal-footer">
        <button id="modal_pay_now_button" type="button" class="btn btn-success" onclick="onModalPayNowClick()" disabled="">
            {{ ctrans('texts.pay_now') }}
        </button>
      </div>
    </div>
  </div>
</div>

</body>
@endsection
@push('css')
<style type="text/css">
    #signature {
        border: 2px dotted black;
        background-color:lightgrey;
    }
</style>
@endpush
@push('scripts')
<script src="/vendors/js/jSignature.min.js"></script>

<script type="text/javascript">

var terms_accepted = false;

$('#pay_now').on('click', function(e) {
    //check if terms must be accepted

    @if($settings->show_accept_invoice_terms)
        $('#terms_modal').modal('show');
    @endif

    @if($settings->require_invoice_signature)
        getSignature();
    @endif
});

$('#terms_accepted').on('click', function(e){

        terms_accepted = true;

        $('#terms_modal').modal('hide');


    //push to payment
    

});

$("#modal_pay_now_button").on('click', function(e){
    
    //disable to prevent firing twice
    $("#modal_pay_now_button").attr("disabled", true);


});

    function paymentMethod(company_gateway_id, payment_method_id)
    {
        $('#company_gateway_id').val(company_gateway_id);
        $('#payment_method_id').val(payment_method_id);
        $('#payment_form').submit();

    }


    function getSignature()
    {
        //check in signature is required 
        $("#signature").jSignature({ 'UndoButton': true, }).bind('change', function(e) {

            if( $("#signature").jSignature('getData', 'native').length >= 1) {
                
                $("#modal_pay_now_button").removeAttr("disabled");

            } else {
                $("#modal_pay_now_button").attr("disabled", true);

            }

        });

        $("#signature").resize();

        $("#signature").jSignature('reset');
        $('#signature_modal').modal();
    }

    function onModalPayNowClick() {
            var data = {
                signature: $('#signature').jSignature('getData', 'svgbase64')[1]
            };
            //var data = false;

    }
    
</script>
@endpush
@section('footer')
@endsection

