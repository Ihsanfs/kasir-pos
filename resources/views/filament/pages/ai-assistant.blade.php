@use('Illuminate\Support\Str')

<x-filament-panels::page>
    <style>
        .ai-app {
            --ai-bg: #ffffff;
            --ai-soft: #f8fafc;
            --ai-border: #e2e8f0;
            --ai-text: #0f172a;
            --ai-muted: #64748b;
            --ai-accent: #7c3aed;
            --ai-accent-soft: #f5f3ff;
            display: flex;
            flex-direction: column;
            gap: .9rem;
        }

        .dark .ai-app {
            --ai-bg: #1e293b;
            --ai-soft: #0f172a;
            --ai-border: #334155;
            --ai-text: #f1f5f9;
            --ai-muted: #94a3b8;
            --ai-accent: #a78bfa;
            --ai-accent-soft: rgba(167, 139, 250, 0.12);
        }

        .ai-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            padding: .85rem 1rem;
            border: 1px solid var(--ai-border);
            border-radius: .9rem;
            background: var(--ai-bg);
        }

        .ai-status {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            font-size: .8rem;
            font-weight: 700;
            color: var(--ai-accent);
        }

        .ai-status::before {
            content: "";
            width: .5rem;
            height: .5rem;
            border-radius: 999px;
            background: currentColor;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--ai-accent) 18%, transparent);
        }

        .ai-provider {
            font-size: .74rem;
            color: var(--ai-muted);
        }

        .ai-clear {
            border: 1px solid var(--ai-border);
            background: transparent;
            color: var(--ai-muted);
            padding: .45rem .8rem;
            border-radius: .55rem;
            font-size: .74rem;
            font-weight: 600;
            cursor: pointer;
        }

        .ai-clear:hover {
            color: var(--ai-accent);
            border-color: var(--ai-accent);
        }

        .ai-quick {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .ai-quick button {
            padding: .42rem .75rem;
            border-radius: 999px;
            border: 1px solid var(--ai-border);
            background: var(--ai-bg);
            color: var(--ai-text);
            font-size: .74rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .12s;
        }

        .ai-quick button:hover {
            border-color: var(--ai-accent);
            color: var(--ai-accent);
            background: var(--ai-accent-soft);
        }

        .ai-messages {
            display: flex;
            flex-direction: column;
            gap: .7rem;
            padding: 1rem;
            border: 1px solid var(--ai-border);
            border-radius: .9rem;
            background: var(--ai-soft);
            min-height: 360px;
            max-height: 58vh;
            overflow-y: auto;
        }

        .ai-message {
            display: flex;
            gap: .55rem;
            max-width: 82%;
        }

        .ai-message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .ai-avatar {
            flex-shrink: 0;
            width: 1.9rem;
            height: 1.9rem;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            font-weight: 800;
            background: var(--ai-accent-soft);
            color: var(--ai-accent);
        }

        .ai-message.user .ai-avatar {
            background: color-mix(in srgb, var(--ai-accent) 90%, #000);
            color: #fff;
        }

        .ai-bubble {
            padding: .65rem .85rem;
            border-radius: .85rem;
            background: var(--ai-bg);
            border: 1px solid var(--ai-border);
            color: var(--ai-text);
            font-size: .84rem;
            line-height: 1.55;
            overflow-wrap: anywhere;
        }

        .ai-message.user .ai-bubble {
            background: var(--ai-accent);
            border-color: var(--ai-accent);
            color: #fff;
        }

        .ai-bubble p {
            margin: 0 0 .45rem;
        }

        .ai-bubble p:last-child {
            margin-bottom: 0;
        }

        .ai-bubble ul,
        .ai-bubble ol {
            margin: .3rem 0 .45rem;
            padding-inline-start: 1.2rem;
        }

        .ai-bubble strong {
            font-weight: 800;
        }

        .ai-bubble hr {
            border: none;
            border-top: 1px solid var(--ai-border);
            margin: .55rem 0;
        }

        .ai-bubble code {
            background: var(--ai-soft);
            padding: .1rem .3rem;
            border-radius: .25rem;
            font-size: .78rem;
        }

        .ai-compose {
            display: flex;
            gap: .5rem;
            align-items: flex-end;
        }

        .ai-compose textarea {
            flex: 1;
            resize: vertical;
            min-height: 52px;
            max-height: 160px;
            padding: .7rem .85rem;
            border-radius: .75rem;
            border: 1px solid var(--ai-border);
            background: var(--ai-bg);
            color: var(--ai-text);
            font-size: .85rem;
            outline: none;
            line-height: 1.4;
        }

        .ai-compose textarea:focus {
            border-color: var(--ai-accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--ai-accent) 15%, transparent);
        }

        .ai-send {
            padding: .72rem 1.1rem;
            border: none;
            border-radius: .7rem;
            background: var(--ai-accent);
            color: #fff;
            font-size: .82rem;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
        }

        .ai-send:disabled {
            opacity: .6;
            cursor: wait;
        }

        .ai-note {
            font-size: .7rem;
            color: var(--ai-muted);
        }

        @media (max-width: 640px) {
            .ai-message {
                max-width: 95%;
            }
        }
    </style>

    <div class="ai-app">
        <div class="ai-head">
            <div>
                <div class="ai-status">
                    {{ $this->isOnline ? 'AI online aktif' : 'Mode analisis lokal' }}
                </div>
                <div class="ai-provider">Provider: {{ $this->providerLabel }}</div>
            </div>

            <button type="button" class="ai-clear" wire:click="clearChat">Mulai Percakapan Baru</button>
        </div>

        <div class="ai-quick">
            <button type="button" wire:click="ask('Bagaimana ringkasan penjualan hari ini?')">Ringkasan Penjualan</button>
            <button type="button" wire:click="ask('Produk apa yang paling laris 30 hari terakhir?')">Produk Terlaris</button>
            <button type="button" wire:click="ask('Produk mana yang stoknya menipis dan berapa rekomendasi restock?')">Stok & Restock</button>
            <button type="button" wire:click="ask('Bagaimana laba rugi 30 hari terakhir?')">Laba Rugi</button>
            <button type="button" wire:click="ask('Audit anomali transaksi 30 hari terakhir')">Audit Anomali</button>
            <button type="button" wire:click="ask('Rincian pengeluaran 30 hari terakhir')">Pengeluaran</button>
        </div>

        <div class="ai-messages">
            @foreach ($messages as $index => $message)
                <div class="ai-message {{ $message['role'] === 'user' ? 'user' : 'assistant' }}" wire:key="msg-{{ $index }}">
                    <div class="ai-avatar">{{ $message['role'] === 'user' ? 'Anda' : 'AI' }}</div>
                    <div class="ai-bubble">
                        @if ($message['role'] === 'user')
                            {!! nl2br(e($message['content'])) !!}
                        @else
                            {!! Str::markdown($message['content'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                        @endif
                    </div>
                </div>
            @endforeach

            <div wire:loading.flex wire:target="send,ask" class="ai-message assistant">
                <div class="ai-avatar">AI</div>
                <div class="ai-bubble">Menganalisis data...</div>
            </div>
        </div>

        <form class="ai-compose" wire:submit="send">
            <textarea
                wire:model="question"
                placeholder="Tulis pertanyaan, contoh: produk apa yang perlu direstock minggu ini?"
                wire:keydown.enter.prevent="send"
            ></textarea>

            <button type="submit" class="ai-send" wire:loading.attr="disabled" wire:target="send,ask">
                Kirim
            </button>
        </form>

        <div class="ai-note">
            Jawaban dihasilkan dari data transaksi, stok, dan pengeluaran yang tersimpan di aplikasi.
            Mode lokal tidak memerlukan API key. Mode online mengirim ringkasan data ke provider AI yang dipilih.
        </div>
    </div>

    <script>
        (function () {
            if (window.__aiScrollRegistered) {
                return;
            }

            window.__aiScrollRegistered = true;

            const scrollToBottom = () => {
                const box = document.querySelector('.ai-messages');

                if (box) {
                    box.scrollTop = box.scrollHeight;
                }
            };

            document.addEventListener('livewire:init', () => {
                Livewire.hook('morph.updated', () => scrollToBottom());
                scrollToBottom();
            });
        })();
    </script>
</x-filament-panels::page>
