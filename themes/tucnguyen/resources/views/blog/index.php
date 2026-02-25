<?php include __DIR__ . '/../parts/header.php'; ?>

<main class="container">
    <header class="blog-header">
        <h1>DigitalCore <span class="gradient-text">Blog & Kiến thức</span></h1>
        <p>Chia sẻ kiến thức chuyên sâu về Lập trình, WordPress, Hosting và các xu hướng công nghệ mới nhất. Cập nhật hàng tuần.</p>
    </header>

    <?php if ($featured_post): ?>
    <article class="featured-card">
        <div class="featured-img">
            <img src="<?php echo $featured_post['featured_image'] ?: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&q=80&w=1200'; ?>" alt="">
        </div>
        <div class="featured-body">
            <span class="badge-featured">Nổi bật</span>
            <div class="post-meta">
                <span>📅 <?php echo date('d/m/Y', strtotime($featured_post['published_at'])); ?></span>
                <span>⏱️ <?php echo $featured_post['reading_time']; ?> phút đọc</span>
            </div>
            <h2><a href="/blog/<?php echo $featured_post['slug']; ?>" style="text-decoration: none; color: inherit;"><?php echo $featured_post['title']; ?></a></h2>
            <p><?php echo $featured_post['excerpt']; ?></p>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="https://i.pravatar.cc/40?u=author" style="border-radius: 50%; width: 40px; height: 40px;">
                    <div>
                        <b style="display: block; font-size: 14px;">David Pham</b>
                        <span style="font-size: 12px; color: var(--gray);">Tác giả</span>
                    </div>
                </div>
                <a href="/blog/<?php echo $featured_post['slug']; ?>" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; text-decoration: none; color: var(--dark);">→</a>
            </div>
        </div>
    </article>
    <?php endif; ?>

    <div class="blog-grid-layout">
        <div class="posts-column">
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <div class="post-thumb">
                        <?php if ($post['category_name']): ?>
                        <span class="post-category-badge"><?php echo $post['category_name']; ?></span>
                        <?php endif; ?>
                        <img src="<?php echo $post['featured_image'] ?: 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&q=80&w=800'; ?>" alt="">
                    </div>
                    <div class="post-info">
                        <div class="post-meta">
                            <span>📅 <?php echo date('d/m/Y', strtotime($post['published_at'])); ?></span>
                            <span>⏱️ <?php echo $post['reading_time']; ?> phút</span>
                        </div>
                        <h3><a href="/blog/<?php echo $post['slug']; ?>" style="text-decoration: none; color: inherit;"><?php echo $post['title']; ?></a></h3>
                        <p><?php echo $post['excerpt']; ?></p>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="https://i.pravatar.cc/30?u=<?php echo $post['id']; ?>" style="border-radius: 50%; width: 30px; height: 30px;">
                            <span style="font-size: 13px; font-weight: 500;">Admin</span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <div style="display: flex; justify-content: center; gap: 10px; margin-top: 50px; margin-bottom: 80px;">
                <span style="width: 40px; height: 40px; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-weight: 600;">1</span>
                <span style="width: 40px; height: 40px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border-radius: 8px; cursor: pointer; font-weight: 600;">2</span>
                <span style="width: 40px; height: 40px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border-radius: 8px; cursor: pointer; font-weight: 600;">3</span>
                <span style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">...</span>
                <span style="width: 40px; height: 40px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border-radius: 8px; cursor: pointer; font-weight: 600;">12</span>
            </div>
        </div>

        <aside class="sidebar">
            <div class="widget">
                <h4 class="widget-title">Tìm kiếm</h4>
                <div class="search-widget">
                    <input type="text" placeholder="Nhập từ khóa...">
                </div>
            </div>

            <div class="widget">
                <h4 class="widget-title">Danh mục</h4>
                <ul class="cat-list">
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="/blog/category/<?php echo $cat['slug']; ?>"><?php echo $cat['name']; ?></a>
                        <span class="cat-count"><?php echo $cat['post_count']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="widget" style="background: linear-gradient(135deg, #6366F1, #3B82F6); color: white; border: none;">
                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; opacity: 0.8;">Limited Offer</span>
                <h4 style="color: white; margin: 10px 0;">Hosting Cao Cấp</h4>
                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 20px;">Giảm ngay 30% cho tất cả gói Business.</p>
                <a href="/hosting" style="display: block; background: white; color: var(--primary); text-align: center; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 700;">Nhận ưu đãi</a>
            </div>
        </aside>
    </div>
</main>

<section class="container" style="margin-bottom: 80px;">
    <div style="background: var(--dark); border-radius: 24px; padding: 60px; display: flex; align-items: center; justify-content: space-between; color: white;">
        <div style="display: flex; align-items: center; gap: 30px;">
            <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 40px;">✉️</div>
            <div>
                <h2 style="color: white; margin-bottom: 10px;">Đừng bỏ lỡ kiến thức mới!</h2>
                <p style="opacity: 0.7;">Nhận bài viết, thủ thuật và tài nguyên miễn phí hàng tuần. Không spam.</p>
            </div>
        </div>
        <div class="newsletter" style="width: 400px;">
            <input type="email" placeholder="Địa chỉ email của bạn" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); width: 250px;">
            <button style="border-radius: 8px;">Đăng ký</button>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../parts/footer.php'; ?>
