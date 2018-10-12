<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

    <!-- Source: https://github.com/invoiceninja/invoiceninja -->
    <!-- Error: {{ session('error') }} -->

    @if (config('services.analytics.tracking_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-122229484-1"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('services.analytics.tracking_id') }}', { 'anonymize_ip': true });
        function trackEvent(category, action) {
            ga('send', 'event', category, action, this.src);
        }
        </script>
    @else
        <script>
        function gtag(){}
        </script>
    @endif

    <meta charset="utf-8">
    <title>@yield('meta_title') | {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description')"/>
    <link href="{{ asset('favicon.png') }}" rel="shortcut icon" type="image/png">

    <!--
    TODO Setup social sharing info
    <meta property="og:site_name" content="Invoice Ninja"/>
    <meta property="og:url" content="{{ config('ninja.site_url') }}"/>
    <meta property="og:title" content="Invoice Ninja"/>
    <meta property="og:image" content="{{ config('ninja.site_url') }}/images/round_logo.png"/>
    <meta property="og:description" content="Create. Send. Get Paid."/>
    --/>

    <!-- http://realfavicongenerator.net -->
    <!--
    TODO Setup favicon
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" href="{{ url('favicon-32x32.png') }}" sizes="32x32">
    <link rel="icon" type="image/png" href="{{ url('favicon-16x16.png') }}" sizes="16x16">
    <link rel="manifest" href="{{ url('manifest.json') }}">
    <link rel="mask-icon" href="{{ url('safari-pinned-tab.svg') }}" color="#3bc65c">
    <link rel="shortcut icon" href="{{ url('favicon.ico') }}">
    <meta name="apple-mobile-web-app-title" content="Invoice Ninja">
    <meta name="application-name" content="Invoice Ninja">
    <meta name="theme-color" content="#ffffff">
    --/>

    <!-- http://stackoverflow.com/questions/19012698/browser-cache-issues-in-laravel-4-application -->
    <meta http-equiv="cache-control" content="max-age=0"/>
    <meta http-equiv="cache-control" content="no-cache"/>
    <meta http-equiv="cache-control" content="no-store"/>
    <meta http-equiv="cache-control" content="must-revalidate"/>
    <meta http-equiv="expires" content="0"/>
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT"/>
    <meta http-equiv="pragma" content="no-cache"/>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="canonical" href="{{ config('ninja.app_url') }}/{{ request()->path() }}"/>

    <link rel="stylesheet" href="{{ mix('/css/ninja.css') }}">
    <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.css"/>-->
    <!--<script defer src="https://use.fontawesome.com/releases/v5.1.0/js/all.js"></script>-->
    <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/core.js"></script>-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">


    <script src=" {{ mix('/js/ninja.js') }}"></script>

    @yield('head')

</head>
<body>

    @yield('body')

</body>
</html>
