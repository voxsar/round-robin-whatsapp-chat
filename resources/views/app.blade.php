<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Round Robin WhatsApp Chat') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        @php
            $chatSettings = \App\Models\ChatSetting::current();
            $botNumber = $chatSettings->bot_number ?: config('services.whatsapp.bot_number');
        @endphp
        <div
            id="app"
            data-pusher-key="{{ config('services.pusher.key') }}"
            data-pusher-cluster="{{ config('services.pusher.cluster') }}"
            data-whatsapp-instance="{{ config('services.whatsapp.instance') }}"
            data-whatsapp-bot-number="{{ $botNumber }}"
        ></div>
    </body>
</html>
