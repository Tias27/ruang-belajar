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
                async copyMessage(content) {
                    try {
                        await navigator.clipboard.writeText(content);
                    } catch {
                        const ta = document.createElement('textarea');
                        ta.value = content;
                        ta.style.position = 'fixed';
                        ta.style.opacity = '0';
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                    }
                },
                toast: null,
                _toastTimer: null,
                showToast(msg, duration = 2500) {
                    clearTimeout(this._toastTimer);
                    this.toast = msg;
                    this._toastTimer = setTimeout(() => { this.toast = null; }, duration);
                },
                async exportMessage(content, format, filename = this.title || 'jawaban-ai') {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = @js(route('chat.export'));
                    form.style.display = 'none';
                    const fields = {
                        _token: @js(csrf_token()),
                        content: content,
                        format: format,
                        filename: filename.substring(0, 200),
                    };
                    for (const [k, v] of Object.entries(fields)) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = k;
                        input.value = v;
                        form.appendChild(input);
                    }
                    document.body.appendChild(form);
                    form.submit();
                    setTimeout(() => form.remove(), 1000);
                },
                fileFormat(content) {
                    const match = content.match(/<!-- FILE_DOCUMENT_FILE:([a-z0-9_.-]+) -->|<!-- FILE_DOCUMENT(?::([a-z0-9]+))? -->/i);
                    if (!match) return null;
                    return (match[1]?.split('.').pop() || match[2] || 'md').toLowerCase();
                },
                fileContent(content) {
                    const marker = content.match(/<!-- FILE_DOCUMENT_FILE:[a-z0-9_.-]+ -->|<!-- FILE_DOCUMENT(?::[a-z0-9]+)? -->/i);
                    return marker ? content.slice(marker.index + marker[0].length).trim() : content;
                },
                fileIntroduction(content) {
                    const marker = content.match(/<!-- FILE_DOCUMENT_FILE:[a-z0-9_.-]+ -->|<!-- FILE_DOCUMENT(?::[a-z0-9]+)? -->/i);
                    return marker ? content.slice(0, marker.index).trim() : '';
                },
                fileCards(content) {
                    const markers = [...content.matchAll(/<!-- FILE_DOCUMENT_FILE:([a-z0-9_.-]+) -->|<!-- FILE_DOCUMENT(?::([a-z0-9]+))? -->/gi)];
                    return markers.map((marker, index) => {
                        const start = marker.index + marker[0].length;
                        const end = markers[index + 1]?.index ?? content.length;
                        const body = content.slice(start, end).trim();
                        const format = (marker[1]?.split('.').pop() || marker[2] || 'md').toLowerCase();
                        const fallback = this.fileBaseName(body);
                        return { name: marker[1] || `${fallback || 'Dokumen'}.${format}`, format, content: body };
                    }).filter(file => file.content);
                },
                fileName(content) {
                    const format = this.fileFormat(content);
                    const safeTitle = this.fileBaseName(this.fileContent(content));
                    return `${safeTitle}.${format}`;
                },
                fileBaseName(content) {
                    const heading = content.match(/^#\s+(.+)$/m)?.[1]?.trim();
                    const latestQuestion = [...this.messages].reverse().find(item => item.role === 'user')?.content || '';
                    const source = heading || latestQuestion.replace(/\b(?:tolong|bantu|buatkan|buat|bikin|saya|aku|dong|ya|file|dalam|format|bentuk|pisahkan|dan|untuk|yang|dari|ke|jadikan)\b/gi, ' ');
                    return source.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').slice(0, 80) || 'file';
                },
                displayContent(content) {
                    return this.fileFormat(content) ? this.fileIntroduction(content) : content;
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
                    let assistantIndex = -1;
                    let typeQueue = '';
                    let typingTimer = null;
                    try {
                        this.messages.push({ id: assistantMessageId, role: 'assistant', content: '', metadata: null, streaming: true });
                        assistantIndex = this.messages.length - 1;

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

                                        <template x-if="displayContent(message.content).trim()">
                                            <div x-chat-content="[displayContent(message.content), message.streaming]"></div>
                                        </template>

                                        <!-- File document download card -->
                                        <template x-if="!message.streaming && fileFormat(message.content)">
                                            <div class="space-y-2.5">
                                            <template x-for="file in fileCards(message.content)" :key="file.name">
                                            <div class="chat-file-card">
                                                <div class="chat-file-card-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
                                                </div>
                                                <div class="chat-file-card-info">
                                                    <span class="chat-file-card-title" x-text="file.name"></span>
                                                    <span class="chat-file-card-subtitle">Dokumen</span>
                                                </div>
                                                <div class="chat-file-card-actions">
                                                    <button type="button" class="chat-file-btn" :aria-label="'Unduh ' + file.name" :title="'Unduh ' + file.name" @click="exportMessage(file.content, file.format, file.name.replace(/\.[^.]+$/, '')); showToast('Mengunduh ' + file.format.toUpperCase() + '...')">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            </template>
                                            </div>
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

                                        <!-- Action buttons: shown after streaming completes -->
                                        <template x-if="!message.streaming && message.content.trim() && !fileFormat(message.content)">
                                            <div x-data="{ copied: false, feedback: null, showExport: false }"
                                                class="chat-message-actions">
                                                <!-- Copy -->
                                                <button type="button"
                                                    class="chat-action-btn"
                                                    :class="copied ? 'chat-action-btn--active' : ''"
                                                    :aria-label="copied ? 'Disalin!' : 'Salin jawaban'"
                                                    :title="copied ? 'Disalin!' : 'Salin'"
                                                    @click="await copyMessage(displayContent(message.content)); copied = true; showToast('Jawaban disalin'); setTimeout(() => copied = false, 2000)">
                                                    <template x-if="!copied">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                                    </template>
                                                    <template x-if="copied">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                                    </template>
                                                </button>
                                                <!-- Download -->
                                                <div class="chat-export-wrap" x-show="false">
                                                    <button type="button"
                                                        class="chat-action-btn"
                                                        :class="showExport ? 'chat-action-btn--active' : ''"
                                                        aria-label="Unduh jawaban"
                                                        title="Unduh"
                                                        @click="showExport = !showExport">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                    </button>
                                                    <div class="chat-export-menu" x-show="showExport" x-cloak
                                                        x-transition:enter="transition ease-out duration-150"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="transition ease-in duration-100"
                                                        x-transition:leave-start="opacity-100 scale-100"
                                                        x-transition:leave-end="opacity-0 scale-95">
                                                        <button type="button" @click="exportMessage(message.content, 'md'); showExport = false; showToast('Mengunduh Markdown...')">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                                                            <span>Markdown (.md)</span>
                                                        </button>
                                                        <button type="button" @click="exportMessage(message.content, 'pdf'); showExport = false; showToast('Mengunduh PDF...')">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M10 13H8v4h2a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1Z"/></svg>
                                                            <span>PDF (.pdf)</span>
                                                        </button>
                                                        <button type="button" @click="exportMessage(message.content, 'docx'); showExport = false; showToast('Mengunduh Word...')">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13l2 4 2-4 2 4"/></svg>
                                                            <span>Word (.docx)</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <!-- Thumbs down -->
                                                <button type="button"
                                                    class="chat-action-btn"
                                                    :class="feedback === 'bad' ? 'chat-action-btn--bad' : ''"
                                                    aria-label="Respons buruk"
                                                    title="Respons buruk"
                                                    @click="feedback = feedback === 'bad' ? null : 'bad'; if (feedback === 'bad') showToast('Terima kasih, masukan kamu dicatat')">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"/></svg>
                                                </button>
                                                <!-- Thumbs up -->
                                                <button type="button"
                                                    class="chat-action-btn"
                                                    :class="feedback === 'good' ? 'chat-action-btn--good' : ''"
                                                    aria-label="Respons bagus"
                                                    title="Respons bagus"
                                                    @click="feedback = feedback === 'good' ? null : 'good'; if (feedback === 'good') showToast('Terima kasih, senang bisa membantu!')">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>
                                                </button>
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
                                <svg class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3.5"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <template x-if="!sending">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 19V5"></path>
                                    <path d="m5 12 7-7 7 7"></path>
                                </svg>
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
        <!-- Toast notification -->
        <div x-show="toast" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2" class="chat-toast" x-text="toast" aria-live="polite" role="status"></div>
    </div>
</x-app-layout>
