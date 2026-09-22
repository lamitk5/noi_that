@extends('layouts.admin')

@section('title', 'Quản trị Trợ lý AI & RAG | Mộc An')
@section('header-title', 'Trung tâm Điều khiển Trợ lý AI & RAG')

@section('content')
<div class="max-w-6xl mx-auto space-y-8" x-data="{ activeTab: '{{ $activeTab }}' }">
    <!-- Success Banner -->
    @if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span>✓</span>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-xs opacity-70 hover:opacity-100">✕</button>
    </div>
    @endif

    <!-- Header Banner -->
    <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Mộc An AI Engine</span>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $settings['ai_enabled'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                    {{ $settings['ai_enabled'] ? 'Đang hoạt động' : 'Tạm tắt' }}
                </span>
            </div>
            <h1 class="mt-2 font-display text-2xl sm:text-3xl font-semibold text-heading">
                Trợ lý Tư vấn Mua sắm & RAG
            </h1>
            <p class="mt-2 text-sm text-muted leading-relaxed">
                Hệ thống AI LLM kết hợp RAG đa nguồn (FAQ, CMS, Blog) và bộ 10 ecommerce tools thời gian thực: tra cứu sản phẩm, kiểm kho, so sánh, voucher, và kiểm tra đơn hàng khách hàng an toàn.
            </p>
        </div>
    </div>

    <!-- Live KPI Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Tổng hội thoại</div>
            <div class="text-xl font-bold font-display text-heading mt-1">
                {{ number_format($metrics['total_conversations']) }}
            </div>
            <div class="text-[10px] text-emerald-600 mt-1">+{{ $metrics['active_today'] }} hôm nay</div>
        </div>

        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Tổng tin nhắn</div>
            <div class="text-xl font-bold font-display text-heading mt-1">
                {{ number_format($metrics['total_messages']) }}
            </div>
            <div class="text-[10px] text-muted mt-1">{{ $metrics['messages_today'] }} hôm nay</div>
        </div>

        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Tokens sử dụng</div>
            <div class="text-xl font-bold font-display text-primary mt-1">
                {{ number_format($metrics['total_tokens']) }}
            </div>
            <div class="text-[10px] text-muted mt-1">Ước tính tích lũy</div>
        </div>

        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Lượt gọi Tools</div>
            <div class="text-xl font-bold font-display text-heading mt-1">
                {{ number_format($metrics['tool_events']) }}
            </div>
            <div class="text-[10px] text-emerald-600 mt-1">Tương tác thực</div>
        </div>

        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Nguồn tri thức RAG</div>
            <div class="text-xl font-bold font-display text-heading mt-1">
                {{ $knowledgeCounts['faqs'] + $knowledgeCounts['pages'] + $knowledgeCounts['posts'] }}
            </div>
            <div class="text-[10px] text-muted mt-1">FAQ/Trang/Bài viết</div>
        </div>

        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Model & Cấu hình</div>
            <div class="text-xs font-bold font-mono text-heading mt-1 truncate" title="{{ $settings['model'] }}">
                {{ $settings['model'] }}
            </div>
            <div class="text-[10px] text-muted mt-1">{{ $settings['provider'] }}</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-ui-border pb-1 overflow-x-auto">
        <button
            type="button"
            @click="activeTab = 'overview'"
            class="px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition whitespace-nowrap"
            :class="activeTab === 'overview' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading hover:bg-surface-alt'"
        >
            Tổng quan & Công cụ
        </button>
        <button
            type="button"
            @click="activeTab = 'conversations'"
            class="px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition whitespace-nowrap"
            :class="activeTab === 'conversations' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading hover:bg-surface-alt'"
        >
            Lịch sử trò chuyện ({{ $metrics['total_conversations'] }})
        </button>
        <button
            type="button"
            @click="activeTab = 'knowledge'"
            class="px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition whitespace-nowrap"
            :class="activeTab === 'knowledge' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading hover:bg-surface-alt'"
        >
            Kiểm thử RAG Tri thức
        </button>
        <button
            type="button"
            @click="activeTab = 'settings'"
            class="px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition whitespace-nowrap"
            :class="activeTab === 'settings' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading hover:bg-surface-alt'"
        >
            Cài đặt Hệ thống
        </button>
    </div>

    <!-- TAB 1: OVERVIEW & TOOLS STATUS -->
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Ecommerce Tools Registered -->
            <div class="p-6 rounded-3xl bg-surface border border-ui-border space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-display font-semibold text-heading text-base">Bộ Ecommerce Tools Đang Chạy</h3>
                    <span class="text-xs bg-primary/10 text-primary px-2.5 py-0.5 rounded-full font-bold">10 Tools Active</span>
                </div>
                <div class="divide-y divide-ui-border/60 text-xs">
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-heading">search_products</span>
                            <p class="text-muted text-[11px]">Tìm kiếm sản phẩm theo từ khóa, danh mục, ngân sách min/max</p>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Active</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-heading">get_product_detail</span>
                            <p class="text-muted text-[11px]">Xem chi tiết thuộc tính, kích thước, chất liệu, ảnh, biến thể</p>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Active</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-heading">check_inventory</span>
                            <p class="text-muted text-[11px]">Kiểm tra tồn kho thời gian thực từ product_variants.stock</p>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Active</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-heading">compare_products</span>
                            <p class="text-muted text-[11px]">So sánh thông số 2-4 sản phẩm trực quan</p>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Active</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-heading">recommend_products</span>
                            <p class="text-muted text-[11px]">Gợi ý sản phẩm tương tự, mua kèm bán chạy</p>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Active</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-heading">get_active_vouchers</span>
                            <p class="text-muted text-[11px]">Lấy danh sách mã giảm giá còn hạn và hợp lệ</p>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Active</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-heading">get_order_status</span>
                            <p class="text-muted text-[11px]">Tra cứu đơn hàng cá nhân (bảo vệ quyền sở hữu user_id)</p>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Active (Strict Auth)</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-heading">add_to_cart</span>
                            <p class="text-muted text-[11px]">Thêm vào giỏ hàng thông qua CartService</p>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Active</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-heading">create_support_ticket</span>
                            <p class="text-muted text-[11px]">Tạo ticket khiếu nại hoặc tư vấn chuyên sâu</p>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Active</span>
                    </div>
                </div>
            </div>

            <!-- Knowledge Sources & Safety -->
            <div class="space-y-6">
                <div class="p-6 rounded-3xl bg-surface border border-ui-border space-y-4">
                    <h3 class="font-display font-semibold text-heading text-base">Cơ chế RAG & Dữ liệu Tri thức</h3>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-3 rounded-2xl bg-surface-alt border border-ui-border">
                            <span class="text-xl font-bold font-display text-primary">{{ $knowledgeCounts['faqs'] }}</span>
                            <p class="text-[11px] text-muted mt-0.5">Câu hỏi FAQ</p>
                        </div>
                        <div class="p-3 rounded-2xl bg-surface-alt border border-ui-border">
                            <span class="text-xl font-bold font-display text-primary">{{ $knowledgeCounts['pages'] }}</span>
                            <p class="text-[11px] text-muted mt-0.5">Trang CMS tĩnh</p>
                        </div>
                        <div class="p-3 rounded-2xl bg-surface-alt border border-ui-border">
                            <span class="text-xl font-bold font-display text-primary">{{ $knowledgeCounts['posts'] }}</span>
                            <p class="text-[11px] text-muted mt-0.5">Bài viết tin tức</p>
                        </div>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        Dữ liệu kiến thức được chấm điểm liên quan (term frequency & keyword overlap) theo thời gian thực và tự động đưa vào prompt kèm trích dẫn nguồn (source citations).
                    </p>
                </div>

                <div class="p-6 rounded-3xl bg-surface border border-ui-border space-y-3">
                    <h3 class="font-display font-semibold text-heading text-base">Bảo mật & Cam kết Zero Hallucination</h3>
                    <ul class="text-xs text-muted space-y-2">
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold">✓</span>
                            <span><strong>Chống tự chế:</strong> AI không tự bịa giá, tồn kho hay chính sách. Mọi dữ liệu phải qua Tools và RAG.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold">✓</span>
                            <span><strong>Kiểm tra sở hữu:</strong> Đơn hàng chỉ được tra cứu nếu người dùng đã đăng nhập và là chủ sở hữu đơn.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold">✓</span>
                            <span><strong>Phòng vệ Jailbreak:</strong> Khối kiến thức được bọc an toàn, ngăn chặn ghi đè system instruction.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold">✓</span>
                            <span><strong>Rate limiting:</strong> Giới hạn 30 yêu cầu/phút trên mỗi người dùng/session.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: CONVERSATIONS VIEWER -->
    <div x-show="activeTab === 'conversations'" class="space-y-4">
        <!-- Search filter -->
        <form method="GET" action="{{ route('admin.ai.index') }}" class="flex items-center gap-3">
            <input type="hidden" name="tab" value="conversations">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Tìm theo tiêu đề, UUID, tên khách hoặc email..."
                class="flex-1 rounded-2xl border border-ui-border bg-surface px-4 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
            >
            <button
                type="submit"
                class="px-4 py-2.5 rounded-2xl bg-primary text-primary-foreground font-semibold text-xs hover:opacity-90 transition"
            >
                Tìm kiếm
            </button>
            @if(request('q'))
            <a href="{{ route('admin.ai.index', ['tab' => 'conversations']) }}" class="text-xs text-muted hover:text-heading">Xóa tìm</a>
            @endif
        </form>

        <!-- Table list -->
        <div class="rounded-3xl border border-ui-border bg-surface overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-surface-alt border-b border-ui-border">
                        <tr>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider text-muted text-[10px]">Tiêu đề & UUID</th>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider text-muted text-[10px]">Người dùng</th>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider text-muted text-[10px]">Số tin</th>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider text-muted text-[10px]">Thời gian</th>
                            <th class="py-3 px-4 font-bold uppercase tracking-wider text-muted text-[10px] text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ui-border/60">
                        @forelse($conversations as $conv)
                        <tr class="hover:bg-surface-alt/50 transition">
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.ai.conversation', $conv->uuid) }}" class="font-semibold text-heading hover:text-primary">
                                    {{ $conv->title }}
                                </a>
                                <p class="font-mono text-[10px] text-muted truncate max-w-xs mt-0.5">{{ $conv->uuid }}</p>
                            </td>
                            <td class="py-3 px-4">
                                @if($conv->user)
                                <div class="font-semibold text-heading">{{ $conv->user->name }}</div>
                                <div class="text-[10px] text-muted">{{ $conv->user->email }}</div>
                                @else
                                <span class="px-2 py-0.5 rounded bg-surface-alt text-muted text-[10px] font-mono">Khách vãng lai</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-heading">{{ $conv->messages_count }}</span>
                            </td>
                            <td class="py-3 px-4 text-muted text-[11px]">
                                {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : $conv->created_at->diffForHumans() }}
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <a
                                    href="{{ route('admin.ai.conversation', $conv->uuid) }}"
                                    class="inline-block px-3 py-1.5 rounded-xl border border-ui-border bg-surface-alt hover:bg-surface text-heading font-semibold text-[11px] transition"
                                >
                                    Xem hội thoại
                                </a>
                                <form
                                    action="{{ route('admin.ai.destroy-conversation', $conv->uuid) }}"
                                    method="POST"
                                    class="inline-block"
                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa cuộc trò chuyện này?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="px-2 py-1.5 rounded-xl text-muted hover:text-rose-600 transition"
                                        title="Xóa"
                                    >
                                        ✕
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-muted">
                                Không tìm thấy cuộc trò chuyện nào.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($conversations->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $conversations->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- TAB 3: KNOWLEDGE RETRIEVAL TESTER -->
    <div x-show="activeTab === 'knowledge'" class="space-y-6">
        <div class="p-6 rounded-3xl bg-surface border border-ui-border space-y-4">
            <h3 class="font-display font-semibold text-heading text-base">Kiểm Thử Truy Xuất Tri Thức (RAG Explorer)</h3>
            <p class="text-xs text-muted leading-relaxed">
                Nhập câu hỏi để kiểm tra thuật toán RAG chấm điểm và trích xuất các mẩu thông tin (chunks) tương ứng từ FAQ, CMS Pages và Tin tức.
            </p>

            <form method="POST" action="{{ route('admin.ai.test-rag') }}" class="flex items-center gap-3">
                @csrf
                <input
                    type="text"
                    name="query"
                    placeholder="Ví dụ: Chính sách bảo hành gỗ sồi, phí vận chuyển Hà Nội, đổi trả trong bao lâu..."
                    class="flex-1 rounded-2xl border border-ui-border bg-page px-4 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                    required
                >
                <button
                    type="submit"
                    class="px-5 py-2.5 rounded-2xl bg-primary text-primary-foreground font-semibold text-xs hover:opacity-90 shadow-sm transition"
                >
                    Kiểm tra RAG
                </button>
            </form>
        </div>
    </div>

    <!-- TAB 4: SETTINGS -->
    <div x-show="activeTab === 'settings'" class="space-y-6">
        <form method="POST" action="{{ route('admin.ai.update-settings') }}" class="p-6 sm:p-8 rounded-3xl bg-surface border border-ui-border space-y-6">
            @csrf

            <h3 class="font-display font-semibold text-heading text-lg">Cấu Hình Trợ Lý AI Mộc An</h3>

            <!-- AI Enabled Toggle -->
            <div class="flex items-center justify-between p-4 rounded-2xl bg-surface-alt border border-ui-border">
                <div>
                    <h4 class="text-xs font-bold text-heading uppercase tracking-wider">Kích hoạt Trợ lý AI trên Storefront</h4>
                    <p class="text-[11px] text-muted mt-0.5">Khi tắt, widget chat trên web người dùng sẽ thông báo bảo trì tạm thời.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="ai_enabled" value="1" class="sr-only peer" {{ $settings['ai_enabled'] ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-ui-border peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>

            <!-- Provider & Key Information (Read-only security) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-2xl bg-surface-alt border border-ui-border space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">AI Provider & Model</span>
                    <p class="text-xs font-bold font-mono text-heading">{{ $settings['provider'] }} ({{ $settings['model'] }})</p>
                    <p class="text-[10px] text-muted">Cấu hình qua file .env (GEMINI_API_KEY & AI_MODEL)</p>
                </div>
                <div class="p-4 rounded-2xl bg-surface-alt border border-ui-border space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">API Key Status</span>
                    <p class="text-xs font-mono text-heading">{{ $settings['api_key_masked'] }}</p>
                    <p class="text-[10px] text-muted">Bảo mật: Không hiển thị công khai trên giao diện</p>
                </div>
            </div>

            <!-- Parameters: Temperature & Max tokens -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-heading mb-1">Độ sáng tạo (Temperature: 0.0 - 1.0)</label>
                    <input
                        type="number"
                        step="0.1"
                        min="0"
                        max="1"
                        name="ai_temperature"
                        value="{{ $settings['temperature'] }}"
                        class="w-full rounded-xl border border-ui-border bg-page px-3 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                    >
                    <span class="text-[10px] text-muted">Khuyến nghị 0.5 - 0.7 để câu trả lời chính xác, mạch lạc.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-heading mb-1">Giới hạn Tokens phản hồi (Max Tokens)</label>
                    <input
                        type="number"
                        step="100"
                        min="200"
                        max="4000"
                        name="ai_max_tokens"
                        value="{{ $settings['max_tokens'] }}"
                        class="w-full rounded-xl border border-ui-border bg-page px-3 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                    >
                    <span class="text-[10px] text-muted">Mặc định 1500 tokens (đủ cho câu trả lời chi tiết và cards).</span>
                </div>
            </div>

            <!-- Custom System Instructions -->
            <div>
                <label class="block text-xs font-semibold text-heading mb-1">Chỉ Dẫn Bổ Sung Cho Trợ Lý (Admin Instructions)</label>
                <textarea
                    name="ai_custom_instructions"
                    rows="4"
                    placeholder="Ví dụ: Đang có chiến dịch Tết giảm 10% khi mua kèm bàn ăn. Luôn ưu tiên giới thiệu các dòng sản phẩm gỗ sồi tự nhiên..."
                    class="w-full rounded-2xl border border-ui-border bg-page p-3.5 text-xs text-heading focus:border-primary focus:outline-none leading-relaxed"
                >{{ $settings['custom_instructions'] }}</textarea>
                <span class="text-[10px] text-muted">Nội dung này sẽ được tự động tiêm vào System Prompt của trợ lý AI trong mọi cuộc hội thoại.</span>
            </div>

            <div class="pt-2 flex justify-end">
                <button
                    type="submit"
                    class="px-6 py-2.5 rounded-2xl bg-primary text-primary-foreground font-semibold text-xs hover:opacity-90 shadow-sm transition"
                >
                    Lưu Thay Đổi Cài Đặt
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
