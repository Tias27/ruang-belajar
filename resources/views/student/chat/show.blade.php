<x-app-layout title="Tanya AI">
    @php
        $source = $session->folder ?: $session->document;
        $storeUrl = $session->exists ? route('chat.store', $session) : route('chat.general.store');
        $lastQuestion = '';
        $initialMessages = $session->messages->map(function ($message) use ($session, &$lastQuestion) {
            $message->setRelation('session', $session);
            if ($message->role === 'user') $lastQuestion = $message->content;
            return $message->forChat($lastQuestion);
        })->values();
    @endphp

    <script>
        window.chatSessionPage = function () {
            return {
                ...window.chatAttachmentPreview({ documentUrl: @js(route('chat.attachments.document', '__DOCUMENT__')) }),
                ...window.chatMaterials({
                    materials: [],
                    listUrl: @js(route('chat.materials.index')),
                    uploadUrl: @js(route('chat.materials.store')),
                    csrf: @js(csrf_token()),
                }),
                title: @js($session->title),
                storeUrl: @js($storeUrl),
                question: '',
                sending: false,
                messages: @js($initialMessages),
                webSourceUrl(value) {
                    try { const url = new URL(value); return ['https:', 'http:'].includes(url.protocol) ? url.href : null; } catch { return null; }
                },
                webSourceHost(value) {
                    try { return new URL(value).hostname; } catch { return ''; }
                },
                imageFile: null,
                imagePreview: null,
                triggerSelectImage() {
                    this.$refs.imageInput.click();
                },
                handleFile(file) {
                    if (!file) return;
                    if (!file.type.startsWith('image/')) {
                        alert('Hanya file gambar yang diperbolehkan.');
                        return;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Ukuran gambar maksimal adalah 5MB.');
                        this.clearImage();
                        return;
                    }
                    this.imageFile = file;
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        this.imagePreview = event.target.result;
                    };
                    reader.readAsDataURL(file);
                },
                onImageSelected(e) {
                    this.handleFile(e.target.files[0]);
                },
                onPaste(e) {
                    const file = e.clipboardData?.files?.[0];
                    if (file) {
                        this.handleFile(file);
                    }
                },
                onDrop(e) {
                    const file = e.dataTransfer?.files?.[0];
                    if (file) {
                        this.handleFile(file);
                    }
                },
                clearImage() {
                    this.imageFile = null;
                    this.imagePreview = null;
                    if (this.$refs.imageInput) {
                        this.$refs.imageInput.value = '';
                    }
                },
                isNearBottom(offset = 180) {
                    const el = this.$refs.messages;
                    if (!el) return true;
                    return el.scrollHeight - el.scrollTop - el.clientHeight <= offset;
                },
                scrollToBottom(force = false) {
                    const shouldScroll = force || this.isNearBottom();
                    this.$nextTick(() => {
                        if (shouldScroll && this.$refs.messages) {
                            this.$refs.messages.scrollTo({ top: this.$refs.messages.scrollHeight, behavior: force ? 'instant' : 'smooth' });
                        }
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                },
                async sendQuestion() {
                    const text = this.question.trim();
                    if ((!text && !this.imageFile && !this.materials.length) || this.sending || this.materialUploading) return;

                    const localId = 'local-' + Date.now();
                    const localMetadata = { materials: this.materials.map(item => ({ ...item })) };
                    if (this.imagePreview) localMetadata.image_path = this.imagePreview;
                    const question = text || (this.imageFile ? 'Jelaskan gambar ini' : 'Jelaskan materi yang dilampirkan');
                    const pendingImage = this.imageFile;
                    const pendingPreview = this.imagePreview;

                    this.messages.push({
                        id: localId,
                        role: 'user',
                        content: question,
                        metadata: localMetadata
                    });

                    const formData = new FormData();
                    formData.append('question', question);
                    if (this.materials.length) {
                        formData.append('materials_present', '1');
                        this.materials.forEach(item => formData.append('material_ids[]', item.id));
                    }
                    if (this.imageFile) {
                        formData.append('image', this.imageFile);
                    }

                    this.question = '';
                    this.materials = [];
                    this.clearImage();
                    this.sending = true;
                    this.$refs.textarea.style.height = 'auto'; // Reset textarea height
                    this.scrollToBottom(true);

                    const assistantMessageId = 'assistant-' + Date.now();
                    let requestAccepted = false;
                    try {
                        this.messages.push({ id: assistantMessageId, role: 'assistant', content: '', metadata: null, streaming: true });
                        const assistantIndex = this.messages.length - 1;

                        const response = await fetch(this.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json, text/event-stream',
                                'X-CSRF-TOKEN': @js(csrf_token()),
                            },
                            body: formData,
                        });

                        if (!response.ok) {
                            const raw = await response.text();
                            let data = {};
                            try { data = JSON.parse(raw); } catch (e) { }
                            throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Pertanyaan belum bisa diproses.');
                        }
                        requestAccepted = true;

                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();
                        let buffer = '';

                        let typeQueue = '';
                        let typingTimer = null;
                        const drainTypeQueue = (stepOverride) => {
                            if (!typeQueue.length || !this.messages[assistantIndex]) return;
                            const qlen = typeQueue.length;
                            const step = stepOverride || (qlen > 250 ? 24 : (qlen > 100 ? 12 : (qlen > 30 ? 5 : 2)));
                            const chars = typeQueue.slice(0, step);
                            typeQueue = typeQueue.slice(step);
                            this.messages[assistantIndex].content += chars;
                            if (window.lucide && chars.includes('<')) {
                                this.$nextTick(() => window.lucide?.createIcons());
                            }
                        };

                        const startTyping = () => {
                            if (typingTimer) return;
                            typingTimer = setInterval(() => {
                                if (!typeQueue.length) {
                                    if (!this.sending) {
                                        clearInterval(typingTimer);
                                        typingTimer = null;
                                    }
                                    return;
                                }
                                drainTypeQueue();
                            }, 18);
                        };

                        let streamCompleted = false;
                        while (!streamCompleted) {
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
                                        if (parsed.user_message) {
                                            const localMessage = this.messages.findIndex(message => message.id === localId);
                                            if (localMessage !== -1) this.messages[localMessage] = parsed.user_message;
                                            if (parsed.store_url) this.storeUrl = parsed.store_url;
                                            if (parsed.session_url) history.replaceState(history.state, '', parsed.session_url);
                                        } else if (parsed.status) {
                                            this.messages[assistantIndex].searchStatus = ({searching: 'Mencari sumber...', reading: 'Membaca sumber...'})[parsed.status] || '';
                                            this.messages[assistantIndex].searchPhase = ['searching', 'reading', 'answering'].includes(parsed.status) ? parsed.status : '';
                                        } else if (parsed.web_sources) {
                                            this.messages[assistantIndex].metadata = { ...(this.messages[assistantIndex].metadata || {}), web_sources: parsed.web_sources };
                                        } else if (parsed.chunk) {
                                            if (this.messages[assistantIndex].searchPhase) {
                                                this.messages[assistantIndex].searchStatus = '';
                                                this.messages[assistantIndex].searchPhase = 'answering';
                                            }
                                            typeQueue += parsed.chunk;
                                            startTyping();
                                        } else if (parsed.done) {
                                            while (typeQueue.length > 0) {
                                                drainTypeQueue(Math.max(6, Math.ceil(typeQueue.length / 4)));
                                                await new Promise(r => setTimeout(r, 16));
                                            }
                                            if (typingTimer) {
                                                clearInterval(typingTimer);
                                                typingTimer = null;
                                            }
                                            this.messages[assistantIndex].streaming = false;
                                            this.title = parsed.title || this.title;
                                            if (parsed.store_url) this.storeUrl = parsed.store_url;
                                            if (parsed.session_url) history.replaceState(history.state, '', parsed.session_url);
                                            this.messages[assistantIndex].content = parsed.message.content ?? this.messages[assistantIndex].content;
                                            this.messages[assistantIndex].id = parsed.message.id;
                                            this.messages[assistantIndex].metadata = parsed.message.metadata;
                                            this.$nextTick(() => {
                                                if (window.lucide) window.lucide.createIcons();
                                            });
                                            streamCompleted = true;
                                            try { await reader.cancel(); } catch (e) { }
                                            break;
                                        }
                                    } catch (e) {
                                        console.error('SSE parse error:', e);
                                    }
                                }
                            }
                        }
                    } catch (error) {
                        if (!requestAccepted) {
                            this.messages = this.messages.filter(message => message.id !== localId);
                            this.question = text;
                            this.materials = localMetadata.materials;
                            this.imageFile = pendingImage;
                            this.imagePreview = pendingPreview;
                        }
                        this.messages = this.messages.filter(message => message.id !== assistantMessageId || message.content.trim());
                        this.messages.push({
                            id: 'error-' + Date.now(),
                            role: 'assistant',
                            content: error.message || 'Maaf, jawaban belum berhasil dibuat. Coba kirim ulang pertanyaannya.',
                        });
                    } finally {
                        if (typingTimer) {
                            clearInterval(typingTimer);
                            typingTimer = null;
                        }
                        if (typeQueue.length > 0 && this.messages[assistantIndex]) {
                            this.messages[assistantIndex].content += typeQueue;
                            typeQueue = '';
                        }
                        this.messages.forEach(message => { if (message.streaming) message.streaming = false; });
                        this.sending = false;
                        this.$nextTick(() => {
                            if (window.lucide) window.lucide.createIcons();
                        });
                    }
                },
            };
        };
    </script>

    <div class="learning-live live-chat live-ai-chat h-full w-full flex flex-col" x-data="chatSessionPage()" x-init="if (messages.length) scrollToBottom(true)">
        <!-- Header Page -->
        <section
            class="live-chat-header flex-none px-4 py-3 sm:px-6 lg:px-8 flex items-center justify-between border-b border-slate-200/60 bg-white shadow-sm">
            <div class="flex min-w-0 items-center gap-3">
                <a class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-campus-700 shadow-sm transition hover:bg-campus-50 border border-slate-200"
                    href="{{ $session->folder ? route('folders.show', $session->folder) : ($session->document ? route('documents.show', $session->document) : route('chat.index')) }}"
                    title="Kembali" aria-label="{{ $source ? 'Kembali ke materi' : 'Kembali ke riwayat' }}">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span
                            class="inline-flex items-center rounded bg-campus-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-campus-700">
                            {{ $source ? ($session->folder ? 'Folder' : 'Materi') : 'Chat bebas' }}
                        </span>
                        <p class="line-clamp-1 text-xs font-semibold text-slate-500">{{ $source?->title ?? 'Bahas apa saja tanpa upload materi' }}</p>
                    </div>
                    <h1 class="truncate text-[15px] font-bold tracking-tight text-campus-950" x-text="title" :title="title"></h1>
                </div>
            </div>
        </section>

        <!-- Chat Canvas -->
        <div class="flex-1 flex flex-col relative min-h-0 overflow-hidden">

            <!-- Messages Area -->
            <div x-ref="messages" class="live-chat-messages flex-1 min-h-0 overflow-y-auto p-4 sm:px-6 lg:px-8"
                style="scroll-behavior: smooth; overscroll-behavior-y: contain;">

                <!-- Empty State / Welcome Message -->
                <template x-if="messages.length === 0">
                    <div class="live-chat-welcome">
                        <div class="live-chat-welcome-icon" aria-hidden="true">
                            <i data-lucide="messages-square"></i>
                        </div>
                        <h2>{{ $source ? 'Mau bahas bagian mana?' : 'Mau ngobrolin apa?' }}</h2>
                        <p>Pilih pertanyaan awal atau tulis yang bikin kamu penasaran</p>

                        <div class="live-chat-prompts">
                            <button type="button"
                                @click="question = @js($source ? 'Jelaskan intisari materi ini dengan bahasa yang sangat sederhana.' : 'Jelaskan bagaimana hujan terbentuk dengan bahasa sederhana.'); sendQuestion()"
                                class="live-chat-prompt">
                                <i data-lucide="message-circle" aria-hidden="true"></i>
                                <div>
                                    <strong>Jelaskan sederhana</strong>
                                    <span>{{ $source ? 'Inti materi, bahasa sehari-hari' : 'Kenapa hujan bisa turun?' }}</span>
                                </div>
                            </button>
                            <button type="button"
                                @click="question = @js($source ? 'Bantu aku memahami konsep yang paling sulit di materi ini.' : 'Bantu aku menyusun jadwal belajar yang realistis.'); sendQuestion()"
                                class="live-chat-prompt">
                                <i data-lucide="lightbulb" aria-hidden="true"></i>
                                <div>
                                    <strong>{{ $source ? 'Pahami konsep' : 'Susun rencana' }}</strong>
                                    <span>{{ $source ? 'Urai bagian yang rumit' : 'Bikin jadwal belajar' }}</span>
                                </div>
                            </button>
                            <button type="button"
                                @click="question = @js($source ? 'Beri aku analogi atau contoh nyata untuk materi ini.' : 'Ayo cari ide kegiatan kreatif untuk akhir pekan.'); sendQuestion()"
                                class="live-chat-prompt">
                                <i data-lucide="sparkles" aria-hidden="true"></i>
                                <div>
                                    <strong>{{ $source ? 'Beri contoh' : 'Cari ide' }}</strong>
                                    <span>{{ $source ? 'Hubungkan ke keseharian' : 'Kegiatan kreatif akhir pekan' }}</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Chat Bubbles -->
                <template x-for="message in messages" :key="message.id">
                    <div class="live-chat-message flex w-full justify-center">
                        <div class="w-full max-w-3xl flex"
                            :class="message.role === 'user' ? 'justify-end pl-12' : 'justify-start pr-4 sm:pr-12'">

                            <!-- User Message -->
                            <template x-if="message.role === 'user'">
                                <div class="live-chat-user-content">
                                    <template x-if="message.metadata?.image_path">
                                        <button type="button" class="live-chat-user-image" aria-label="Buka gambar ukuran penuh" @click="openImagePreview(message.metadata.image_path)">
                                            <img :src="imageUrl(message.metadata.image_path)" alt="Gambar yang dikirim" loading="lazy" @load="scrollToBottom()">
                                        </button>
                                    </template>
                                    <div class="chat-message-materials" x-show="message.metadata?.materials?.length">
                                        <template x-for="material in (message.metadata?.materials || [])" :key="material.id">
                                            <button type="button" class="chat-message-material" :title="material.title" @click="openFilePreview(material)"><i data-lucide="file-text" aria-hidden="true"></i><span x-text="material.title"></span></button>
                                        </template>
                                    </div>
                                    <div x-show="message.content && message.content !== 'Mengirim gambar...' && message.content !== 'Jelaskan gambar ini'" x-text="message.content" class="live-chat-user-bubble"></div>
                                </div>
                            </template>

                            <!-- AI Message -->
                            <template x-if="message.role === 'assistant'">
                                <div class="flex w-full gap-4 sm:gap-6">
                                    <div class="shrink-0 mt-1 hidden sm:block">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-campus-600 shadow-sm ring-1 ring-slate-100">
                                            <i data-lucide="bot" class="h-5 w-5"></i>
                                        </div>
                                    </div>
                                    <div x-data="{ showSources: false }"
                                        class="min-w-0 flex-1 text-[13px] leading-relaxed text-slate-800 pt-1">

                                        <!-- Typing indicator inside empty assistant message -->
                                        <template x-if="message.streaming && message.searchPhase">
                                            <div class="chat-web-status" :data-phase="message.searchPhase" role="status" aria-live="polite" aria-atomic="true" :aria-label="message.searchPhase === 'answering' ? 'Jawaban sedang ditampilkan' : null">
                                                <span class="chat-web-status-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                        <g x-show="message.searchPhase === 'searching'"><circle cx="12" cy="12" r="8"/><ellipse cx="12" cy="12" rx="3.5" ry="8"/><path d="M4 12h16"/></g>
                                                        <g x-show="message.searchPhase === 'reading'"><path d="M12 6v14M12 6C9 4 6 4 3 5v14c3-1 6-1 9 1 3-2 6-2 9-1V5c-3-1-6-1-9 1Z"/><path d="m6 9 3 1m-3 3 3 1m6-4 3-1m-3 5 3-1"/></g>
                                                        <g x-show="message.searchPhase === 'answering'"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/><path d="m15 5 3 3"/></g>
                                                    </svg>
                                                </span>
                                                <span class="chat-web-status-label" x-show="message.searchStatus" x-text="message.searchStatus"></span>
                                                <span class="chat-web-status-steps" aria-hidden="true"><i></i><i></i><i></i></span>
                                            </div>
                                        </template>
                                        <template x-if="!message.content.trim() && !message.searchPhase">
                                            <div class="flex items-center gap-1.5 pt-2.5">
                                                <span class="h-2 w-2 rounded-full bg-slate-400 animate-[bounce_1s_infinite_0ms]"></span>
                                                <span class="h-2 w-2 rounded-full bg-slate-400 animate-[bounce_1s_infinite_200ms]"></span>
                                                <span class="h-2 w-2 rounded-full bg-slate-400 animate-[bounce_1s_infinite_400ms]"></span>
                                            </div>
                                        </template>

                                        <template x-if="message.content.trim()">
                                            <div x-chat-content="[message.content, message.streaming]"></div>
                                        </template>

                                        <template x-if="message.metadata?.web_sources?.length">
                                            <details class="chat-web-sources">
                                                <summary>Sumber web <span x-text="'(' + message.metadata.web_sources.length + ')'"></span></summary>
                                                <div class="chat-web-source-list">
                                                    <template x-for="source in message.metadata.web_sources" :key="source.url">
                                                        <a :href="webSourceUrl(source.url)" target="_blank" rel="noopener noreferrer">
                                                            <strong x-text="source.title"></strong>
                                                            <span x-text="webSourceHost(source.url)"></span>
                                                            <small x-show="source.published_at" x-text="'Terbit: ' + source.published_at"></small>
                                                            <small x-text="source.read ? 'Isi halaman dibaca' : 'Cuplikan pencarian'"></small>
                                                        </a>
                                                    </template>
                                                </div>
                                            </details>
                                        </template>

                                        <!-- Source Snippets Toggle -->
                                        <template x-if="message.metadata?.source_snippets?.length">
                                            <div class="mt-4 border-t border-slate-100 pt-3">
                                                <button @click="showSources = !showSources"
                                                    class="group flex items-center gap-1.5 text-xs font-semibold text-campus-600 transition hover:text-campus-800">
                                                    <i data-lucide="book-open" class="h-3.5 w-3.5 text-campus-500"></i>
                                                    <span
                                                        x-text="showSources ? 'Sembunyikan Referensi' : 'Lihat Referensi Materi'"></span>
                                                    <i data-lucide="chevron-down"
                                                        class="h-3.5 w-3.5 text-campus-400 transition-transform duration-300"
                                                        :class="showSources ? 'rotate-180' : ''"></i>
                                                </button>

                                                <!-- Sources Content -->
                                                <div x-show="showSources"
                                                    x-transition:enter="transition ease-out duration-200"
                                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                                    x-transition:enter-end="opacity-100 translate-y-0"
                                                    x-transition:leave="transition ease-in duration-150"
                                                    x-transition:leave-start="opacity-100 translate-y-0"
                                                    x-transition:leave-end="opacity-0 -translate-y-2">
                                                    <div class="mt-3 space-y-2">
                                                        <template x-for="snippet in message.metadata.source_snippets"
                                                            :key="snippet.document_id + snippet.snippet">
                                                            <div
                                                                class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-[13px] leading-5 text-slate-600 shadow-sm">
                                                                <div
                                                                    class="mb-1.5 flex items-center gap-1.5 font-semibold text-campus-800">
                                                                    <i data-lucide="file-text"
                                                                        class="h-3.5 w-3.5 text-campus-600"></i>
                                                                    <span x-text="snippet.title"></span>
                                                                </div>
                                                                <p class="italic text-slate-500">"...<span
                                                                        x-text="snippet.snippet"></span>..."</p>
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
            </div>
            <!-- Composer stays outside the message scroller. -->
            <div class="live-chat-composer">
                <form method="POST" action="{{ $storeUrl }}" @submit.prevent="sendQuestion()"
                    @dragover.prevent
                    @drop.prevent="onDrop($event)"
                    class="flex w-full flex-col mx-auto max-w-3xl pointer-events-auto">
                    @csrf

                    <div class="chat-material-chips" x-show="materials.length" x-cloak aria-label="Materi yang akan dikirim">
                        <template x-for="material in materials" :key="material.id">
                            <span class="chat-material-chip">
                                <i data-lucide="file-text" aria-hidden="true"></i>
                                <button type="button" class="chat-material-preview-label" x-text="material.title" :title="material.title" @click="openFilePreview(material)"></button>
                                <button type="button" @click="toggleMaterial(material)" :disabled="sending || materialUploading" :aria-label="'Lepas materi ' + material.title"><i data-lucide="x" aria-hidden="true"></i></button>
                            </span>
                        </template>
                    </div>

                    <!-- Image Preview -->
                    <template x-if="imagePreview">
                        <div class="relative ml-5 mb-3 inline-flex self-start">
                            <button type="button" @click="openImagePreview(imagePreview)" aria-label="Lihat gambar sebelum dikirim"><img :src="imagePreview" alt="Gambar yang akan dikirim" class="h-20 w-20 object-cover rounded-2xl border-2 border-white shadow-md"></button>
                            <button type="button" @click="clearImage()" class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-white hover:bg-slate-800 shadow-md transition-all active:scale-90">
                                <i data-lucide="x" class="h-3.5 w-3.5"></i>
                            </button>
                        </div>
                    </template>

                    <div
                        class="live-chat-input relative flex min-w-0 items-end gap-2 bg-white">
                        
                        <input type="file" x-ref="imageInput" @change="onImageSelected" accept="image/*" class="hidden">
                        
                        <button type="button" @click="triggerSelectImage()" aria-label="Lampirkan gambar" 
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-slate-400 hover:text-slate-600 transition active:scale-95">
                            <i data-lucide="image" class="h-5 w-5"></i>
                        </button>

                        <button type="button" @click="openMaterials()" :disabled="sending || materialUploading" aria-label="Tambahkan materi" title="Tambahkan materi" class="chat-material-trigger">
                            <i data-lucide="paperclip" aria-hidden="true"></i>
                        </button>

                        <textarea aria-label="Pertanyaan untuk AI" x-ref="textarea" x-model="question" name="question" rows="1" maxlength="2000"
                            @paste="onPaste($event)"
                            class="max-h-32 min-h-[44px] w-full resize-none border-0 bg-transparent py-3 pl-2 pr-2 text-[15px] focus:ring-0 focus:outline-none"
                            placeholder="{{ $source ? 'Tanya tentang materi...' : 'Tanya apa saja...' }}"
                            @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); sendQuestion(); }"
                            @input="$el.style.height = 'auto'; $el.style.height = ($el.scrollHeight) + 'px';"
                            style="border: none !important; background: transparent !important; box-shadow: none !important; outline: none !important; resize: none !important;"></textarea>

                        <button type="submit" aria-label="Kirim pertanyaan" :disabled="(!question.trim() && !imageFile && !materials.length) || sending || materialUploading"
                            :class="{ 'is-sending !cursor-wait !bg-campus-600 !text-white opacity-95': sending }"
                            class="group flex h-11 w-11 shrink-0 items-center justify-center p-0 rounded-full bg-slate-900 text-white shadow-sm transition-all hover:bg-slate-800 active:scale-95 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            <template x-if="sending">
                                <svg class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3.5"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <template x-if="!sending">
                                <i data-lucide="arrow-up" class="h-5 w-5"></i>
                            </template>
                            <span class="sr-only" x-text="sending ? 'Sedang memproses jawaban...' : 'Kirim'"></span>
                        </button>
                    </div>
                    <p class="live-chat-hint">AI bisa keliru, cek lagi dengan materimu</p>
                </form>
            </div>
        </div>
        <x-chat-material-picker />
        <x-chat-attachment-preview />
    </div>
</x-app-layout>
