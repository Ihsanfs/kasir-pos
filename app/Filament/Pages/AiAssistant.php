<?php

namespace App\Filament\Pages;

use App\Services\AiService;
use App\Models\Setting;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class AiAssistant extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Asisten AI';

    protected static ?string $title = 'Asisten AI';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.ai-assistant';

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    public string $question = '';

    public function mount(): void
    {
        $this->messages = session('ai_chat', []);

        if (empty($this->messages)) {
            $this->messages = [[
                'role' => 'assistant',
                'content' => $this->welcomeMessage(),
            ]];
        }
    }

    #[Computed]
    public function isOnline(): bool
    {
        return app(AiService::class)->isConfigured();
    }

    #[Computed]
    public function providerLabel(): string
    {
        return match ((string) Setting::get('ai_provider', 'local')) {
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            default => 'Analisis Lokal',
        };
    }

    public function send(): void
    {
        $question = trim($this->question);

        if ($question === '') {
            return;
        }

        $history = $this->messages;

        $this->messages[] = ['role' => 'user', 'content' => $question];
        $this->question = '';

        $answer = app(AiService::class)->answer($question, $history);

        $this->messages[] = ['role' => 'assistant', 'content' => $answer];

        session()->put('ai_chat', $this->messages);
    }

    public function ask(string $prompt): void
    {
        $this->question = $prompt;
        $this->send();
    }

    public function clearChat(): void
    {
        $this->messages = [[
            'role' => 'assistant',
            'content' => $this->welcomeMessage(),
        ]];

        session()->put('ai_chat', $this->messages);
    }

    private function welcomeMessage(): string
    {
        $mode = app(AiService::class)->isConfigured()
            ? 'AI online aktif ('.(string) Setting::get('ai_provider', 'openai').').'
            : 'Mode analisis lokal aktif. Isi API key di menu Pengaturan untuk mengaktifkan AI online.';

        return "Halo! Saya asisten bisnis Anda. {$mode}\n\n"
            ."Saya bisa membantu menganalisis penjualan, laba, stok menipis, prediksi restock, pengeluaran, dan anomali transaksi.\n\n"
            .'Coba salah satu tombol pertanyaan cepat di bawah, atau tulis pertanyaan sendiri.';
    }
}
