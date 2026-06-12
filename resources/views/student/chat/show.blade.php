<x-app-layout title="Tanya AI">
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
                const normalizeMath = (value) => String(value || '')
                    .replace(/\$\$([\s\S]*?)\$\$/g, '$1')
                    .replace(/\$([^$]+)\$/g, '$1')
                    .replace(/\\text\{([^{}]*)\}/g, '$1')
                    .replace(/\\frac\{([^{}]*)\}\{([^{}]*)\}/g, '($1) / ($2)')
                    .replace(/\\sum/g, 'Sigma')
                    .replace(/\\times/g, 'x')
                    .replace(/\\rightarrow/g, '->')
                    .replace(/\\geq/g, '>=')
                    .replace(/\\leq/g, '<=')
                    .replace(/\\neq/g, '!=')
                    .replace(/\\\(|\\\)|\\\[|\\\]/g, '');

                const escaped = normalizeMath(content)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const lines = escaped
                    .replace(/â€¢/g, '-')
                    .replace(/â†’/g, '->')
                    .replace(/Î£/g, 'Sigma')
                    .split(/\n/);

                let listOpen = false;
                const closeList = () => {
                    if (! listOpen) return '';
                    listOpen = false;
                    return '</ul>';
                };

                const inline = (value) => value
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.+?)\*/g, '<em>$1</em>')
                    .replace(/`([^`]+)`/g, '<code class="rounded bg-slate-200 px-1 py-0.5 text-[.8em]">$1</code>');

                const blocks = [];

                return lines.map((line) => {
                    const trimmed = line.trim();
                    if (! trimmed) {
                        return closeList();
                    }

                    if (/^#{1,6}\s+/.test(trimmed)) {
                        return closeList() + '<h3 class="mt-4 text-sm font-semibold text-slate-950">' + inline(trimmed.replace(/^#{1,6}\s+/, '')) + '</h3>';
                    }

                    if (/^[-•]\s+/.test(trimmed)) {
                        const open = listOpen ? '' : '<ul class="my-2 ml-5 list-disc space-y-1">';
                        listOpen = true;
                        return open + '<li>' + inline(trimmed.replace(/^[-•]\s+/, '')) + '</li>';
                    }

                    if (/^\d+\.\s+/.test(trimmed)) {
                        return closeList() + '<p class="mb-2">' + inline(trimmed) + '</p>';
                    }

                    if (/^\|/.test(trimmed) || /^[-:|\s]+$/.test(trimmed) || /^```/.test(trimmed)) {
                        return '';
                    }

                    const looksLikeFormula = /[=()\/]|Sigma|Bobot|CR|CI|RI|Eigen|Normalisasi/i.test(trimmed)
                        && trimmed.length <= 160;

                    return closeList() + (looksLikeFormula
                        ? '<div class="my-2 rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-xs text-slate-800">' + inline(trimmed) + '</div>'
                        : '<p class="mb-2">' + inline(trimmed) + '</p>');
                }).concat(closeList()).filter(Boolean).join('');
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

    <div class="min-w-0 overflow-x-hidden" x-data="chatSessionPage()" x-init="scrollToBottom()">
        <section class="mb-5 overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
            <a class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-2 text-sm font-semibold text-campus-700 shadow-sm hover:bg-campus-100" href="{{ $session->folder ? route('folders.show', $session->folder) : route('documents.show', $session->document) }}">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $session->folder ? 'folder' : 'materi' }}
            </a>
            <div class="mt-5 flex min-w-0 flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-campus-700">Tanya materi</p>
                    <h1 class="mt-1 break-words text-2xl font-semibold tracking-tight text-campus-900" x-text="title"></h1>
                    <p class="mt-1 truncate text-sm text-slate-600">{{ $source->title }}</p>
                </div>
                <span class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full bg-white px-3 py-2 text-xs font-semibold text-campus-700 shadow-sm">
                    <i data-lucide="shield-check" class="h-4 w-4"></i> Jawaban dari {{ $session->folder ? 'folder ini' : 'file ini' }}
                </span>
            </div>
        </section>

        <div class="overflow-hidden rounded-[1.5rem] bg-white shadow-sm">
            <div x-ref="messages" class="max-h-[62vh] space-y-4 overflow-y-auto bg-white p-4 sm:p-5">
                <template x-if="messages.length === 0">
                    <div class="py-12 text-center">
                        <i data-lucide="message-circle-question" class="mx-auto h-10 w-10 text-slate-400"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-700">Mulai tanya AI dari {{ $session->folder ? 'folder' : 'file' }} ini.</p>
                        <p class="mt-1 text-sm text-slate-500">Contoh: Jelaskan inti materi ini dengan bahasa sederhana.</p>
                    </div>
                </template>

                <template x-for="message in messages" :key="message.id">
                    <div class="flex min-w-0" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[min(42rem,92%)] rounded-[1.25rem] px-4 py-3 text-sm leading-6 shadow-sm" :class="message.role === 'user' ? 'bg-campus-700 text-white rounded-br-md' : 'bg-slate-50 text-slate-800 rounded-bl-md'">
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
                    <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3 text-sm text-slate-600 shadow-sm">
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 animate-pulse rounded-full bg-campus-500"></span> RuangBelajar AI sedang menyusun jawaban...</span>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('chat.store', $session) }}" class="border-t border-slate-100 bg-white p-3 sm:p-4" x-on:submit.prevent="sendQuestion()">
                @csrf
                <div class="flex min-w-0 gap-2">
                    <input x-model="question" name="question" required placeholder="Tanyakan sesuatu dari materi..." class="min-w-0 flex-1 rounded-full border-slate-200 bg-slate-50 text-sm shadow-sm focus:border-campus-500 focus:ring-campus-500">
                    <button x-bind:disabled="sending" class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-campus-700 text-white shadow-sm hover:bg-campus-900 disabled:cursor-not-allowed disabled:opacity-60" title="Kirim">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        <span class="sr-only" x-text="sending ? 'Menjawab...' : 'Kirim'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
