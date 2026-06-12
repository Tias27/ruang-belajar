<x-app-layout title="Tanya Materi">
    @php
        $source = $session->folder ?: $session->document;
        $initialMessages = $session->messages->map(fn ($message) => [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'metadata' => $message->metadata,
        ])->values();
    @endphp

    <script>
        window.chatSessionPage = function () {
            return {
            title: @js($session->title),
            question: '',
            sending: false,
            messages: @js($initialMessages),
            scrollToBottom() {
                this.$nextTick(() => {
                    this.$refs.messages?.scrollTo({ top: this.$refs.messages.scrollHeight, behavior: 'smooth' });
                });
            },
            formatMessage(content) {
                const escaped = String(content || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                return escaped
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.+?)\*/g, '<em>$1</em>')
                    .replace(/^- (.+)$/gm, '&bull; $1')
                    .replace(/\n/g, '<br>');
            },
            async sendQuestion() {
                const text = this.question.trim();
                if (! text || this.sending) return;

                this.messages.push({ id: 'local-' + Date.now(), role: 'user', content: text });
                this.question = '';
                this.sending = true;
                this.scrollToBottom();

                try {
                    const response = await fetch(@js(route('chat.store', $session)), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({ question: text }),
                    });

                    const raw = await response.text();
                    let data = {};
                    try {
                        data = raw ? JSON.parse(raw) : {};
                    } catch (parseError) {
                        data = { message: 'Server mengirim respons yang belum bisa dibaca. Coba ulangi dengan pertanyaan yang lebih spesifik.' };
                    }

                    if (! response.ok) {
                        throw new Error(data.message || 'Pertanyaan belum bisa diproses.');
                    }

                    this.title = data.title || this.title;
                    const answer = data.messages?.find((message) => message.role === 'assistant');
                    if (answer) this.messages.push(answer);
                } catch (error) {
                    this.messages.push({
                        id: 'error-' + Date.now(),
                        role: 'assistant',
                        content: error.message || 'Maaf, jawaban belum berhasil dibuat. Coba kirim ulang pertanyaannya.',
                    });
                } finally {
                    this.sending = false;
                    this.scrollToBottom();
                }
            },
            };
        };
    </script>

    <div x-data="chatSessionPage()" x-init="scrollToBottom()">
        <section class="mb-5 rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
            <a class="inline-flex items-center gap-1 text-sm font-semibold text-campus-700" href="{{ $session->folder ? route('folders.show', $session->folder) : route('documents.show', $session->document) }}">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $session->folder ? 'folder' : 'dokumen' }}
            </a>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-campus-900" x-text="title"></h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $source->title }}</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-lg bg-campus-50 px-3 py-2 text-xs font-semibold text-campus-700">
                    <i data-lucide="shield-check" class="h-4 w-4"></i> Jawaban berbasis {{ $session->folder ? 'folder' : 'dokumen' }}
                </span>
            </div>
        </section>

        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div x-ref="messages" class="max-h-[62vh] space-y-4 overflow-y-auto p-4">
                <template x-if="messages.length === 0">
                    <div class="py-12 text-center">
                        <i data-lucide="message-circle-question" class="mx-auto h-10 w-10 text-slate-400"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-700">Mulai bertanya berdasarkan {{ $session->folder ? 'folder' : 'dokumen' }} ini.</p>
                        <p class="mt-1 text-sm text-slate-500">Contoh: Jelaskan inti materi ini dengan bahasa sederhana.</p>
                    </div>
                </template>

                <template x-for="message in messages" :key="message.id">
                    <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-3xl rounded-lg px-4 py-3 text-sm leading-6 shadow-sm" :class="message.role === 'user' ? 'bg-campus-700 text-white' : 'border border-slate-200 bg-slate-50 text-slate-800'">
                            <div class="mb-1 text-xs font-semibold" :class="message.role === 'user' ? 'text-campus-100' : 'text-campus-700'" x-text="message.role === 'user' ? 'Kamu' : 'RuangBelajar AI'"></div>
                            <div class="leading-6 [&_strong]:font-semibold [&_em]:italic" x-html="formatMessage(message.content)"></div>
                            <template x-if="message.role === 'assistant' && message.metadata?.source_snippets?.length">
                                <div class="mt-3 space-y-2 border-t border-slate-200 pt-3">
                                    <p class="text-xs font-semibold text-slate-500">Sumber dari materi</p>
                                    <template x-for="snippet in message.metadata.source_snippets" :key="snippet.document_id + snippet.snippet">
                                        <div class="rounded-lg bg-white p-3 text-xs leading-5 text-slate-600">
                                            <p class="mb-1 font-semibold text-campus-700" x-text="snippet.title"></p>
                                            <p x-text="snippet.snippet"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="sending" x-cloak class="flex justify-start">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 shadow-sm">
                        RuangBelajar AI sedang menyusun jawaban...
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('chat.store', $session) }}" class="border-t border-slate-200 p-4" x-on:submit.prevent="sendQuestion()">
                @csrf
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input x-model="question" name="question" required placeholder="Tanyakan sesuatu dari materi..." class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-campus-500 focus:ring-campus-500">
                    <button x-bind:disabled="sending" class="inline-flex items-center justify-center gap-2 rounded-lg bg-campus-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900 disabled:cursor-not-allowed disabled:opacity-60">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        <span x-text="sending ? 'Menjawab...' : 'Kirim'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
