<?php include __DIR__ . '/parts/header.php'; ?>

<style>
    .single-tp-hero { padding: 100px 0; background: #f8fafc; }
    .tp-preview-grid { display: grid; grid-template-columns: 1fr 400px; gap: 40px; }
    .tp-main-img img { width: 100%; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
    .tp-sidebar-info { background: white; padding: 40px; border-radius: 24px; border: 1px solid #e2e8f0; height: fit-content; sticky; top: 100px; }
    .tp-sidebar-info h1 { font-size: 32px; font-weight: 800; margin-bottom: 20px; }
    .tp-sidebar-info .price { font-size: 36px; font-weight: 800; color: var(--primary); margin-bottom: 30px; }
    .btn-checkout { display: block; background: var(--primary); color: white; text-align: center; padding: 18px; border-radius: 14px; text-decoration: none; font-weight: 800; font-size: 16px; margin-bottom: 20px; }
    .btn-demo { display: block; border: 2px solid #e2e8f0; text-align: center; padding: 16px; border-radius: 14px; text-decoration: none; font-weight: 700; color: var(--dark); }
</style>

<main class="single-tp-hero">
    <div class="container">
        <div class="tp-preview-grid">
            <div class="tp-main-img">
                <img src="<?php echo $template['image_url']; ?>" alt="">
                <div style="margin-top: 40px;">
                    <h2>Chi tiết giao diện</h2>
                    <p><?php echo $template['description']; ?></p>
                </div>
            </div>
            <aside class="tp-sidebar-info">
                <h1><?php echo $template['name']; ?></h1>
                <div class="price">$<?php echo number_format($template['price'], 2); ?></div>
                <a href="/checkout/template/<?php echo $template['slug']; ?>" class="btn-checkout">Mua Giao Diện Ngay</a>
                <a href="<?php echo $template['demo_url'] ?? '#'; ?>" target="_blank" class="btn-demo">Xem Bản Demo 🔗</a>
                
                <ul style="margin-top: 40px; font-size: 14px; color: var(--gray); list-style: none; padding: 0;">
                    <li style="margin-bottom: 12px;">✅ Hỗ trợ cài đặt miễn phí</li>
                    <li style="margin-bottom: 12px;">✅ Tối ưu SEO & Google PageSpeed</li>
                    <li style="margin-bottom: 12px;">✅ Cập nhật trọn đời</li>
                </ul>
            </aside>
        </div>
    </div>
</main>

<?php include __DIR__ . '/parts/footer.php'; ?>
