@extends('header')

@section('content')
    @parent
    @include('accounts.nav', ['selected' => ACCOUNT_MANAGEMENT])

    @include('migration.includes.errors')

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{!! trans('texts.welcome_to_the_new_version') !!}</h3>
        </div>
        <div class="panel-body">
            <h4>Awesome! Please select the company you would like to apply migration.</h4>
            <form action="/migration/companies" method="post" id="auth-form">
                {{ csrf_field() }}
                    
                @foreach($companies as $company)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="companies[]" id="company1" value="{{ $company->id }}" checked>
                    <label class="form-check-label" for="company1">
                        Name: {{ $company->settings->name }} ID: {{ $company->id }}
                    </label>
                </div>
                @endforeach
            </form>
        </div>
        <div class="panel-footer text-right">
            <button onclick="document.getElementById('auth-form').submit();" class="btn btn-primary">{!! trans('texts.continue') !!}</button>
        </div>
    </div>
@stop