<?php include __DIR__ . '/../parts/header.php'; ?>

<style>
    .post-detail-header { text-align: center; padding: 60px 0 40px; }
    .post-breadcrumb { font-size: 13px; color: var(--gray); margin-bottom: 20px; }
    .post-detail-header h1 { font-size: 42px; font-weight: 800; max-width: 900px; margin: 0 auto 30px; line-height: 1.2; }
    .post-detail-meta { display: flex; align-items: center; justify-content: center; gap: 20px; font-size: 14px; color: var(--gray); }
    .post-author { display: flex; align-items: center; gap: 10px; color: var(--dark); font-weight: 600; text-decoration: none; }
    
    .post-featured-image { width: 100%; border-radius: 24px; margin-bottom: 50px; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.1); }
    .post-featured-image img { width: 100%; height: auto; display: block; }
    
    .post-content-layout { display: grid; grid-template-columns: 3fr 1fr; gap: 60px; }
    .entry-content { font-size: 18px; line-height: 1.8; color: #334155; }
    .entry-content h2 { font-size: 28px; margin: 40px 0 20px; color: var(--dark); }
    .entry-content p { margin-bottom: 25px; }
    .entry-content blockquote { border-left: 5px solid var(--primary); background: #f8fafc; padding: 30px; margin: 40px 0; font-style: italic; font-size: 20px; color: var(--dark); }
    
    .post-tags { margin-top: 50px; padding-top: 30px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; flex-wrap: wrap; }
    .post-tags a { background: #f1f5f9; color: var(--gray); font-size: 13px; font-weight: 600; padding: 6px 15px; border-radius: 20px; text-decoration: none; transition: 0.3s; }
    .post-tags a:hover { background: var(--primary); color: white; }
    
    .author-box { background: #f8fafc; border-radius: 24px; padding: 40px; margin-top: 60px; display: flex; gap: 30px; }
    .author-avatar img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; }
    .author-info h4 { margin: 0 0 10px; font-size: 20px; color: var(--dark); }
    .author-info p { margin: 0; color: var(--gray); font-size: 15px; line-height: 1.6; }
    
    .comments-section { margin-top: 80px; }
    .comment-list { list-style: none; padding: 0; margin: 40px 0; }
    .comment-item { display: flex; gap: 20px; margin-bottom: 40px; }
    .comment-avatar img { width: 50px; height: 50px; border-radius: 50%; }
    .comment-body { flex: 1; background: white; border: 1px solid #f1f5f9; padding: 25px; border-radius: 16px; position: relative; }
    .comment-body h5 { margin: 0 0 5px; font-size: 16px; }
    .comment-date { font-size: 12px; color: var(--gray); margin-bottom: 15px; display: block; }
    .comment-content { font-size: 15px; line-height: 1.6; color: #475569; }
    
    .comment-form-box { background: #f8fafc; padding: 40px; border-radius: 24px; margin-top: 50px; }
    .comment-form textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; min-height: 150px; margin-bottom: 20px; font-family: inherit; }
    .comment-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .comment-form input { width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px 20px; font-family: inherit; }
    
    .toc-widget { position: sticky; top: 100px; }
    .toc-list { list-style: none; padding: 0; }
    .toc-list li { margin-bottom: 12px; }
    .toc-list a { color: var(--gray); text-decoration: none; font-size: 14px; display: block; padding-left: 15px; border-left: 2px solid transparent; }
    .toc-list a.active { color: var(--primary); border-left-color: var(--primary); font-weight: 600; }
</style>

<article class="container">
    <header class="post-detail-header">
        <div class="post-breadcrumb">
            <a href="/blog">Blog</a> / <a href="/blog/category/<?php echo $post['category_slug']; ?>"><?php echo $post['category_name']; ?></a>
        </div>
        <h1><?php echo $post['title']; ?></h1>
        <div class="post-detail-meta">
            <a href="#" class="post-author">
                <img src="https://i.pravatar.cc/32?u=admin" style="border-radius: 50%;">
                Alex Nguyen
            </a>
            <span>📅 <?php echo date('d M, Y', strtotime($post['published_at'])); ?></span>
            <span>⏱️ <?php echo $post['reading_time']; ?> phút đọc</span>
            <span>👁️ <?php echo $post['view_count']; ?> lượt xem</span>
        </div>
    </header>

    <div class="post-featured-image">
        <img src="<?php echo $post['featured_image'] ?: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=1200'; ?>" alt="">
    </div>

    <div class="post-content-layout">
        <div class="post-main-content">
            <div class="entry-content">
                <?php echo nl2br($post['content']); ?>
                
                <h2>Core Web Vitals là gì?</h2>
                <p>Core Web Vitals là một bộ chỉ số thực tế từ Google giúp đo lường trải nghiệm người dùng trên website của bạn. Các chỉ số này tập trung vào ba khía cạnh chính: hiệu năng tải trang, khả năng tương tác và độ ổn định thị giác.</p>
                
                <blockquote>
                    "Website chạy nhanh không chỉ tốt cho SEO, mà nó còn giúp bạn giữ chân khách hàng lâu hơn."
                    <cite>- Google Developers</cite>
                </blockquote>
            </div>

            <div class="post-tags">
                <?php foreach ($tags as $tag): ?>
                <a href="/blog/tag/<?php echo $tag['slug']; ?>">#<?php echo $tag['name']; ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Author Box -->
            <div class="author-box">
                <div class="author-avatar">
                    <img src="https://i.pravatar.cc/100?u=admin" alt="">
                </div>
                <div class="author-info">
                    <h4>Alex Nguyen</h4>
                    <p>Chuyên gia tối ưu hóa website và SEO với hơn 10 năm kinh nghiệm. Hiện đang công tác tại DigitalCore với vai trò Lead Technical Editor.</p>
                    <div style="margin-top: 15px; display: flex; gap: 15px;">
                        <a href="#" style="color: var(--primary); font-size: 18px;">f</a>
                        <a href="#" style="color: var(--primary); font-size: 18px;">t</a>
                        <a href="#" style="color: var(--primary); font-size: 18px;">l</a>
                    </div>
                </div>
            </div>

            <!-- Related Posts -->
            <div style="margin-top: 80px;">
                <h3 style="font-size: 24px; margin-bottom: 30px;">Có thể bạn quan tâm</h3>
                <div class="posts-grid">
                    <?php foreach ($related_posts as $rp): ?>
                    <div class="post-card">
                        <div class="post-thumb" style="height: 160px;">
                            <img src="<?php echo $rp['featured_image'] ?: 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&q=80&w=800'; ?>" alt="">
                        </div>
                        <div class="post-info" style="padding: 20px;">
                            <h4 style="font-size: 16px; margin: 0;"><a href="/blog/<?php echo $rp['slug']; ?>" style="text-decoration: none; color: inherit;"><?php echo $rp['title']; ?></a></h4>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Comments -->
            <div class="comments-section">
                <h3 style="font-size: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px;">Thảo luận (<?php echo count($comments); ?>)</h3>
                <ul class="comment-list">
                    <?php if (empty($comments)): ?>
                    <li style="color: var(--gray);">Chưa có bình luận nào. Hãy là người đầu tiên!</li>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                        <li class="comment-item">
                            <div class="comment-avatar">
                                <img src="https://i.pravatar.cc/50?u=<?php echo $comment['author_email']; ?>" alt="">
                            </div>
                            <div class="comment-body">
                                <h5><?php echo $comment['author_name']; ?></h5>
                                <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                                <div class="comment-content">
                                    <?php echo nl2br($comment['content']); ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>

                <div class="comment-form-box">
                    <h4 style="margin-bottom: 25px;">Gửi bình luận của bạn</h4>
                    <form class="comment-form">
                        <textarea placeholder="Nội dung bình luận..."></textarea>
                        <div class="comment-form-row">
                            <input type="text" placeholder="Họ và tên *">
                            <input type="email" placeholder="Email (không công khai) *">
                        </div>
                        <button type="submit" class="btn-primary" style="border: none; cursor: pointer;">Gửi bình luận</button>
                    </form>
                </div>
            </div>
        </div>

        <aside class="sidebar">
            <div class="toc-widget">
                <h4 class="widget-title">Mục lục bài viết</h4>
                <ul class="toc-list">
                    <li><a href="#" class="active">Core Web Vitals là gì?</a></li>
                    <li><a href="#">Tại sao nên tối ưu Core Web Vitals</a></li>
                    <li><a href="#">Tối ưu hóa LCP (Largest Contentful Paint)</a></li>
                    <li><a href="#">Tối ưu hóa FID (First Input Delay)</a></li>
                    <li><a href="#">Tối ưu hóa CLS (Cumulative Layout Shift)</a></li>
                </ul>

                <div class="widget" style="margin-top: 40px; background: #0f172a; color: white; padding: 30px; border-radius: 16px;">
                    <h4 style="color: white; margin-bottom: 15px;">Tham gia Bản tin</h4>
                    <p style="font-size: 13px; opacity: 0.7; margin-bottom: 20px;">Nhận nội dung tuyển chọn hàng tuần.</p>
                    <div style="display: flex; gap: 5px;">
                        <input type="email" placeholder="Email..." style="background: rgba(255,255,255,0.1); border: none; padding: 10px; border-radius: 6px; color: white; font-size: 13px; flex: 1;">
                        <button style="background: var(--primary); color: white; border: none; width: 40px; border-radius: 6px; cursor: pointer;">→</button>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</article>

<?php include __DIR__ . '/../parts/footer.php'; ?>
