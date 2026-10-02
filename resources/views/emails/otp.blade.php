<div style="font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 28px; border: 1px solid #e5e7eb; border-radius: 12px; background-color: #ffffff;">
    <div style="text-align: center; margin-bottom: 24px;">
        <h1 style="color: #78350f; font-size: 24px; font-weight: bold; margin: 0;">MỘC AN</h1>
        <p style="color: #92400e; font-size: 13px; margin: 4px 0 0 0;">Nội Thất Gỗ Tự Nhiên &amp; Sang Trọng</p>
    </div>
    <div style="border-top: 1px solid #f3f4f6; padding-top: 20px;">
        <p style="color: #1f2937; font-size: 15px; margin: 0 0 12px 0;">Xin chào,</p>
        <p style="color: #4b5563; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;">
            @if(($purpose ?? 'verify') === 'reset')
                Chúng tôi nhận được yêu cầu <strong>đặt lại mật khẩu</strong> cho tài khoản của bạn tại <strong>Mộc An</strong>. Dưới đây là mã xác thực:
            @else
                Cảm ơn bạn đã đăng ký tài khoản tại <strong>Mộc An</strong>. Dưới đây là mã xác thực tài khoản của bạn:
            @endif
        </p>
        <div style="text-align: center; margin: 24px 0;">
            <span style="display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #78350f; background: #fef3c7; border: 1px dashed #d97706; padding: 12px 28px; border-radius: 8px;">{{ $code }}</span>
        </div>
        <p style="color: #6b7280; font-size: 13px; line-height: 1.5; margin: 0 0 8px 0;">
            • Mã xác thực có hiệu lực trong <strong>10 phút</strong>.
        </p>
        @if(($purpose ?? 'verify') === 'reset')
            <p style="color: #6b7280; font-size: 13px; line-height: 1.5; margin: 0 0 8px 0;">
                • Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này. Mật khẩu hiện tại vẫn được giữ nguyên.
            </p>
        @endif
        <p style="color: #ef4444; font-size: 12px; line-height: 1.5; margin: 0;">
            • Tuyệt đối không chia sẻ mã này cho bất kỳ ai để bảo vệ tài khoản của bạn.
        </p>
    </div>
    <div style="border-top: 1px solid #f3f4f6; margin-top: 24px; padding-top: 16px; text-align: center; color: #9ca3af; font-size: 12px;">
        © {{ date('Y') }} Mộc An. Mọi quyền được bảo lưu.
    </div>
</div>
