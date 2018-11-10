@extends('layouts.master', ['header' => $header])

@section('body')
<main class="main" id="app">
    <!-- Breadcrumb-->
    {{ Breadcrumbs::render('clients.edit', $client) }}

<client-edit-form v-bind:clientdata="{{ $client }}"></client-edit-form>

</main>

@endsection