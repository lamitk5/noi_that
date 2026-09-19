<div
    x-data="{
        isOpen: false,
        activeTab: 'help',
        searchQuery: '',
        quickTickets: [
            'Tư vấn kích thước sofa',
            'Kiểm tra tình trạng đơn hàng',
            'Yêu cầu bảo hành / đổi trả',
            'Tư vấn lắp đặt tại nhà'
        ]
    }"
    class="fixed bottom-6 right-6 z-50 font-sans"
>
    <!-- Toggle Button -->
    <button
        @click="isOpen = !isOpen"
        type="button"
        class="flex items-center gap-2.5 px-4 py-3 rounded-full bg-primary text-primary-foreground shadow-xl hover:shadow-2xl hover:scale-105 active:scale-95 transition-all cursor-pointer focus:outline-none focus:ring-4 focus:ring-primary/20"
        :aria-expanded="isOpen.toString()"
        aria-label="Trung tâm hỗ trợ khách hàng Mộc An"
    >
        <div class="relative">
            <svg x-show="!isOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <svg x-show="isOpen" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
        </div>
        <span class="text-xs font-bold font-display tracking-wide hidden sm:inline">Hỗ trợ Mộc An</span>
    </button>

    <!-- Support Dialog Popup -->
    <div
        x-show="isOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        @click.outside="isOpen = false"
        class="absolute bottom-16 right-0 w-80 sm:w-96 rounded-3xl border border-ui-border bg-surface shadow-2xl overflow-hidden flex flex-col max-h-[520px]"
    >
        <!-- Header -->
        <div class="p-4 bg-primary text-primary-foreground flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold font-display">Trung tâm trợ giúp Mộc An</h3>
                <p class="text-[11px] text-primary-foreground/80 mt-0.5">Chúng tôi luôn sẵn sàng hỗ trợ bạn</p>
            </div>
            <button @click="isOpen = false" class="p-1 rounded-lg text-primary-foreground/80 hover:text-primary-foreground hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Quick Contacts Banner -->
        <div class="grid grid-cols-2 gap-2 p-3 bg-surface-alt border-b border-ui-border text-center">
            <a href="tel:19006868" class="flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-xl bg-surface border border-ui-border hover:border-primary/50 text-xs font-semibold text-heading hover:text-primary transition shadow-2xs">
                <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                1900 6868
            </a>
            <a href="https://zalo.me" target="_blank" rel="noopener" class="flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-xl bg-surface border border-ui-border hover:border-blue-500/50 text-xs font-semibold text-heading hover:text-blue-600 transition shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                Chat Zalo OA
            </a>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex border-b border-ui-border bg-surface text-xs font-bold text-muted">
            <button
                @click="activeTab = 'help'"
                class="flex-1 py-2.5 text-center border-b-2 transition"
                :class="activeTab === 'help' ? 'border-primary text-primary font-bold' : 'border-transparent hover:text-heading'"
            >
                Câu hỏi nhanh
            </button>
            <button
                @click="activeTab = 'ticket'"
                class="flex-1 py-2.5 text-center border-b-2 transition"
                :class="activeTab === 'ticket' ? 'border-primary text-primary font-bold' : 'border-transparent hover:text-heading'"
            >
                Gửi yêu cầu
            </button>
        </div>

        <!-- Tab Body -->
        <div class="p-4 flex-1 overflow-y-auto space-y-3 text-xs">
            <!-- Help Tab -->
            <div x-show="activeTab === 'help'" class="space-y-3">
                <div class="relative">
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Tìm câu trả lời nhanh..."
                        class="w-full rounded-xl border border-ui-border bg-surface-alt pl-8 pr-3 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                    >
                    <svg class="w-3.5 h-3.5 text-muted absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Chủ đề thường gặp</p>
                    <div class="space-y-1.5">
                        <a href="{{ route('faq.index') }}" class="block p-2 rounded-xl bg-surface-alt hover:bg-primary/5 hover:border-primary border border-ui-border transition text-heading hover:text-primary font-medium">
                            🚚 Chính sách giao hàng và lắp đặt nội thất
                        </a>
                        <a href="{{ route('faq.index') }}" class="block p-2 rounded-xl bg-surface-alt hover:bg-primary/5 hover:border-primary border border-ui-border transition text-heading hover:text-primary font-medium">
                            🛡️ Quy trình bảo hành 24 tháng cho gỗ tự nhiên
                        </a>
                        <a href="{{ route('faq.index') }}" class="block p-2 rounded-xl bg-surface-alt hover:bg-primary/5 hover:border-primary border border-ui-border transition text-heading hover:text-primary font-medium">
                            💳 Phương thức thanh toán VNPAY, MoMo & COD
                        </a>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="{{ route('faq.index') }}" class="block text-center text-xs font-bold text-primary hover:underline">
                        Xem tất cả câu hỏi thường gặp (FAQ) →
                    </a>
                </div>
            </div>

            <!-- Ticket Tab -->
            <div x-show="activeTab === 'ticket'" class="space-y-3">
                <p class="text-muted leading-relaxed">
                    Bạn cần hỗ trợ chuyên sâu hoặc giải quyết thắc mắc về đơn hàng? Tạo phiếu hỗ trợ để đội ngũ Mộc An phản hồi trong 2 giờ.
                </p>

                @auth
                    <a
                        href="{{ route('account.tickets.create') }}"
                        class="block w-full py-2.5 rounded-xl bg-primary text-primary-foreground text-center font-bold hover:opacity-95 transition shadow-xs"
                    >
                        + Tạo phiếu yêu cầu hỗ trợ mới
                    </a>
                    <a
                        href="{{ route('account.tickets.index') }}"
                        class="block w-full py-2 rounded-xl border border-ui-border text-center font-semibold text-heading hover:bg-surface-alt transition text-xs"
                    >
                        Xem các phiếu yêu cầu của tôi
                    </a>
                @else
                    <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 text-amber-800 dark:text-amber-300 space-y-2">
                        <p class="font-medium">Vui lòng đăng nhập để gửi ticket hỗ trợ được theo dõi tự động.</p>
                        <a href="{{ route('login') }}" class="inline-block px-3 py-1.5 rounded-lg bg-primary text-primary-foreground text-xs font-bold">
                            Đăng nhập ngay
                        </a>
                    </div>
                    <div class="pt-1">
                        <a href="{{ route('pages.contact') }}" class="block text-center text-xs font-semibold text-heading hover:text-primary">
                            Hoặc gửi thư qua Trang liên hệ trực tiếp →
                        </a>
                    </div>
                @endauth
            </div>
        </div>

        <!-- Footer -->
        <div class="p-2.5 bg-surface border-t border-ui-border text-center text-[10px] text-muted">
            Mộc An Furniture • Đồng hành cùng tổ ấm Việt
        </div>
    </div>
</div>
