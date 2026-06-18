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
                        if (this.$refs.messages) {
                            this.$refs.messages.scrollTo({ top: this.$refs.messages.scrollHeight, behavior: 'smooth' });
                        }
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
                        .replace(/â†'/g, '->')
                        .replace(/Î£/g, 'Sigma')
                        .split(/\n/);

                    let listOpen = false;
                    let tableBuffer = [];

                    const closeList = () => {
                        if (! listOpen) return '';
                        listOpen = false;
                        return '</ul>';
                    };

                    const flushTable = () => {
                        if (tableBuffer.length === 0) return '';
                        const rows = tableBuffer.filter(r => !/^[\s|:-]+$/.test(r.replace(/\|/g, '').trim()));
                        tableBuffer = [];
                        if (rows.length === 0) return '';
                        const parseRow = (r) => r.split('|').map(c => c.trim()).filter((_, i, a) => i !== 0 && i !== a.length - 1);
                        const headers = parseRow(rows[0]);
                        const body = rows.slice(1);
                        const th = headers.map(h => `<th class="border border-slate-200 bg-slate-50 px-4 py-2.5 text-left text-sm font-semibold text-slate-700">${inline(h)}</th>`).join('');
                        const trs = body.map(r => {
                            const cells = parseRow(r);
                            const tds = cells.map(c => `<td class="border border-slate-200 px-4 py-2.5 text-sm text-slate-700">${inline(c)}</td>`).join('');
                            return `<tr class="even:bg-slate-50/50">${tds}</tr>`;
                        }).join('');
                        return `<div class="my-4 w-full overflow-x-auto rounded-xl border border-slate-200 shadow-sm"><table class="w-full border-collapse text-left"><thead><tr>${th}</tr></thead><tbody>${trs}</tbody></table></div>`;
                    };

                    const inline = (value) => value
                        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.+?)\*/g, '<em>$1</em>')
                        .replace(/`([^`]+)`/g, '<code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm text-slate-800">$1</code>');

                    const result = [];

                    for (let i = 0; i < lines.length; i++) {
                        const trimmed = lines[i].trim();

                        // Table row detection
                        if (/^\|/.test(trimmed)) {
                            tableBuffer.push(trimmed);
                            continue;
                        }

                        // Flush table when we hit a non-table line
                        if (tableBuffer.length > 0) {
                            result.push(flushTable());
                        }

                        if (! trimmed) {
                            result.push(closeList());
                            continue;
                        }

                        if (/^```/.test(trimmed)) continue;

                        if (/^#{1,6}\s+/.test(trimmed)) {
                            result.push(closeList() + '<h3 class="mt-4 mb-2 text-base font-bold text-slate-900">' + inline(trimmed.replace(/^#{1,6}\s+/, '')) + '</h3>');
                            continue;
                        }

                        if (/^[-•]\s+/.test(trimmed)) {
                            const open = listOpen ? '' : '<ul class="my-2 ml-5 list-disc space-y-1.5">';
                            listOpen = true;
                            result.push(open + '<li>' + inline(trimmed.replace(/^[-•]\s+/, '')) + '</li>');
                            continue;
                        }

                        if (/^\d+\.\s+/.test(trimmed)) {
                            result.push(closeList() + '<p class="mb-2 ml-2">' + inline(trimmed) + '</p>');
                            continue;
                        }

                        const looksLikeFormula = /=|\b(?:CR|CI|RI|Sigma|Bobot|Eigen|Normalisasi)\b/i.test(trimmed) && trimmed.length <= 160;
                        result.push(closeList() + (looksLikeFormula
                            ? '<div class="my-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm text-slate-800 shadow-sm">' + inline(trimmed) + '</div>'
                            : '<p class="mb-2 leading-relaxed">' + inline(trimmed) + '</p>'));
                    }

                    // Flush any remaining table
                    if (tableBuffer.length > 0) result.push(flushTable());
                    result.push(closeList());

                    return result.filter(Boolean).join('');
                },
                async sendQuestion() {
                    const text = this.question.trim();
                    if (!text || this.sending) return;

                    this.messages.push({ id: 'local-' + Date.now(), role: 'user', content: text });
                    this.question = '';
                    this.sending = true;
                    this.$refs.textarea.style.height = 'auto'; // Reset textarea height
                    this.scrollToBottom();

                    try {
                        const assistantMessageId = 'assistant-' + Date.now();
                        this.messages.push({ id: assistantMessageId, role: 'assistant', content: '', metadata: null });
                        const assistantIndex = this.messages.length - 1;

                        const response = await fetch(@js(route('chat.store', $session)), {
                            method: 'POST',
                            headers: {
                                'Accept': 'text/event-stream',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': @js(csrf_token()),
                            },
                            body: JSON.stringify({ question: text }),
                        });

                        if (!response.ok) {
                            const raw = await response.text();
                            let data = {};
                            try { data = JSON.parse(raw); } catch (e) {}
                            throw new Error(data.message || 'Pertanyaan belum bisa diproses.');
                        }

                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();
                        let buffer = '';

                        while (true) {
                            const { value, done } = await reader.read();
                            if (done) break;

                            buffer += decoder.decode(value, { stream: true });
                            const lines = buffer.split('\n');
                            buffer = lines.pop(); // keep partial lines

                            for (const line of lines) {
                                if (line.startsWith('data: ')) {
                                    const dataStr = line.slice(6).trim();
                                    if (!dataStr) continue;

                                    try {
                                        const parsed = JSON.parse(dataStr);
                                        if (parsed.chunk) {
                                            this.messages[assistantIndex].content += parsed.chunk;
                                            this.scrollToBottom();
                                        } else if (parsed.done) {
                                            this.title = parsed.title || this.title;
                                            this.messages[assistantIndex].id = parsed.message.id;
                                            this.messages[assistantIndex].metadata = parsed.message.metadata;
                                            this.scrollToBottom();
                                        }
                                    } catch (e) {
                                        console.error('SSE parse error:', e);
                                    }
                                }
                            }
                        }
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

    <div class="h-full w-full flex flex-col bg-[#f8fbff]" x-data="chatSessionPage()" x-init="scrollToBottom()">
        <!-- Header Page -->
        <section class="flex-none px-4 py-3 sm:px-6 lg:px-8 flex items-center justify-between border-b border-slate-200/60 bg-white/80 backdrop-blur-md shadow-sm">
            <div class="flex items-center gap-3">
                <a class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-campus-700 shadow-sm transition hover:bg-campus-50 border border-slate-200" href="{{ $session->folder ? route('folders.show', $session->folder) : route('documents.show', $session->document) }}" title="Kembali">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="inline-flex items-center rounded bg-campus-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-campus-700">
                            {{ $session->folder ? 'Folder' : 'Materi' }}
                        </span>
                        <p class="line-clamp-1 text-xs font-semibold text-slate-500">{{ $source->title }}</p>
                    </div>
                    <h1 class="break-words text-[15px] font-bold tracking-tight text-campus-950" x-text="title"></h1>
                </div>
            </div>
        </section>

        <!-- Chat Canvas -->
        <div class="flex-1 flex flex-col relative min-h-0 overflow-hidden">
            
            <!-- Messages Area -->
            <div x-ref="messages" class="flex-1 space-y-8 overflow-y-auto pb-44 pt-6 px-4 sm:px-6 lg:px-8" style="scroll-behavior: smooth;">
                
                <!-- Empty State / Welcome Message -->
                <template x-if="messages.length === 0">
                    <div class="flex h-full flex-col items-center justify-center p-4">
                        <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-slate-100 text-campus-600">
                            <i data-lucide="bot" class="h-8 w-8"></i>
                        </div>
                        <h3 class="mb-2 text-center text-2xl font-bold tracking-tight text-slate-900">Halo! Apa yang ingin kamu pelajari?</h3>
                        <p class="mb-8 max-w-md text-center text-[15px] leading-relaxed text-slate-500">Aku siap membantu kamu mendalami materi ini. Pilih topik cepat di bawah atau ketik pertanyaanmu sendiri.</p>
                        
                        <div class="flex w-full max-w-3xl flex-wrap justify-center gap-3">
                            <button @click="question = 'Jelaskan intisari materi ini dengan bahasa yang sangat sederhana.'; sendQuestion()" class="group flex flex-col items-start gap-3 rounded-[1.25rem] border border-slate-200 bg-white p-5 text-left shadow-sm transition-all hover:-translate-y-1 hover:border-campus-200 hover:shadow-md w-full sm:w-[calc(33.333%-0.5rem)]">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><i data-lucide="message-circle" class="h-4 w-4"></i></span>
                                <div>
                                    <span class="block text-sm font-bold text-slate-700">Jelaskan Sederhana</span>
                                    <span class="mt-1 block text-[12px] text-slate-500">Ringkasan dengan bahasa awam</span>
                                </div>
                            </button>
                            <button @click="question = 'Bantu aku memahami konsep yang paling sulit di materi ini.'; sendQuestion()" class="group flex flex-col items-start gap-3 rounded-[1.25rem] border border-slate-200 bg-white p-5 text-left shadow-sm transition-all hover:-translate-y-1 hover:border-campus-200 hover:shadow-md w-full sm:w-[calc(33.333%-0.5rem)]">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><i data-lucide="lightbulb" class="h-4 w-4"></i></span>
                                <div>
                                    <span class="block text-sm font-bold text-slate-700">Pahami Konsep</span>
                                    <span class="mt-1 block text-[12px] text-slate-500">Bedah bagian yang rumit</span>
                                </div>
                            </button>
                            <button @click="question = 'Beri aku analogi atau contoh nyata untuk materi ini.'; sendQuestion()" class="group flex flex-col items-start gap-3 rounded-[1.25rem] border border-slate-200 bg-white p-5 text-left shadow-sm transition-all hover:-translate-y-1 hover:border-campus-200 hover:shadow-md w-full sm:w-[calc(33.333%-0.5rem)]">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="sparkles" class="h-4 w-4"></i></span>
                                <div>
                                    <span class="block text-sm font-bold text-slate-700">Beri Analogi</span>
                                    <span class="mt-1 block text-[12px] text-slate-500">Contoh di dunia nyata</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Chat Bubbles -->
                <template x-for="message in messages" :key="message.id">
                    <div class="flex w-full justify-center">
                        <div class="w-full max-w-3xl flex" :class="message.role === 'user' ? 'justify-end pl-12' : 'justify-start pr-4 sm:pr-12'">
                            
                            <!-- User Message -->
                            <template x-if="message.role === 'user'">
                                <div class="max-w-[85%] rounded-3xl bg-slate-100 px-5 py-3.5 text-[15px] leading-relaxed text-slate-800">
                                    <div x-html="formatMessage(message.content)" class="[&_p]:mb-0"></div>
                                </div>
                            </template>

                            <!-- AI Message -->
                            <template x-if="message.role === 'assistant'">
                                <div class="flex w-full gap-4 sm:gap-6">
                                    <div class="shrink-0 mt-1 hidden sm:block">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-campus-600 shadow-sm ring-1 ring-slate-100">
                                            <i data-lucide="bot" class="h-5 w-5"></i>
                                        </div>
                                    </div>
                                    <div x-data="{ showSources: false }" class="min-w-0 flex-1 text-[15px] leading-relaxed text-slate-800 pt-1 [&_p]:mb-4 [&_p:last-child]:mb-0 [&_ul]:mb-4 [&_ul]:ml-5 [&_ul]:list-disc [&_ol]:mb-4 [&_ol]:ml-5 [&_ol]:list-decimal [&_strong]:font-bold [&_strong]:text-slate-900 [&_em]:italic [&_pre]:my-4 [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:bg-slate-800 [&_pre]:p-4 [&_pre]:text-[13px] [&_pre]:text-slate-50 [&_table]:my-4 [&_table]:w-full [&_table]:border-collapse [&_table]:rounded-xl [&_table]:overflow-hidden [&_td]:border [&_td]:border-slate-200 [&_td]:p-3 [&_th]:border [&_th]:border-slate-200 [&_th]:bg-slate-50 [&_th]:p-3 [&_th]:text-left">
                                        
                                        <div x-html="formatMessage(message.content)"></div>
                                        
                                        <!-- Source Snippets Toggle -->
                                        <template x-if="message.metadata?.source_snippets?.length">
                                            <div class="mt-4 border-t border-slate-100 pt-3">
                                                <button @click="showSources = !showSources" class="group flex items-center gap-1.5 text-xs font-semibold text-campus-600 transition hover:text-campus-800">
                                                    <i data-lucide="book-open" class="h-3.5 w-3.5 text-campus-500"></i>
                                                    <span x-text="showSources ? 'Sembunyikan Referensi' : 'Lihat Referensi Materi'"></span>
                                                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-campus-400 transition-transform duration-300" :class="showSources ? 'rotate-180' : ''"></i>
                                                </button>
                                                
                                                <!-- Sources Content -->
                                                <div x-show="showSources" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2">
                                                    <div class="mt-3 space-y-2">
                                                        <template x-for="snippet in message.metadata.source_snippets" :key="snippet.document_id + snippet.snippet">
                                                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-[13px] leading-5 text-slate-600 shadow-sm">
                                                                <div class="mb-1.5 flex items-center gap-1.5 font-semibold text-campus-800">
                                                                    <i data-lucide="file-text" class="h-3.5 w-3.5 text-campus-600"></i>
                                                                    <span x-text="snippet.title"></span>
                                                                </div>
                                                                <p class="italic text-slate-500">"...<span x-text="snippet.snippet"></span>..."</p>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                        </div>
                    </div>
                </template>

                <!-- Typing Indicator -->
                <div x-show="sending" x-cloak class="flex w-full justify-center">
                    <div class="w-full max-w-3xl flex justify-start pr-4 sm:pr-12">
                        <div class="flex gap-4 sm:gap-6 w-full">
                            <div class="mt-1 shrink-0 hidden sm:block">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-campus-600 shadow-sm ring-1 ring-slate-100">
                                    <i data-lucide="bot" class="h-5 w-5"></i>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 pt-2">
                                <span class="h-2 w-2 rounded-full bg-slate-400 animate-[bounce_1s_infinite_0ms]"></span>
                                <span class="h-2 w-2 rounded-full bg-slate-400 animate-[bounce_1s_infinite_200ms]"></span>
                                <span class="h-2 w-2 rounded-full bg-slate-400 animate-[bounce_1s_infinite_400ms]"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Input Area — floating at bottom like ChatGPT -->
            <div class="absolute bottom-0 left-0 right-0 px-3 pt-6 sm:px-4 lg:px-8" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom, 0.75rem)); background: linear-gradient(to top, white 60%, transparent);">
                <form method="POST" action="{{ route('chat.store', $session) }}" @submit.prevent="sendQuestion()" class="flex w-full flex-col mx-auto max-w-3xl pointer-events-auto">
                    @csrf
                    <div class="relative flex min-w-0 flex-1 items-end gap-2 rounded-[2rem] bg-white p-2 shadow-xl shadow-black/8 ring-1 ring-slate-200 focus-within:ring-2 focus-within:ring-campus-300 transition-all duration-200">
                        <textarea 
                            x-ref="textarea"
                            x-model="question" 
                            name="question" 
                            rows="1"
                            class="max-h-32 min-h-[44px] w-full resize-none border-0 bg-transparent py-3 pl-5 pr-2 text-[15px] focus:ring-0 focus:outline-none" 
                            placeholder="Tanya AI tentang materi ini..." 
                            @keydown.enter.prevent="if(!$event.shiftKey) sendQuestion()"
                            @input="$el.style.height = 'auto'; $el.style.height = ($el.scrollHeight) + 'px';"
                        ></textarea>
                        
                        <button type="submit" :disabled="!question.trim() || sending" class="group flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white shadow-sm transition-all hover:bg-slate-800 active:scale-95 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            <i data-lucide="arrow-up" class="h-5 w-5"></i>
                            <span class="sr-only">Kirim</span>
                        </button>
                    </div>
                    <div class="mt-2 text-center text-[11px] font-medium text-slate-400">
                        AI bisa keliru. Periksa kembali info penting.
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
