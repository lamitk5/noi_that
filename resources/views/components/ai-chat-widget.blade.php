<div
    x-data="aiAssistantWidget()"
    x-cloak
    class="ai-assistant-root relative z-50 font-sans"
    @keydown.escape.window="isOpen && (isOpen = false)"
>
    <!-- Floating Trigger Button -->
    <div class="fixed bottom-6 right-6 z-40 flex items-center gap-3">
        <!-- Floating Tooltip / Promo Bubble -->
        <div
            x-show="!isOpen && showTooltip"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="hidden sm:flex items-center gap-2.5 rounded-full bg-surface/95 px-4 py-2 text-xs font-medium text-heading shadow-xl border border-ui-border backdrop-blur-md"
        >
            <span class="inline-flex size-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Cần tư vấn nội thất? Chat cùng <strong>Trợ lý Mộc An</strong></span>
            <button
                type="button"
                @click.stop="showTooltip = false"
                class="ml-1 text-muted hover:text-heading"
                aria-label="Đóng gợi ý"
            >✕</button>
        </div>

        <!-- Main Trigger Button -->
        <button
            type="button"
            @click="toggleChat()"
            class="relative grid size-14 place-items-center rounded-full bg-primary text-primary-foreground shadow-2xl transition duration-300 hover:scale-105 active:scale-95 focus:outline-none focus:ring-4 focus:ring-primary/20"
            aria-label="Mở Trợ lý AI Mộc An"
            :title="isOpen ? 'Thu nhỏ chat' : 'Mở Trợ lý Mộc An'"
        >
            <div x-show="!isOpen" class="flex items-center justify-center">
                <!-- Sparkles / Leaf Icon -->
                <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                </svg>
            </div>
            <div x-show="isOpen" class="flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </div>

            <!-- Pulsing Active Indicator -->
            <span class="absolute -top-1 -right-1 flex size-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full size-3.5 bg-emerald-500 border-2 border-white"></span>
            </span>
        </button>
    </div>

    <!-- Chat Modal / Floating Panel -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        class="fixed bottom-0 right-0 sm:bottom-24 sm:right-6 z-50 w-full sm:w-[440px] sm:max-w-[calc(100vw-32px)] h-[85vh] sm:h-[640px] max-h-[760px] flex flex-col rounded-t-3xl sm:rounded-3xl bg-surface shadow-2xl border border-ui-border overflow-hidden backdrop-blur-xl"
    >
        <!-- Header -->
        <div class="shrink-0 flex items-center justify-between px-4 py-3.5 bg-surface-alt border-b border-ui-border">
            <div class="flex items-center gap-3">
                <div class="relative grid size-10 place-items-center rounded-2xl bg-primary text-primary-foreground shadow-sm">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span class="absolute bottom-0 right-0 size-2.5 rounded-full bg-emerald-500 border border-white"></span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-display font-semibold text-heading text-sm sm:text-base leading-tight">Trợ lý Mộc An</h3>
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary px-1.5 py-0.5 rounded">AI</span>
                    </div>
                    <p class="text-[11px] text-muted leading-tight mt-0.5" x-text="currentConversationTitle || 'Tư vấn nội thất & đơn hàng'"></p>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-1">
                <!-- History Drawer Toggle -->
                <button
                    type="button"
                    @click="showDrawer = !showDrawer; if(showDrawer) loadConversations()"
                    class="p-2 rounded-xl text-muted hover:text-heading hover:bg-surface transition"
                    title="Lịch sử trò chuyện"
                    aria-label="Lịch sử trò chuyện"
                >
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                <!-- New Chat Button -->
                <button
                    type="button"
                    @click="startNewConversation()"
                    class="p-2 rounded-xl text-muted hover:text-heading hover:bg-surface transition"
                    title="Cuộc trò chuyện mới"
                    aria-label="Cuộc trò chuyện mới"
                >
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>

                <!-- Clear Current Chat -->
                <button
                    type="button"
                    @click="clearCurrentConversation()"
                    class="p-2 rounded-xl text-muted hover:text-rose-600 hover:bg-surface transition"
                    title="Xóa nội dung chat này"
                    aria-label="Xóa nội dung chat này"
                >
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </button>

                <!-- Close Button -->
                <button
                    type="button"
                    @click="isOpen = false"
                    class="p-2 rounded-xl text-muted hover:text-heading hover:bg-surface transition"
                    title="Đóng chat"
                    aria-label="Đóng chat"
                >
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- History Drawer (Slide in overlay) -->
        <div
            x-show="showDrawer"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-x-full"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-full"
            class="absolute inset-0 z-30 flex flex-col bg-surface/98 backdrop-blur-md border-r border-ui-border"
        >
            <div class="flex items-center justify-between p-4 border-b border-ui-border bg-surface-alt">
                <span class="font-semibold text-xs uppercase tracking-wider text-heading">Lịch sử tư vấn</span>
                <button type="button" @click="showDrawer = false" class="p-1 rounded-lg text-muted hover:text-heading">✕</button>
            </div>
            <div class="flex-1 overflow-y-auto p-3 space-y-2">
                <template x-if="conversationsList.length === 0">
                    <p class="text-xs text-muted text-center py-6">Chưa có lịch sử trò chuyện nào.</p>
                </template>
                <template x-for="item in conversationsList" :key="item.uuid">
                    <div
                        class="flex items-center justify-between gap-2 p-2.5 rounded-xl border border-ui-border hover:bg-surface-alt transition cursor-pointer"
                        :class="{ 'bg-primary/5 border-primary/40': currentConversationUuid === item.uuid }"
                        @click="selectConversation(item.uuid)"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-heading truncate" x-text="item.title"></p>
                            <p class="text-[10px] text-muted mt-0.5" x-text="item.last_message_at || 'Vừa xong'"></p>
                        </div>
                        <button
                            type="button"
                            @click.stop="deleteConversation(item.uuid)"
                            class="text-muted hover:text-rose-600 p-1 rounded transition"
                            title="Xóa cuộc trò chuyện này"
                        >
                            <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
            <div class="p-3 border-t border-ui-border bg-surface-alt">
                <button
                    type="button"
                    @click="startNewConversation()"
                    class="w-full py-2 px-3 rounded-xl bg-primary text-primary-foreground font-semibold text-xs flex items-center justify-center gap-2 shadow hover:opacity-95 transition"
                >
                    <span>+ Bắt đầu cuộc trò chuyện mới</span>
                </button>
            </div>
        </div>

        <!-- Chat Messages Container -->
        <div
            id="ai-messages-scroll"
            class="flex-1 overflow-y-auto p-4 space-y-4 bg-page/40"
        >
            <!-- Welcome Screen (When no messages) -->
            <template x-if="messages.length === 0">
                <div class="py-4 text-center space-y-4">
                    <div class="inline-grid size-14 place-items-center rounded-3xl bg-primary/10 text-primary mb-1">
                        <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-display font-semibold text-heading text-base">Chào bạn, Em là Trợ lý Mộc An!</h4>
                        <p class="text-xs text-muted max-w-xs mx-auto mt-1 leading-relaxed">
                            Em có thể hỗ trợ bạn tìm kiếm nội thất theo ngân sách, kiểm tra tồn kho, so sánh kích thước hoặc tra cứu đơn hàng nhanh chóng.
                        </p>
                    </div>

                    <!-- Quick Suggestions Prompt Chips -->
                    <div class="pt-2 text-left">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2 text-center">Gợi ý câu hỏi nhanh</p>
                        <div class="grid grid-cols-1 gap-2">
                            <button
                                type="button"
                                @click="sendQuickPrompt('Tư vấn sofa gỗ sồi cho phòng khách nhỏ')"
                                class="text-left p-2.5 rounded-xl border border-ui-border bg-surface hover:bg-surface-alt hover:border-primary/40 transition text-xs text-heading flex items-center gap-2 group"
                            >
                                <span class="text-base group-hover:scale-110 transition">🛋️</span>
                                <span class="flex-1">Tư vấn sofa gỗ sồi cho phòng khách nhỏ</span>
                                <span class="text-muted group-hover:text-primary transition">→</span>
                            </button>
                            <button
                                type="button"
                                @click="sendQuickPrompt('Gợi ý bàn ăn gỗ tự nhiên giá dưới 15 triệu')"
                                class="text-left p-2.5 rounded-xl border border-ui-border bg-surface hover:bg-surface-alt hover:border-primary/40 transition text-xs text-heading flex items-center gap-2 group"
                            >
                                <span class="text-base group-hover:scale-110 transition">💰</span>
                                <span class="flex-1">Gợi ý bàn ăn gỗ tự nhiên giá dưới 15 triệu</span>
                                <span class="text-muted group-hover:text-primary transition">→</span>
                            </button>
                            <button
                                type="button"
                                @click="sendQuickPrompt('Hiện có voucher hoặc mã khuyến mãi nào đang áp dụng?')"
                                class="text-left p-2.5 rounded-xl border border-ui-border bg-surface hover:bg-surface-alt hover:border-primary/40 transition text-xs text-heading flex items-center gap-2 group"
                            >
                                <span class="text-base group-hover:scale-110 transition">🎟️</span>
                                <span class="flex-1">Hiện có voucher khuyến mãi nào đang áp dụng?</span>
                                <span class="text-muted group-hover:text-primary transition">→</span>
                            </button>
                            <button
                                type="button"
                                @click="sendQuickPrompt('Chính sách bảo hành và đổi trả nội thất Mộc An ra sao?')"
                                class="text-left p-2.5 rounded-xl border border-ui-border bg-surface hover:bg-surface-alt hover:border-primary/40 transition text-xs text-heading flex items-center gap-2 group"
                            >
                                <span class="text-base group-hover:scale-110 transition">🛡️</span>
                                <span class="flex-1">Chính sách bảo hành và đổi trả nội thất</span>
                                <span class="text-muted group-hover:text-primary transition">→</span>
                            </button>
                            <button
                                type="button"
                                @click="sendQuickPrompt('Kiểm tra tình trạng đơn hàng gần nhất của tôi')"
                                class="text-left p-2.5 rounded-xl border border-ui-border bg-surface hover:bg-surface-alt hover:border-primary/40 transition text-xs text-heading flex items-center gap-2 group"
                            >
                                <span class="text-base group-hover:scale-110 transition">📦</span>
                                <span class="flex-1">Kiểm tra đơn hàng gần nhất của tôi</span>
                                <span class="text-muted group-hover:text-primary transition">→</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Message List -->
            <template x-for="(msg, index) in messages" :key="msg.id || index">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div class="max-w-[90%] sm:max-w-[85%] space-y-2">
                        <!-- Role & Time label -->
                        <div class="flex items-center gap-1.5 text-[10px] text-muted px-1" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                            <span x-text="msg.role === 'user' ? 'Bạn' : 'Trợ lý Mộc An'"></span>
                            <span>•</span>
                            <span x-text="msg.created_at || 'Vừa xong'"></span>
                        </div>

                        <!-- Bubble Box -->
                        <div
                            class="rounded-2xl p-3.5 text-xs sm:text-sm leading-relaxed shadow-sm transition"
                            :class="msg.role === 'user'
                                ? 'bg-primary text-primary-foreground rounded-tr-none font-medium'
                                : 'bg-surface text-body border border-ui-border rounded-tl-none prose-sm'"
                        >
                            <!-- Text content with safe formatting -->
                            <div class="break-words space-y-1" x-html="renderMarkdown(msg.content)"></div>

                            <!-- Source citations if any -->
                            <template x-if="msg.sources && msg.sources.length > 0">
                                <div class="mt-3 pt-2.5 border-t border-ui-border/60 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Nguồn tham chiếu:</span>
                                    <template x-for="src in msg.sources" :key="src.title">
                                        <a
                                            :href="src.url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-surface-alt border border-ui-border text-primary hover:underline"
                                            :title="src.title"
                                        >
                                            <span x-text="src.type === 'faq' ? '❓' : (src.type === 'post' ? '📰' : '📄')"></span>
                                            <span class="max-w-[120px] truncate" x-text="src.title"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Structured Cards Rendering (Underneath Assistant Bubble) -->
                        <template x-if="msg.cards && msg.cards.length > 0">
                            <div class="space-y-2 pt-1">
                                <template x-for="(card, cIndex) in msg.cards" :key="cIndex">
                                    <div>
                                        <!-- PRODUCT LIST / DETAIL CARD -->
                                        <template x-if="card.type === 'product_list' || card.type === 'product_detail'">
                                            <div class="space-y-2">
                                                <template x-for="p in (card.type === 'product_detail' ? [card.product] : card.products)" :key="p.id">
                                                    <div class="p-3 rounded-2xl bg-surface border border-ui-border shadow-sm flex items-start gap-3 hover:border-primary/40 transition">
                                                        <img
                                                            :src="p.primary_image || '/images/placeholder.jpg'"
                                                            :alt="p.name"
                                                            class="size-16 rounded-xl object-cover border border-ui-border shrink-0 bg-surface-alt"
                                                            loading="lazy"
                                                            onerror="this.src='/images/placeholder.jpg'"
                                                        />
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-start justify-between gap-1">
                                                                <h5 class="text-xs font-semibold text-heading truncate" x-text="p.name"></h5>
                                                                <span
                                                                    class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider shrink-0"
                                                                    :class="{
                                                                        'bg-emerald-100 text-emerald-800': p.stock_status === 'in_stock',
                                                                        'bg-amber-100 text-amber-800': p.stock_status === 'low_stock',
                                                                        'bg-rose-100 text-rose-800': p.stock_status === 'out_of_stock'
                                                                    }"
                                                                    x-text="p.stock_status === 'in_stock' ? 'Còn hàng' : (p.stock_status === 'low_stock' ? 'Sắp hết' : 'Hết hàng')"
                                                                ></span>
                                                            </div>
                                                            <p class="text-[11px] text-muted truncate mt-0.5" x-text="p.category_name"></p>
                                                            <p class="text-xs font-bold text-primary mt-1" x-text="p.formatted_price"></p>

                                                            <!-- Card Actions -->
                                                            <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                                                <a
                                                                    :href="p.url"
                                                                    class="text-[11px] font-semibold px-2.5 py-1 rounded-lg border border-ui-border bg-surface-alt hover:bg-surface text-heading transition"
                                                                >
                                                                    Xem chi tiết
                                                                </a>
                                                                <template x-if="p.first_variant_id && p.stock_status !== 'out_of_stock'">
                                                                    <button
                                                                        type="button"
                                                                        @click="quickAddToCart(p.first_variant_id)"
                                                                        class="text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-primary text-primary-foreground hover:opacity-90 shadow-sm transition"
                                                                    >
                                                                        Thêm vào giỏ
                                                                    </button>
                                                                </template>
                                                                <button
                                                                    type="button"
                                                                    @click="toggleCompare(p.id)"
                                                                    class="text-[11px] font-medium px-2 py-1 rounded-lg border border-ui-border text-muted hover:text-heading transition"
                                                                    title="So sánh sản phẩm"
                                                                >
                                                                    So sánh
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- ORDER STATUS CARD -->
                                        <template x-if="card.type === 'order_status'">
                                            <div class="p-3.5 rounded-2xl bg-surface border border-ui-border shadow-sm space-y-2.5">
                                                <div class="flex items-center justify-between border-b border-ui-border pb-2">
                                                    <div>
                                                        <span class="text-[10px] uppercase font-bold text-muted">Mã đơn:</span>
                                                        <span class="text-xs font-bold text-heading ml-1" x-text="card.order.order_code"></span>
                                                    </div>
                                                    <span
                                                        class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                                                        :class="{
                                                            'bg-emerald-100 text-emerald-800': card.order.order_status === 'completed',
                                                            'bg-sky-100 text-sky-800': card.order.order_status === 'shipping',
                                                            'bg-amber-100 text-amber-800': card.order.order_status === 'pending' || card.order.order_status === 'confirmed',
                                                            'bg-rose-100 text-rose-800': card.order.order_status === 'canceled'
                                                        }"
                                                        x-text="card.order.status_label"
                                                    ></span>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2 text-xs">
                                                    <div>
                                                        <p class="text-[10px] text-muted">Tổng tiền:</p>
                                                        <p class="font-bold text-primary" x-text="card.order.formatted_total"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] text-muted">Thanh toán:</p>
                                                        <p class="font-semibold text-heading" x-text="card.order.payment_label"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] text-muted">Số sản phẩm:</p>
                                                        <p class="font-semibold text-heading" x-text="card.order.items_count + ' món'"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] text-muted">Ngày đặt:</p>
                                                        <p class="font-semibold text-heading" x-text="card.order.created_at"></p>
                                                    </div>
                                                </div>
                                                <div class="pt-1 flex items-center justify-end">
                                                    <a href="/tai-khoan" class="text-xs font-semibold text-primary hover:underline">Xem danh sách đơn hàng →</a>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- VOUCHER CARDS -->
                                        <template x-if="card.type === 'vouchers'">
                                            <div class="space-y-2">
                                                <template x-for="v in card.vouchers" :key="v.code">
                                                    <div class="p-3 rounded-2xl bg-amber-50/70 border border-amber-200 shadow-sm flex items-center justify-between gap-2">
                                                        <div>
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="font-mono font-bold text-xs bg-amber-200 text-amber-900 px-2 py-0.5 rounded tracking-wider" x-text="v.code"></span>
                                                                <span class="text-xs font-bold text-heading" x-text="v.discount_label"></span>
                                                            </div>
                                                            <p class="text-[10px] text-muted mt-1" x-text="'Đơn từ ' + v.formatted_min_order + ' • HSD: ' + (v.expires_at || 'Không thời hạn')"></p>
                                                        </div>
                                                        <button
                                                            type="button"
                                                            @click="copyVoucher(v.code)"
                                                            class="text-xs font-bold px-3 py-1.5 rounded-xl bg-primary text-primary-foreground hover:opacity-90 shadow-sm transition"
                                                        >
                                                            Sao chép
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- PRODUCT COMPARISON TABLE -->
                                        <template x-if="card.type === 'product_comparison'">
                                            <div class="p-3 rounded-2xl bg-surface border border-ui-border shadow-sm overflow-x-auto">
                                                <p class="text-xs font-bold text-heading mb-2">Bảng so sánh thông số:</p>
                                                <table class="w-full text-left text-[11px] border-collapse">
                                                    <thead>
                                                        <tr class="border-b border-ui-border">
                                                            <th class="py-1 px-1.5 text-muted font-normal">Đặc điểm</th>
                                                            <template x-for="cp in card.products" :key="cp.id">
                                                                <th class="py-1 px-1.5 font-semibold text-heading max-w-[100px] truncate" x-text="cp.name"></th>
                                                            </template>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-ui-border/50">
                                                        <tr>
                                                            <td class="py-1 px-1.5 text-muted">Giá bán</td>
                                                            <template x-for="cp in card.products" :key="cp.id">
                                                                <td class="py-1 px-1.5 font-bold text-primary" x-text="cp.formatted_price"></td>
                                                            </template>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-1 px-1.5 text-muted">Chất liệu</td>
                                                            <template x-for="cp in card.products" :key="cp.id">
                                                                <td class="py-1 px-1.5" x-text="cp.material || 'Gỗ tự nhiên'"></td>
                                                            </template>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-1 px-1.5 text-muted">Kích thước</td>
                                                            <template x-for="cp in card.products" :key="cp.id">
                                                                <td class="py-1 px-1.5" x-text="cp.dimensions || 'Theo tiêu chuẩn'"></td>
                                                            </template>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-1 px-1.5 text-muted">Bảo hành</td>
                                                            <template x-for="cp in card.products" :key="cp.id">
                                                                <td class="py-1 px-1.5" x-text="cp.warranty_months ? (cp.warranty_months + ' tháng') : '12 tháng'"></td>
                                                            </template>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>

                                        <!-- TICKET CREATED CONFIRMATION -->
                                        <template x-if="card.type === 'ticket_created'">
                                            <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-sm space-y-1">
                                                <div class="flex items-center gap-1.5 font-semibold text-xs">
                                                    <span>✓</span>
                                                    <span>Đã tạo yêu cầu hỗ trợ thành công</span>
                                                </div>
                                                <p class="text-[11px] text-emerald-800">
                                                    Mã phiếu: <strong class="font-mono" x-text="card.ticket.ticket_code"></strong>. Chuyên viên Mộc An sẽ liên hệ hỗ trợ bạn sớm nhất qua email hoặc điện thoại.
                                                </p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Typing / Thinking Indicator -->
            <div x-show="isLoading" class="flex justify-start">
                <div class="rounded-2xl rounded-tl-none p-3 bg-surface border border-ui-border shadow-sm flex items-center gap-2">
                    <span class="flex gap-1">
                        <span class="size-2 rounded-full bg-primary/60 animate-bounce" style="animation-delay: 0ms"></span>
                        <span class="size-2 rounded-full bg-primary/60 animate-bounce" style="animation-delay: 150ms"></span>
                        <span class="size-2 rounded-full bg-primary/60 animate-bounce" style="animation-delay: 300ms"></span>
                    </span>
                    <span class="text-xs text-muted">Trợ lý Mộc An đang tra cứu dữ liệu...</span>
                </div>
            </div>
        </div>

        <!-- Chat Input Form -->
        <div class="shrink-0 p-3 bg-surface border-t border-ui-border">
            <form @submit.prevent="sendMessage()" class="flex items-end gap-2">
                <div class="relative flex-1">
                    <textarea
                        x-ref="messageInput"
                        x-model="inputPrompt"
                        @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                        rows="1"
                        placeholder="Hỏi về sản phẩm, giá tiền, đơn hàng, bảo hành..."
                        class="w-full resize-none rounded-2xl border border-ui-border bg-page px-3.5 py-2.5 text-xs sm:text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary max-h-24 overflow-y-auto"
                        :disabled="isLoading"
                    ></textarea>
                </div>
                <button
                    type="submit"
                    :disabled="isLoading || !inputPrompt.trim()"
                    class="grid size-10 shrink-0 place-items-center rounded-2xl bg-primary text-primary-foreground shadow-md transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
                    aria-label="Gửi tin nhắn"
                >
                    <svg viewBox="0 0 24 24" class="size-4 rotate-90" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </form>
            <div class="mt-1.5 flex items-center justify-between text-[10px] text-muted px-1">
                <span>Dữ liệu giá & tồn kho đồng bộ thời gian thực từ Mộc An.</span>
                <span class="hidden sm:inline">Nhấn Enter để gửi</span>
            </div>
        </div>
    </div>
</div>

<script>
function aiAssistantWidget() {
    return {
        isOpen: false,
        showTooltip: true,
        showDrawer: false,
        isLoading: false,
        inputPrompt: '',
        currentConversationUuid: null,
        currentConversationTitle: '',
        conversationsList: [],
        messages: [],

        init() {
            // Check if there is a saved conversation in localStorage
            try {
                const savedUuid = localStorage.getItem('moc_an_ai_conv_uuid');
                if (savedUuid) {
                    this.currentConversationUuid = savedUuid;
                    this.loadConversation(savedUuid);
                }
            } catch (e) {}

            // Auto dismiss tooltip after 10s
            setTimeout(() => {
                this.showTooltip = false;
            }, 10000);
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.showTooltip = false;
                this.scrollToBottom();
                this.$nextTick(() => {
                    this.$refs.messageInput && this.$refs.messageInput.focus();
                });
            }
        },

        async loadConversations() {
            try {
                const res = await fetch('/api/ai/conversations', {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.conversationsList = data.conversations || [];
                }
            } catch (e) {}
        },

        async loadConversation(uuid) {
            try {
                const res = await fetch(`/api/ai/conversations/${uuid}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.success) {
                        this.currentConversationUuid = data.conversation.uuid;
                        this.currentConversationTitle = data.conversation.title;
                        this.messages = data.conversation.messages || [];
                        this.scrollToBottom();
                    }
                }
            } catch (e) {}
        },

        async selectConversation(uuid) {
            this.showDrawer = false;
            await this.loadConversation(uuid);
            try {
                localStorage.setItem('moc_an_ai_conv_uuid', uuid);
            } catch(e) {}
        },

        async startNewConversation() {
            this.showDrawer = false;
            this.isLoading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/api/ai/conversations', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.currentConversationUuid = data.conversation.uuid;
                    this.currentConversationTitle = data.conversation.title;
                    this.messages = [];
                    try {
                        localStorage.setItem('moc_an_ai_conv_uuid', data.conversation.uuid);
                    } catch(e) {}
                }
            } catch(e) {}
            finally {
                this.isLoading = false;
                this.$nextTick(() => this.$refs.messageInput?.focus());
            }
        },

        async clearCurrentConversation() {
            if (!this.currentConversationUuid) {
                this.messages = [];
                return;
            }
            if (!confirm('Bạn có chắc chắn muốn xóa tin nhắn trong cuộc trò chuyện này?')) return;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/ai/conversations/${this.currentConversationUuid}/clear`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    }
                });
                if (res.ok) {
                    this.messages = [];
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Đã dọn dẹp lịch sử trò chuyện!', type: 'info' }
                    }));
                }
            } catch(e) {}
        },

        async deleteConversation(uuid) {
            if (!confirm('Xóa cuộc trò chuyện này?')) return;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/ai/conversations/${uuid}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    }
                });
                if (res.ok) {
                    this.conversationsList = this.conversationsList.filter(c => c.uuid !== uuid);
                    if (this.currentConversationUuid === uuid) {
                        this.currentConversationUuid = null;
                        this.messages = [];
                        try { localStorage.removeItem('moc_an_ai_conv_uuid'); } catch(e) {}
                    }
                }
            } catch(e) {}
        },

        sendQuickPrompt(text) {
            this.inputPrompt = text;
            this.sendMessage();
        },

        async sendMessage() {
            const trimmed = this.inputPrompt.trim();
            if (!trimmed || this.isLoading) return;

            // Optimistic user message append
            const tempUserMsg = {
                id: 'temp_' + Date.now(),
                role: 'user',
                content: trimmed,
                created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            };
            this.messages.push(tempUserMsg);
            this.inputPrompt = '';
            this.isLoading = true;
            this.scrollToBottom();

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/api/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    },
                    body: JSON.stringify({
                        message: trimmed,
                        conversation_uuid: this.currentConversationUuid
                    })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.currentConversationUuid = data.conversation_uuid;
                    this.currentConversationTitle = data.conversation_title;
                    try {
                        localStorage.setItem('moc_an_ai_conv_uuid', data.conversation_uuid);
                    } catch(e) {}

                    this.messages.push(data.message);
                } else {
                    this.messages.push({
                        id: 'err_' + Date.now(),
                        role: 'assistant',
                        content: data.error || 'Rất tiếc, đã có lỗi xảy ra trong quá trình xử lý. Quý khách vui lòng thử lại sau giây lát!',
                        created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    });
                }
            } catch (err) {
                this.messages.push({
                    id: 'err_' + Date.now(),
                    role: 'assistant',
                    content: 'Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng và thử lại.',
                    created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                });
            } finally {
                this.isLoading = false;
                this.scrollToBottom();
                this.$nextTick(() => this.$refs.messageInput?.focus());
            }
        },

        async quickAddToCart(variantId) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/api/cart/quick-add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    },
                    body: JSON.stringify({
                        variant_id: variantId,
                        quantity: 1
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message || 'Đã thêm vào giỏ hàng!', type: 'success' }
                    }));
                    document.querySelectorAll('.cart-count-badge').forEach(b => b.textContent = data.cart_count);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message || 'Không thể thêm sản phẩm.', type: 'error' }
                    }));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Lỗi mạng khi thêm giỏ hàng.', type: 'error' }
                }));
            }
        },

        toggleCompare(productId) {
            if (window.MocAnCompare) {
                window.MocAnCompare.toggle(productId);
            }
        },

        copyVoucher(code) {
            navigator.clipboard.writeText(code).then(() => {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: `Đã sao chép mã ${code}!`, type: 'success' }
                }));
            });
        },

        renderMarkdown(text) {
            if (!text) return '';
            let escaped = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Bold
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Italic
            escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
            // Bullet points
            escaped = escaped.replace(/^[\*\-]\s+(.*)$/gm, '<li class="ml-4 list-disc">$1</li>');
            // Numbered lists
            escaped = escaped.replace(/^\d+\.\s+(.*)$/gm, '<li class="ml-4 list-decimal">$1</li>');
            // Markdown links: [title](url)
            escaped = escaped.replace(/\[(.*?)\]\((https?:\/\/[^\s]+|\/[^\s]+)\)/g, '<a href="$2" target="_blank" class="text-primary underline font-medium hover:opacity-80">$1</a>');
            // Line breaks
            escaped = escaped.replace(/\n/g, '<br>');

            return escaped;
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('ai-messages-scroll');
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        }
    };
}
</script>
