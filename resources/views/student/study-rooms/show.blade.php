<x-app-layout title="Room Belajar Bareng">
    @php
        $target = $room->target;
        $isFolder = $target instanceof \App\Models\DocumentFolder;
    @endphp

    <script>
        window.studyRoomPage = function () {
            return {
                roomId: @js($room->id),
                roomUuid: @js($room->uuid),
                userId: @js(auth()->id()),
                isHost: @js($room->host_id === auth()->id()),
                messages: @js($room->messages->map(fn($m) => [
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'user_name' => $m->is_ai ? 'RuangBelajar AI' : ($m->user ? $m->user->name : 'Siswa'),
                    'message' => $m->message,
                    'is_ai' => (bool)$m->is_ai,
                    'created_at' => $m->created_at->format('H:i'),
                ])->values()),
                onlineUsers: [],
                messageText: '',
                sending: false,
                showSidebar: false,
                showCloseConfirmationModal: false,
                lastMessageId: @js($room->messages->last()?->id ?? 0),
                _pollInterval: null,

                init() {
                    this.scrollToBottom();
                    
                    // Listen to Reverb Presence Channel
                    Echo.join('study-room.' + this.roomId)
                        .here((users) => {
                            this.onlineUsers = users;
                        })
                        .joining((user) => {
                            if (!this.onlineUsers.some(u => u.id === user.id)) {
                                this.onlineUsers.push(user);
                            }
                        })
                        .leaving((user) => {
                            this.onlineUsers = this.onlineUsers.filter(u => u.id !== user.id);
                        })
                        .listen('.message.sent', (e) => {
                            // Only append if it's from another user or it's an AI message, and not already in the list
                            if ((e.message.user_id !== this.userId || e.message.is_ai) && !this.messages.some(m => m.id === e.message.id)) {
                                this.messages.push({
                                    id: e.message.id,
                                    user_id: e.message.user_id,
                                    user_name: e.message.is_ai ? 'RuangBelajar AI' : (e.message.user ? e.message.user.name : 'Siswa'),
                                    message: e.message.message,
                                    is_ai: !!e.message.is_ai,
                                    created_at: new Date(e.message.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                                });
                                this.scrollToBottom();
                            }

                            // If AI message arrives via WebSocket, clear sending indicator
                            if (e.message.is_ai) {
                                this.sending = false;
                            }
                        });

                    // Start polling as reliable fallback for other participants
                    this._pollInterval = setInterval(() => this.pollMessages(), 3000);
                },

                async pollMessages() {
                    try {
                        const res = await fetch(`/study-rooms/${this.roomUuid}/messages?after_id=${this.lastMessageId}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (data.messages && data.messages.length > 0) {
                            let added = false;
                            data.messages.forEach(msg => {
                                if (!this.messages.some(m => m.id === msg.id)) {
                                    this.messages.push(msg);
                                    added = true;
                                }
                                if (msg.id > this.lastMessageId) {
                                    this.lastMessageId = msg.id;
                                }
                            });
                            if (added) {
                                this.scrollToBottom();
                            }
                            // Clear sending indicator if AI message arrived
                            if (data.messages.some(m => m.is_ai)) {
                                this.sending = false;
                            }
                        }
                    } catch (e) {
                        // Silent fail — polling is just a fallback
                    }
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        if (this.$refs.messages) {
                            this.$refs.messages.scrollTo({ top: this.$refs.messages.scrollHeight, behavior: 'smooth' });
                        }
                    });
                },
                
                isOnline(userId) {
                    return this.onlineUsers.some(u => parseInt(u.id) === parseInt(userId));
                },
                
                async sendMessage() {
                    const text = this.messageText.trim();
                    if (!text || this.sending) return;
                    
                    // Append message locally immediately to feel super snappy
                    const tempId = 'temp-' + Date.now();
                    this.messages.push({
                        id: tempId,
                        user_id: this.userId,
                        user_name: 'Saya',
                        message: text,
                        is_ai: false,
                        created_at: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                    });
                    
                    this.messageText = '';
                    this.sending = true;
                    this.scrollToBottom();
                    
                    try {
                        const response = await fetch(`/study-rooms/${this.roomUuid}/messages`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({ message: text }),
                        });
                        
                        if (!response.ok) {
                            throw new Error('Gagal mengirim pesan.');
                        }

                        const data = await response.json();
                        if (data.status === 'success') {
                            // Replace the temp message with the actual saved user message to get the real database ID
                            this.messages = this.messages.map(m => m.id === tempId ? {
                                id: data.user_message.id,
                                user_id: data.user_message.user_id,
                                user_name: 'Saya',
                                message: data.user_message.message,
                                is_ai: false,
                                created_at: new Date(data.user_message.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                            } : m);

                            // Append the AI message if it is not already in the messages list
                            if (data.ai_message && !this.messages.some(m => m.id === data.ai_message.id)) {
                                this.messages.push({
                                    id: data.ai_message.id,
                                    user_id: null,
                                    user_name: 'RuangBelajar AI',
                                    message: data.ai_message.message,
                                    is_ai: true,
                                    created_at: new Date(data.ai_message.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                                });
                            }
                            // Advance the polling cursor so next poll skips already-shown messages
                            if (data.ai_message && data.ai_message.id > this.lastMessageId) {
                                this.lastMessageId = data.ai_message.id;
                            } else if (data.user_message && data.user_message.id > this.lastMessageId) {
                                this.lastMessageId = data.user_message.id;
                            }
                            this.sending = false;
                            this.scrollToBottom();
                        }
                    } catch (error) {
                        console.error(error);
                        // Remove the local temp message
                        this.messages = this.messages.filter(m => m.id !== tempId);
                        alert('Gagal mengirim pesan. Silakan coba lagi.');
                        this.sending = false;
                    }
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

                        if (/^\|/.test(trimmed)) {
                            tableBuffer.push(trimmed);
                            continue;
                        }

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

                    if (tableBuffer.length > 0) result.push(flushTable());
                    result.push(closeList());

                    return result.filter(Boolean).join('');
                }
            };
        };
    </script>

    <div class="h-full w-full flex flex-col lg:flex-row bg-[#f8fbff] min-h-0 overflow-hidden" x-data="studyRoomPage()" x-init="init()">
        
        <!-- Left Side: Chat Workspace (Takes full space on mobile, major space on desktop) -->
        <div class="flex-1 flex flex-col min-w-0 min-h-0 border-r border-slate-200/60 bg-white">
            
            <!-- Chat Header -->
            <section class="flex-none px-4 py-3 sm:px-6 flex items-center justify-between border-b border-slate-200/60 bg-white/80 backdrop-blur-md shadow-sm">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <a class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-campus-700 shadow-sm transition hover:bg-campus-50 border border-slate-200" href="{{ route('study-rooms.index') }}" title="Kembali ke Ruang Belajar">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    </a>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Sesi Belajar Bareng
                        </div>
                        <h1 class="text-sm sm:text-base font-bold text-slate-800 flex items-center gap-1.5 min-w-0">
                            <i data-lucide="{{ $isFolder ? 'folder' : 'file-text' }}" class="h-4 w-4 text-campus-600 shrink-0"></i>
                            <span class="truncate">{{ $target->name ?? $target->title }}</span>
                        </h1>
                    </div>
                </div>
                
                <div class="flex items-center gap-2 shrink-0">
                    <!-- Detail Button (opens mobile drawer) -->
                    <button @click="showSidebar = true" type="button" class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        <i data-lucide="users" class="h-3.5 w-3.5 text-slate-500"></i>
                        <span>Detail</span>
                    </button>

                    <!-- PIN badge -->
                    <span class="px-3 py-1.5 rounded-lg bg-campus-50 border border-campus-100 text-campus-700 font-bold text-sm tracking-wider">
                        PIN: {{ $room->pin }}
                    </span>
                </div>
            </section>

            <!-- Chat Canvas -->
            <div class="flex-1 flex flex-col relative min-h-0 overflow-hidden">
                <!-- Messages Area -->
                <div x-ref="messages" class="flex-1 space-y-8 overflow-y-auto pb-44 pt-6 px-4 sm:px-6" style="scroll-behavior: smooth;">
                    
                    <!-- Welcome Room Banner -->
                    <div class="rounded-2xl border border-campus-100 bg-campus-50/50 p-6 max-w-2xl mx-auto text-center shadow-sm">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-campus-100 text-campus-600">
                            <i data-lucide="users" class="h-6 w-6"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">Selamat Datang di Room Belajar Bareng!</h3>
                        <p class="text-sm text-slate-600 mt-1.5 leading-relaxed">
                            Di room ini, kamu dan teman-temanmu bisa berdiskusi mengenai materi 
                            <strong class="text-campus-900">"{{ $target->name ?? $target->title }}"</strong>.
                            Ketik pesan di bawah, dan asisten AI kita juga akan membantu menjawab pertanyaan kalian.
                        </p>
                    </div>


                    <!-- Chat Bubbles -->
                    <template x-for="message in messages" :key="message.id">
                        <div class="flex w-full justify-center">
                            <div class="w-full max-w-3xl flex flex-col" :class="message.user_id === userId ? 'items-end' : 'items-start'">
                                
                                <!-- User Tag (Name and Time) -->
                                <div class="flex items-center gap-2 mb-1 text-xs text-slate-400 font-medium px-2">
                                    <span class="font-semibold text-slate-600" x-text="message.user_id === userId ? 'Saya' : message.user_name"></span>
                                    <span>•</span>
                                    <span x-text="message.created_at"></span>
                                </div>

                                <div class="w-full flex" :class="message.user_id === userId ? 'justify-end pl-12' : 'justify-start pr-4 sm:pr-12'">
                                    <!-- User Bubble -->
                                    <template x-if="!message.is_ai">
                                        <div class="max-w-[85%] rounded-2xl px-4 py-3 text-[14px] leading-relaxed shadow-sm"
                                             :class="message.user_id === userId ? 'bg-campus-600 text-white' : 'bg-slate-100 text-slate-800'">
                                            <div x-html="formatMessage(message.message)" class="[&_p]:mb-0"></div>
                                        </div>
                                    </template>

                                    <!-- AI Bubble -->
                                    <template x-if="message.is_ai">
                                        <div class="flex w-full gap-3">
                                            <div class="shrink-0 mt-1">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-50 text-amber-600 shadow-sm ring-1 ring-amber-100">
                                                    <i data-lucide="bot" class="h-5 w-5"></i>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1 text-[14px] leading-relaxed text-slate-800 pt-1 [&_p]:mb-4 [&_p:last-child]:mb-0 [&_ul]:mb-4 [&_ul]:ml-5 [&_ul]:list-disc [&_ol]:mb-4 [&_ol]:ml-5 [&_ol]:list-decimal [&_strong]:font-bold [&_strong]:text-slate-900 [&_em]:italic [&_pre]:my-4 [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:bg-slate-800 [&_pre]:p-4 [&_pre]:text-[13px] [&_pre]:text-slate-50 [&_table]:my-4 [&_table]:w-full [&_table]:border-collapse [&_table]:rounded-xl [&_table]:overflow-hidden [&_td]:border [&_td]:border-slate-200 [&_td]:p-3 [&_th]:border [&_th]:border-slate-200 [&_th]:bg-slate-50 [&_th]:p-3 [&_th]:text-left">
                                                <div x-html="formatMessage(message.message)"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Typing Indicator -->
                    <div x-show="sending" x-cloak class="flex w-full justify-center">
                        <div class="w-full max-w-3xl flex justify-start pr-4 sm:pr-12">
                            <div class="flex gap-4 sm:gap-6 w-full">
                                <div class="mt-1 shrink-0">
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
                <div class="absolute bottom-0 left-0 right-0 px-3 pt-6 sm:px-4 pointer-events-none" style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom, 1.5rem)); background: linear-gradient(to top, white 60%, transparent);">
                    <form @submit.prevent="sendMessage()" class="flex w-full flex-col mx-auto max-w-3xl pointer-events-auto">
                        <div class="relative flex min-w-0 flex-1 items-end gap-2 rounded-[2rem] bg-white p-2 shadow-xl shadow-black/8 ring-1 ring-slate-200 focus-within:ring-2 focus-within:ring-campus-300 transition-all duration-200">
                            <textarea 
                                x-model="messageText" 
                                rows="1"
                                class="max-h-32 min-h-[44px] w-full resize-none border-0 bg-transparent py-3 pl-5 pr-2 text-[14px] focus:ring-0 focus:outline-none" 
                                placeholder="Ketik pesan untuk didiskusikan..." 
                                @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                                @input="$el.style.height = 'auto'; $el.style.height = ($el.scrollHeight) + 'px';"
                                style="border: none !important; background: transparent !important; box-shadow: none !important; outline: none !important; resize: none !important; overflow-y: hidden;"
                            ></textarea>
                            
                            <button type="submit" :disabled="!messageText.trim() || sending" class="group flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white shadow-sm transition-all hover:bg-slate-800 active:scale-95 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                                <i data-lucide="send" class="h-4 w-4"></i>
                                <span class="sr-only">Kirim</span>
                            </button>
                        </div>
                        <div class="mt-2 text-center text-[10px] font-medium text-slate-400">
                            Pesan kamu akan disiarkan langsung ke semua anggota di room ini. AI akan merespons secara real-time.
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Side: Sidebar Info & Participants (Desktop only) -->
        <div class="hidden lg:flex w-80 shrink-0 bg-[#f8fbff] p-6 flex-col gap-6 min-h-0 overflow-y-auto border-l border-slate-200/60">
            
            <!-- Room Code Card -->
            <div class="rounded-2xl bg-gradient-to-br from-campus-600 to-campus-800 p-5 text-white shadow-md relative overflow-hidden shrink-0">
                <div class="absolute right-0 bottom-0 opacity-10 pointer-events-none transform translate-x-2 translate-y-2">
                    <i data-lucide="users" class="w-24 h-24"></i>
                </div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-campus-200">PIN ROOM</span>
                <h2 class="text-3xl font-black mt-1 tracking-widest">{{ $room->pin }}</h2>
                <p class="text-xs text-campus-100 mt-2">Bagikan PIN ini ke teman agar bisa bergabung dan belajar bersama.</p>
            </div>

            <!-- Material Card -->
            <div class="rounded-2xl bg-white border border-slate-200/85 p-4 shadow-sm shrink-0">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Materi Pembahasan</h4>
                <div class="flex items-start gap-3">
                    <div class="shrink-0 p-2 rounded-xl bg-campus-50 text-campus-700">
                        <i data-lucide="{{ $isFolder ? 'folder' : 'file-text' }}" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-slate-800 leading-snug break-words">
                            {{ $target->name ?? $target->title }}
                        </span>
                        <span class="block text-xs text-slate-500 mt-0.5">
                            {{ $isFolder ? $target->documents_count . ' File' : strtoupper($target->extension) . ' • ' . number_format($target->size / 1024 / 1024, 2) . ' MB' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Fitur Belajar AI Card -->
            <div class="rounded-2xl bg-white border border-slate-200/85 p-4 shadow-sm shrink-0" x-data="{ quizOpen: false, quizLoading: false, quizType: 'multiple_choice', quizCount: 10, fcLoading: false }">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Aktivitas Belajar AI</h4>
                
                <div class="space-y-3">
                    <!-- Flashcard Section -->
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <i data-lucide="copy-check" class="h-4 w-4 text-purple-600"></i>
                                Kartu Belajar
                            </span>
                            @php
                                $fcCount = $target->flashcards->count();
                                $fcRoute = $isFolder ? route('folders.flashcards.index', $target) : route('flashcards.index', $target);
                                $fcStoreRoute = $isFolder ? route('folders.flashcards.store', $target) : route('flashcards.store', $target);
                            @endphp
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700">
                                {{ $fcCount }} Kartu
                            </span>
                        </div>
                        
                        @if($fcCount > 0)
                            <a href="{{ $fcRoute }}?room={{ $room->uuid }}" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-purple-600 text-white text-xs font-bold hover:bg-purple-700 transition shadow-sm">
                                <i data-lucide="play" class="h-3.5 w-3.5"></i>
                                Mulai Latihan Kartu
                            </a>
                        @else
                            <form method="POST" action="{{ $fcStoreRoute }}?room={{ $room->uuid }}" x-on:submit="fcLoading = true">
                                @csrf
                                <button type="submit" :disabled="fcLoading" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-purple-50 text-purple-700 text-xs font-bold hover:bg-purple-100 transition border border-purple-100 disabled:opacity-75 disabled:cursor-wait">
                                    <template x-if="!fcLoading">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                            Buat Flashcard Baru
                                        </span>
                                    </template>
                                    <template x-if="fcLoading">
                                        <span class="flex items-center gap-1.5">
                                            <span class="h-3.5 w-3.5 animate-spin rounded-full border border-purple-200 border-t-purple-700"></span>
                                            Membuat Kartu...
                                        </span>
                                    </template>
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Quizzes Section -->
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <i data-lucide="list-checks" class="h-4 w-4 text-emerald-600"></i>
                                Latihan Soal
                            </span>
                            @php
                                $quizzes = $target->quizzes->where('study_room_id', $room->id);
                                $quizCount = $quizzes->count();
                                $quizStoreRoute = $isFolder ? route('folders.quizzes.store', $target) : route('quizzes.store', $target);
                            @endphp
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">
                                {{ $quizCount }} Soal
                            </span>
                        </div>

                        <!-- Existing Quizzes List -->
                        @if($quizCount > 0)
                            <div class="max-h-28 overflow-y-auto mb-2 space-y-1.5 pr-1 font-sans">
                                @foreach($quizzes as $quiz)
                                    <a href="{{ route('quizzes.show', $quiz) }}?room={{ $room->uuid }}" class="block p-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-[11px] text-slate-700 font-semibold truncate transition">
                                        {{ $quiz->title }}
                                    </a>
                                @endforeach
                            </div>
                        @endif                        <!-- Create Quiz Form Toggle -->
                        <button type="button" x-show="!quizOpen" @click="quizOpen = true" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold hover:bg-emerald-100 transition border border-emerald-100">
                            <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                            Buat Soal Latihan Baru
                        </button>

                        <div x-show="quizOpen" x-cloak class="mt-2 pt-2 border-t border-slate-200">
                            <form method="POST" action="{{ $quizStoreRoute }}?room={{ $room->uuid }}" x-on:submit="quizLoading = true">
                                @csrf
                                <input type="hidden" name="question_type" x-model="quizType">
                                <input type="hidden" name="question_count" x-model="quizCount">
                                
                                <div class="grid grid-cols-2 gap-1.5 rounded-full bg-slate-200/60 p-0.5 mb-2">
                                    <button type="button" @click="quizType='multiple_choice'" class="h-7 rounded-full text-[10px] font-bold transition" :class="quizType === 'multiple_choice' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500'">PG</button>
                                    <button type="button" @click="quizType='essay'" class="h-7 rounded-full text-[10px] font-bold transition" :class="quizType === 'essay' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500'">Esai</button>
                                </div>

                                <div class="flex items-center justify-between gap-2 bg-white px-2 py-1.5 rounded-lg border border-slate-200 mb-2">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Jumlah</span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="quizCount = Math.max(1, quizCount - 1)" class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-slate-600"><i data-lucide="minus" class="h-3 w-3"></i></button>
                                        <span class="w-6 text-center text-xs font-bold text-slate-800" x-text="quizCount">10</span>
                                        <button type="button" @click="quizCount = Math.min(30, quizCount + 1)" class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-slate-600"><i data-lucide="plus" class="h-3 w-3"></i></button>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" @click="quizOpen = false" :disabled="quizLoading" class="flex-1 h-9 rounded-lg bg-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-300 transition disabled:opacity-50">Batal</button>
                                    <button type="submit" :disabled="quizLoading" class="flex-[2] h-9 inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition shadow-sm disabled:opacity-75 disabled:cursor-wait">
                                        <template x-if="!quizLoading">
                                            <span>Mulai Soal</span>
                                        </template>
                                        <template x-if="quizLoading">
                                            <span class="flex items-center gap-1">
                                                <span class="h-3.5 w-3.5 animate-spin rounded-full border border-emerald-200 border-t-white"></span>
                                                Proses...
                                            </span>
                                        </template>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Members Box -->
            <div class="rounded-2xl bg-white border border-slate-200/85 p-4 shadow-sm shrink-0">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Anggota Sesi</h4>
                    <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span x-text="onlineUsers.length">0</span> Online
                    </span>
                </div>
                
                <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                    
                    <!-- Host Item -->
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($room->host->name) }}&background=1456a3&color=fff" 
                                 class="h-7 w-7 rounded-full shadow-sm shrink-0" 
                                 alt="{{ $room->host->name }}">
                            <div class="min-w-0">
                                <span class="block text-xs font-bold text-slate-700 truncate leading-tight">
                                    {{ $room->host->name }}
                                </span>
                                <span class="inline-block text-[9px] font-bold text-campus-700 uppercase tracking-wider">
                                    Host / Pembuat
                                </span>
                            </div>
                        </div>
                        <span class="h-2 w-2 rounded-full shrink-0 transition-colors duration-300"
                              :class="isOnline({{ $room->host_id }}) ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-slate-300'">
                        </span>
                    </div>

                    <!-- Participant List -->
                    @if($room->users->isEmpty())
                        <div class="text-center py-6 text-xs text-slate-400">
                            Belum ada teman yang bergabung.
                        </div>
                    @else
                        @foreach($room->users as $participant)
                            <div class="flex items-center justify-between p-2 rounded-xl bg-white hover:bg-slate-50 transition border border-transparent">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($participant->name) }}&background=7c5cff&color=fff" 
                                         class="h-7 w-7 rounded-full shadow-sm shrink-0" 
                                         alt="{{ $participant->name }}">
                                    <div class="min-w-0">
                                        <span class="block text-xs font-semibold text-slate-700 truncate leading-tight">
                                            {{ $participant->name }}
                                        </span>
                                        <span class="block text-[9px] text-slate-400 font-medium">
                                            Siswa
                                        </span>
                                    </div>
                                </div>
                                <span class="h-2 w-2 rounded-full shrink-0 transition-colors duration-300"
                                      :class="isOnline({{ $participant->id }}) ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-slate-300'">
                                </span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Action Buttons at bottom -->
            <div class="shrink-0 mt-auto">
                @if($room->host_id === auth()->id())
                    <button type="button" @click="showCloseConfirmationModal = true" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-rose-50 px-4 text-xs font-bold text-rose-600 border border-rose-100 hover:bg-rose-100 transition shadow-sm">
                        <i data-lucide="power" class="h-4 w-4"></i>
                        Tutup Sesi Belajar
                    </button>
                @else
                    <a href="{{ route('study-rooms.index') }}" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 text-xs font-bold text-slate-700 hover:bg-slate-200 transition border border-slate-200 shadow-sm">
                        <i data-lucide="log-out" class="h-4 w-4"></i>
                        Keluar Room
                    </a>
                @endif
            </div>

        </div>

        <!-- Mobile Sidebar Slide-over Drawer -->
        <div x-show="showSidebar" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <!-- Backdrop overlay -->
            <div 
                x-show="showSidebar"
                x-transition:enter="transition-opacity ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"
                @click="showSidebar = false"
            ></div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <div 
                            x-show="showSidebar"
                            x-transition:enter="transform transition ease-out duration-300 sm:duration-300"
                            x-transition:enter-start="translate-x-full"
                            x-transition:enter-end="translate-x-0"
                            x-transition:leave="transform transition ease-in duration-200 sm:duration-200"
                            x-transition:leave-start="translate-x-0"
                            x-transition:leave-end="translate-x-full"
                            class="pointer-events-auto w-screen max-w-md"
                        >
                            <div class="flex h-full flex-col bg-[#f8fbff] shadow-2xl">
                                <!-- Drawer Header -->
                                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4 sm:px-6 bg-white shrink-0">
                                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <i data-lucide="info" class="h-5 w-5 text-slate-500"></i>
                                        Detail Room Belajar
                                    </h2>
                                    <button @click="showSidebar = false" type="button" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-500">
                                        <i data-lucide="x" class="h-5 w-5"></i>
                                    </button>
                                </div>

                                <!-- Drawer Body (scrollable) -->
                                <div class="flex-1 overflow-y-auto p-4 sm:p-6 flex flex-col gap-6">
                                    <!-- Room Code Card -->
                                    <div class="rounded-2xl bg-gradient-to-br from-campus-600 to-campus-800 p-5 text-white shadow-md relative overflow-hidden shrink-0">
                                        <div class="absolute right-0 bottom-0 opacity-10 pointer-events-none transform translate-x-2 translate-y-2">
                                            <i data-lucide="users" class="w-24 h-24"></i>
                                        </div>
                                        <span class="block text-[10px] font-bold uppercase tracking-wider text-campus-200">PIN ROOM</span>
                                        <h2 class="text-3xl font-black mt-1 tracking-widest">{{ $room->pin }}</h2>
                                        <p class="text-xs text-campus-100 mt-2">Bagikan PIN ini ke teman agar bisa bergabung dan belajar bersama.</p>
                                    </div>

                                    <!-- Material Card -->
                                    <div class="rounded-2xl bg-white border border-slate-200/85 p-4 shadow-sm shrink-0">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Materi Pembahasan</h4>
                                        <div class="flex items-start gap-3">
                                            <div class="shrink-0 p-2 rounded-xl bg-campus-50 text-campus-700">
                                                <i data-lucide="{{ $isFolder ? 'folder' : 'file-text' }}" class="h-5 w-5"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <span class="block text-sm font-bold text-slate-800 leading-snug break-words">
                                                    {{ $target->name ?? $target->title }}
                                                </span>
                                                <span class="block text-xs text-slate-500 mt-0.5">
                                                    {{ $isFolder ? $target->documents_count . ' File' : strtoupper($target->extension) . ' • ' . number_format($target->size / 1024 / 1024, 2) . ' MB' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Fitur Belajar AI Card -->
                                    <div class="rounded-2xl bg-white border border-slate-200/85 p-4 shadow-sm shrink-0" x-data="{ quizOpen: false, quizLoading: false, quizType: 'multiple_choice', quizCount: 10, fcLoading: false }">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Aktivitas Belajar AI</h4>
                                        
                                        <div class="space-y-3">
                                            <!-- Flashcard Section -->
                                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                                        <i data-lucide="copy-check" class="h-4 w-4 text-purple-600"></i>
                                                        Kartu Belajar
                                                    </span>
                                                    @php
                                                        $fcCount = $target->flashcards->count();
                                                        $fcRoute = $isFolder ? route('folders.flashcards.index', $target) : route('flashcards.index', $target);
                                                        $fcStoreRoute = $isFolder ? route('folders.flashcards.store', $target) : route('flashcards.store', $target);
                                                    @endphp
                                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700">
                                                        {{ $fcCount }} Kartu
                                                    </span>
                                                </div>
                                                
                                                @if($fcCount > 0)
                                                    <a href="{{ $fcRoute }}?room={{ $room->uuid }}" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-purple-600 text-white text-xs font-bold hover:bg-purple-700 transition shadow-sm">
                                                        <i data-lucide="play" class="h-3.5 w-3.5"></i>
                                                        Mulai Latihan Kartu
                                                    </a>
                                                @else
                                                    <form method="POST" action="{{ $fcStoreRoute }}?room={{ $room->uuid }}" x-on:submit="fcLoading = true">
                                                        @csrf
                                                        <button type="submit" :disabled="fcLoading" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-purple-50 text-purple-700 text-xs font-bold hover:bg-purple-100 transition border border-purple-100 disabled:opacity-75 disabled:cursor-wait">
                                                            <template x-if="!fcLoading">
                                                                <span class="flex items-center gap-1.5">
                                                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                                                    Buat Flashcard Baru
                                                                </span>
                                                            </template>
                                                            <template x-if="fcLoading">
                                                                <span class="flex items-center gap-1.5">
                                                                    <span class="h-3.5 w-3.5 animate-spin rounded-full border border-purple-200 border-t-purple-700"></span>
                                                                    Membuat Kartu...
                                                                </span>
                                                            </template>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>

                                            <!-- Quizzes Section -->
                                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                                        <i data-lucide="list-checks" class="h-4 w-4 text-emerald-600"></i>
                                                        Latihan Soal
                                                    </span>
                                                    @php
                                                        $quizzes = $target->quizzes->where('study_room_id', $room->id);
                                                        $quizCount = $quizzes->count();
                                                        $quizStoreRoute = $isFolder ? route('folders.quizzes.store', $target) : route('quizzes.store', $target);
                                                    @endphp
                                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">
                                                        {{ $quizCount }} Soal
                                                    </span>
                                                </div>

                                                <!-- Existing Quizzes List -->
                                                @if($quizCount > 0)
                                                    <div class="max-h-28 overflow-y-auto mb-2 space-y-1.5 pr-1 font-sans">
                                                        @foreach($quizzes as $quiz)
                                                            <a href="{{ route('quizzes.show', $quiz) }}?room={{ $room->uuid }}" class="block p-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-[11px] text-slate-700 font-semibold truncate transition">
                                                                {{ $quiz->title }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Create Quiz Form Toggle -->
                                                <button type="button" x-show="!quizOpen" @click="quizOpen = true" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold hover:bg-emerald-100 transition border border-emerald-100">
                                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                                    Buat Soal Latihan Baru
                                                </button>

                                                <div x-show="quizOpen" x-cloak class="mt-2 pt-2 border-t border-slate-200">
                                                    <form method="POST" action="{{ $quizStoreRoute }}?room={{ $room->uuid }}" x-on:submit="quizLoading = true">
                                                        @csrf
                                                        <input type="hidden" name="question_type" x-model="quizType">
                                                        <input type="hidden" name="question_count" x-model="quizCount">
                                                        
                                                        <div class="grid grid-cols-2 gap-1.5 rounded-full bg-slate-200/60 p-0.5 mb-2">
                                                            <button type="button" @click="quizType='multiple_choice'" class="h-7 rounded-full text-[10px] font-bold transition" :class="quizType === 'multiple_choice' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500'">PG</button>
                                                            <button type="button" @click="quizType='essay'" class="h-7 rounded-full text-[10px] font-bold transition" :class="quizType === 'essay' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500'">Esai</button>
                                                        </div>

                                                        <div class="flex items-center justify-between gap-2 bg-white px-2 py-1.5 rounded-lg border border-slate-200 mb-2">
                                                            <span class="text-[10px] font-bold text-slate-500 uppercase">Jumlah</span>
                                                            <div class="flex items-center gap-1">
                                                                <button type="button" @click="quizCount = Math.max(1, quizCount - 1)" class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-slate-600"><i data-lucide="minus" class="h-3 w-3"></i></button>
                                                                <span class="w-6 text-center text-xs font-bold text-slate-800" x-text="quizCount">10</span>
                                                                <button type="button" @click="quizCount = Math.min(30, quizCount + 1)" class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-slate-600"><i data-lucide="plus" class="h-3 w-3"></i></button>
                                                            </div>
                                                        </div>

                                                        <div class="flex gap-2">
                                                            <button type="button" @click="quizOpen = false" :disabled="quizLoading" class="flex-1 h-9 rounded-lg bg-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-300 transition disabled:opacity-50">Batal</button>
                                                            <button type="submit" :disabled="quizLoading" class="flex-[2] h-9 inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition shadow-sm disabled:opacity-75 disabled:cursor-wait">
                                                                <template x-if="!quizLoading">
                                                                    <span>Mulai Soal</span>
                                                                </template>
                                                                <template x-if="quizLoading">
                                                                    <span class="flex items-center gap-1">
                                                                        <span class="h-3.5 w-3.5 animate-spin rounded-full border border-emerald-200 border-t-white"></span>
                                                                        Proses...
                                                                    </span>
                                                                </template>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Active Members Box -->
                                    <div class="rounded-2xl bg-white border border-slate-200/85 p-4 shadow-sm flex flex-col gap-3">
                                        <div class="flex items-center justify-between shrink-0">
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Anggota Sesi</h4>
                                            <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <span x-text="onlineUsers.length">0</span> Online
                                            </span>
                                        </div>
                                        
                                        <div class="space-y-3">
                                            
                                            <!-- Host Item -->
                                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($room->host->name) }}&background=1456a3&color=fff" 
                                                         class="h-7 w-7 rounded-full shadow-sm shrink-0" 
                                                         alt="{{ $room->host->name }}">
                                                    <div class="min-w-0">
                                                        <span class="block text-xs font-bold text-slate-700 truncate leading-tight">
                                                            {{ $room->host->name }}
                                                        </span>
                                                        <span class="inline-block text-[9px] font-bold text-campus-700 uppercase tracking-wider">
                                                            Host / Pembuat
                                                        </span>
                                                    </div>
                                                </div>
                                                <span class="h-2 w-2 rounded-full shrink-0 transition-colors duration-300"
                                                      :class="isOnline({{ $room->host_id }}) ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-slate-300'">
                                                </span>
                                            </div>

                                            <!-- Participant List -->
                                            @if($room->users->isEmpty())
                                                <div class="text-center py-6 text-xs text-slate-400">
                                                    Belum ada teman yang bergabung.
                                                </div>
                                            @else
                                                @foreach($room->users as $participant)
                                                    <div class="flex items-center justify-between p-2 rounded-xl bg-white hover:bg-slate-50 transition border border-transparent">
                                                        <div class="flex items-center gap-2.5 min-w-0">
                                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($participant->name) }}&background=7c5cff&color=fff" 
                                                                 class="h-7 w-7 rounded-full shadow-sm shrink-0" 
                                                                 alt="{{ $participant->name }}">
                                                            <div class="min-w-0">
                                                                <span class="block text-xs font-semibold text-slate-700 truncate leading-tight">
                                                                    {{ $participant->name }}
                                                                </span>
                                                                <span class="block text-[9px] text-slate-400 font-medium">
                                                                    Siswa
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <span class="h-2 w-2 rounded-full shrink-0 transition-colors duration-300"
                                                              :class="isOnline({{ $participant->id }}) ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-slate-300'">
                                                        </span>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Action Buttons at bottom inside drawer -->
                                    <div class="shrink-0 mt-auto pt-4">
                                        @if($room->host_id === auth()->id())
                                            <button type="button" @click="showCloseConfirmationModal = true" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-rose-50 px-4 text-xs font-bold text-rose-600 border border-rose-100 hover:bg-rose-100 transition shadow-sm">
                                                <i data-lucide="power" class="h-4 w-4"></i>
                                                Tutup Sesi Belajar
                                            </button>
                                        @else
                                            <a href="{{ route('study-rooms.index') }}" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 text-xs font-bold text-slate-700 hover:bg-slate-200 transition border border-slate-200 shadow-sm">
                                                <i data-lucide="log-out" class="h-4 w-4"></i>
                                                Keluar Room
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden close room form -->
        @if($room->host_id === auth()->id())
            <form id="close-room-form" method="POST" action="{{ route('study-rooms.close', $room) }}" class="hidden">
                @csrf
            </form>
        @endif

        <!-- Custom Close Room Confirmation Modal -->
        <div 
            x-show="showCloseConfirmationModal" 
            x-cloak 
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 animate-fade-in"
            role="dialog"
            aria-modal="true"
        >
            <!-- Backdrop -->
            <div 
                x-show="showCloseConfirmationModal"
                x-transition:enter="transition-opacity ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"
                @click="showCloseConfirmationModal = false"
            ></div>

            <!-- Modal Content -->
            <div 
                x-show="showCloseConfirmationModal"
                x-transition:enter="transition-all ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition-all ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
                class="relative w-full max-w-md transform overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-2xl border border-slate-100 transition-all text-center"
            >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 shadow-inner mb-4">
                    <i data-lucide="power" class="h-7 w-7"></i>
                </div>

                <h3 class="text-lg font-black text-slate-800 tracking-tight">Tutup Sesi Belajar Bareng?</h3>
                <p class="text-sm text-slate-500 mt-2.5 leading-relaxed">
                    Apakah Anda yakin ingin menutup sesi belajar ini? Semua peserta akan dikeluarkan dari room, dan histori kuis sesi ini akan dibersihkan secara otomatis.
                </p>

                <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-2.5">
                    <button 
                        type="button" 
                        @click="showCloseConfirmationModal = false" 
                        class="w-full sm:w-auto min-w-[100px] inline-flex h-11 items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 transition"
                    >
                        Batal
                    </button>
                    <button 
                        type="button" 
                        @click="document.getElementById('close-room-form').submit()" 
                        class="w-full sm:w-auto min-w-[120px] inline-flex h-11 items-center justify-center rounded-xl bg-rose-600 hover:bg-rose-700 text-xs font-bold text-white shadow-lg shadow-rose-600/20 transition"
                    >
                        Ya, Tutup Sesi
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
