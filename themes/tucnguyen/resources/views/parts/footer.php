    <footer>
        <div class="container footer-grid">
            <div class="footer-col">
                <div class="logo" style="color: white; margin-bottom: 20px;">
                    Digital<span>Core.</span>
                </div>
                <p><?php echo __('The leading digital resource platform for developers and creative businesses worldwide.'); ?></p>
                <div class="socials" style="display: flex; gap: 10px; margin-top: 20px;">
                    <?php 
                    $locale = current_locale();
                    if ($locale === 'vi'): ?>
                        <a href="#" title="Zalo" style="width: 32px; height: 32px; background: #0084FF; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-weight: bold; font-size: 11px;">Zalo</a>
                        <a href="#" title="Facebook" style="width: 32px; height: 32px; background: #1b74e4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-weight: bold; font-size: 14px;">f</a>
                    <?php elseif ($locale === 'ja'): ?>
                        <a href="#" title="Line" style="width: 32px; height: 32px; background: #06C755; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-weight: bold; font-size: 11px;">Line</a>
                        <a href="#" title="Twitter" style="width: 32px; height: 32px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-weight: bold; font-size: 14px;">X</a>
                    <?php elseif ($locale === 'ko'): ?>
                        <a href="#" title="KakaoTalk" style="width: 32px; height: 32px; background: #FEE500; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #3C1E1E; text-decoration: none; font-weight: bold; font-size: 11px;">Talk</a>
                    <?php else: ?>
                        <a href="#" title="Telegram" style="width: 32px; height: 32px; background: #0088cc; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-weight: bold; font-size: 14px;">T</a>
                        <a href="#" title="Twitter" style="width: 32px; height: 32px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; font-weight: bold; font-size: 14px;">X</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer-col">
                <h4><?php echo __('Solutions'); ?></h4>
                <ul>
                    <li><a href="#"><?php echo __('Domain Search'); ?></a></li>
                    <li><a href="#"><?php echo __('Premium Themes'); ?></a></li>
                    <li><a href="<?php echo locale_url(__('routes.web-templates', [], $locale), $locale); ?>"><?php echo __('Website Templates'); ?></a></li>
                    <li><a href="#"><?php echo __('Pro Software'); ?></a></li>
                    <li><a href="<?php echo locale_url('/portal', $locale); ?>"><?php echo __('Client Dashboard'); ?></a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4><?php echo __('Company'); ?></h4>
                <ul>
                    <li><a href="<?php echo locale_url('/about', $locale); ?>"><?php echo __('About Us'); ?></a></li>
                    <li><a href="<?php echo locale_url('/contact', $locale); ?>"><?php echo __('Contact Support'); ?></a></li>
                    <li><a href="<?php echo locale_url('/privacy-policy', $locale); ?>"><?php echo __('Security'); ?></a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4><?php echo __('Payment Methods'); ?></h4>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px;">
                    <?php 
                    if ($locale === 'vi'): ?>
                        <div class="pay-badge" style="background: white; color: #1B2559; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid #E0E5F2;">VietQR</div>
                        <div class="pay-badge" style="background: white; color: #003087; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid #E0E5F2;">PayPal Biz</div>
                        <div class="pay-badge" style="background: white; color: #EE5D50; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid #E0E5F2;">CK Ngân Hàng</div>
                    <?php elseif ($locale === 'ja'): ?>
                        <div class="pay-badge" style="background: white; color: #003087; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid #E0E5F2;">Stripe</div>
                        <div class="pay-badge" style="background: white; color: #000; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid #E0E5F2;">銀行振込</div>
                    <?php elseif ($locale === 'ko'): ?>
                        <div class="pay-badge" style="background: white; color: #00CD3C; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid #E0E5F2;">Naver Pay</div>
                        <div class="pay-badge" style="background: white; color: #003087; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid #E0E5F2;">Card</div>
                    <?php else: ?>
                        <div class="pay-badge" style="background: white; color: #003087; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid #E0E5F2;">PayPal Global</div>
                        <div class="pay-badge" style="background: white; color: #6366F1; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; border: 1px solid #E0E5F2;">Stripe</div>
                    <?php endif; ?>
                </div>
                <p style="font-size: 12px; margin-top: 15px; color: #94a3b8;"><?php echo __('Safe & secure transactions for all your digital needs.'); ?></p>
            </div>
        </div>
        <div class="container footer-bottom">
            <div>© <?php echo date('Y'); ?> Optilarity. All rights reserved.</div>
            <div style="display: flex; gap: 20px;">
                <a href="<?php echo locale_url('/privacy-policy', $locale); ?>"><?php echo __('Privacy Policy'); ?></a>
                <a href="<?php echo locale_url('/contact', $locale); ?>"><?php echo __('Terms of Service'); ?></a>
            </div>
        </div>
    </footer>
</body>
</html>
