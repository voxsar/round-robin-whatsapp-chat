@if (request()->is('admin/chat-sessions*') || request()->is('admin/people*'))
    @php
        $chatSettings = \App\Models\ChatSetting::current();
        $botNumber = $chatSettings->bot_number ?: config('services.whatsapp.bot_number');
    @endphp
    <div
        id="filament-chat"
        data-embed="filament"
        data-pusher-key="{{ config('services.pusher.key') }}"
        data-pusher-cluster="{{ config('services.pusher.cluster') }}"
        data-whatsapp-instance="{{ config('services.whatsapp.instance') }}"
        data-whatsapp-bot-number="{{ $botNumber }}"
    ></div>

    @vite(['resources/css/app.css', 'resources/js/filament-chat.js'])
@endif
