<x-filament::page>
    <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
        <div class="rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">Active Chats</p>
                <button
                    class="text-xs font-semibold text-emerald-600"
                    type="button"
                    wire:click="refreshSessions"
                >Refresh</button>
            </div>
            <div class="max-h-[70vh] overflow-y-auto">
                @forelse($sessions as $session)
                    <button
                        type="button"
                        wire:click="selectSession({{ $session['id'] }})"
                        class="flex w-full flex-col gap-1 border-b border-slate-100 px-4 py-3 text-left hover:bg-slate-50"
                    >
                        <span class="text-sm font-semibold text-slate-900">{{ $session['label'] }}</span>
                        <span class="text-xs text-slate-500">{{ $session['status'] }}</span>
                    </button>
                @empty
                    <div class="px-4 py-6 text-sm text-slate-500">No chats found.</div>
                @endforelse
            </div>
        </div>

        <div class="flex h-[70vh] flex-col rounded-xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">Conversation</p>
                <button
                    class="text-xs font-semibold text-emerald-600"
                    type="button"
                    wire:click="loadMessages"
                >Refresh</button>
            </div>

            <div
                class="flex-1 space-y-4 overflow-y-auto px-4 py-4"
                wire:poll.5s="loadMessages"
                x-data
                x-init="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
                x-on:livewire:message.processed.window="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
            >
                @forelse($messages as $message)
                    <div class="flex {{ $message['sender'] === 'agent' ? 'justify-end' : ($message['sender'] === 'system' ? 'justify-center' : 'justify-start') }}">
                        <div class="max-w-[75%] rounded-2xl px-4 py-2 text-sm shadow-sm {{ $message['sender'] === 'agent' ? 'bg-emerald-600 text-white' : ($message['sender'] === 'system' ? 'bg-slate-200 text-slate-600' : 'bg-slate-100 text-slate-700') }}">
                            <p>{{ $message['text'] }}</p>
                            <p class="mt-1 text-[0.65rem] opacity-70">{{ $message['timestamp'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">Select a chat to view messages.</div>
                @endforelse
            </div>

            <form class="border-t border-slate-100 p-3" wire:submit.prevent="sendMessage">
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        class="flex-1 rounded-full border border-slate-200 px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                        placeholder="Type your reply..."
                        wire:model.defer="newMessage"
                    />
                    <button
                        type="submit"
                        class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        Send
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-filament::page>
