<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Round Robin WhatsApp Chat') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div
            id="app"
            data-pusher-key="{{ config('services.pusher.key') }}"
            data-pusher-cluster="{{ config('services.pusher.cluster') }}"
            data-whatsapp-instance="{{ config('services.whatsapp.instance') }}"
        ></div>
    </body>
</html>
