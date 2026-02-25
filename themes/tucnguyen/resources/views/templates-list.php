<?php include __DIR__ . '/parts/header.php'; ?>

<style>
    .templates-hero {
        background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent), 
                    radial-gradient(circle at bottom left, rgba(99, 102, 241, 0.05), transparent), #fff;
        padding: 100px 0 60px;
        text-align: center;
        border-bottom: 1px solid #f1f5f9;
        position: relative;
        overflow: hidden;
    }
    .templates-hero::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 40px;
        background: linear-gradient(to top, #f8fafc, transparent);
    }

    .templates-hero h1 { font-size: 48px; font-weight: 800; margin-bottom: 20px; letter-spacing: -1px; }
    .templates-hero p { color: var(--gray); font-size: 18px; max-width: 700px; margin: 0 auto; line-height: 1.6; }

    .filter-container {
        position: sticky;
        top: 80px;
        background: rgba(255, 255, 255, 0.82);
        backdrop-filter: blur(12px);
        z-index: 100;
        padding: 20px 0;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 40px;
    }
    .filter-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 30px;
    }

    .search-box {
        position: relative;
        flex: 1;
        max-width: 400px;
    }
    .search-box input {
        width: 100%;
        padding: 12px 20px 12px 45px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        font-family: inherit;
        font-size: 15px;
        transition: 0.3s;
        background: white;
    }
    .search-box input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        outline: none;
    }
    .search-box svg {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
    }

    .category-shelf {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 5px;
        scrollbar-width: none;
    }
    .category-shelf::-webkit-scrollbar { display: none; }
    
    .cat-btn {
        padding: 8px 20px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: white;
        color: var(--gray);
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        transition: 0.3s;
    }
    .cat-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    .cat-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    }

    .templates-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 35px;
        padding-bottom: 80px;
    }

    .tp-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .tp-card:hover {
        transform: translateY(-12px);
        box-shadow: 0 40px 80px rgba(0,0,0,0.06);
    }

    .tp-thumb {
        height: 250px;
        position: relative;
        overflow: hidden;
    }
    .tp-thumb img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        transition: 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    .tp-card:hover .tp-thumb img {
        transform: scale(1.08);
    }
    .tp-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(4px);
        color: white;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tp-info { padding: 30px; flex: 1; display: flex; flex-direction: column; }
    .tp-info h3 { font-size: 22px; font-weight: 800; margin-bottom: 12px; color: var(--dark); }
    .tp-info p { font-size: 15px; color: var(--gray); line-height: 1.6; margin-bottom: 25px; height: 48px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    
    .tp-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 25px;
        border-top: 1px solid #f1f5f9;
        margin-top: auto;
    }
    .tp-price { font-size: 24px; font-weight: 800; color: var(--dark); }
    .btn-buy-tp {
        background: var(--primary);
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        transition: 0.3s;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }
    .btn-buy-tp:hover { 
        background: #2563EB;
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25);
        transform: translateY(-2px);
    }

    .empty-state {
        text-align: center;
        padding: 100px 0;
        background: white;
        border-radius: 30px;
        border: 2px dashed #e2e8f0;
        margin-bottom: 80px;
    }
    .empty-state .icon { font-size: 64px; margin-bottom: 20px; display: block; }
    .empty-state h2 { font-size: 24px; font-weight: 700; color: var(--dark); margin-bottom: 10px; }
    .empty-state p { color: var(--gray); }

    @media (max-width: 1024px) {
        .templates-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
    }
    @media (max-width: 768px) {
        .filter-inner { flex-direction: column; align-items: stretch; gap: 20px; }
        .search-box { max-width: none; }
    }
    @media (max-width: 600px) {
        .templates-grid { grid-template-columns: 1fr; }
        .templates-hero h1 { font-size: 32px; }
    }
</style>

<main style="background: #f8fafc;">
    <section class="templates-hero">
        <div class="container text-center">
            <div class="hero-badge" style="margin: 0 auto 20px; background: rgba(59, 130, 246, 0.1); color: var(--primary); padding: 6px 16px; border-radius: 99px; display: inline-block; font-weight: 700; font-size: 12px;">🏠 <?php echo __('PREMIUM TEMPLATE LIBRARY'); ?></div>
            <h1><?php echo __('Discover'); ?> <span class="gradient-text"><?php echo __('Professional Templates'); ?></span></h1>
            <p><?php echo __('Optimal solutions for businesses and individuals. Built-in essential features, SEO-ready, UI/UX optimized, and outstanding performance.'); ?></p>
        </div>
    </section>

    <div class="filter-container">
        <div class="container">
            <form action="" method="GET" class="filter-inner">
                <div class="category-shelf">
                    <a href="<?php echo route_url('web-templates'); ?>" class="cat-btn <?php echo !$current_category ? 'active' : ''; ?>"><?php echo __('All'); ?></a>
                    <?php foreach($categories as $cat): ?>
                    <a href="<?php echo route_url('web-templates') . '/' . $cat['category_slug']; ?>" 
                       class="cat-btn <?php echo $current_category === $cat['category'] ? 'active' : ''; ?>">
                       <?php echo $cat['category']; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="search-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="search" placeholder="<?php echo __('Search templates or features...'); ?>" value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                    <?php if ($current_category): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($current_category); ?>">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <?php if (empty($templates)): ?>
            <div class="empty-state">
                <span class="icon">🔍</span>
                <h2>Không tìm thấy mẫu nào phù hợp</h2>
                <p>Thử tìm kiếm với từ khóa khác hoặc xóa bộ lọc danh mục.</p>
                <a href="?" class="btn-buy-tp" style="margin-top: 25px; display: inline-block;">Xem tất cả giao diện</a>
            </div>
        <?php else: ?>
            <div class="templates-grid">
                <?php foreach($templates as $tp): ?>
                <div class="tp-card">
                    <div class="tp-thumb">
                        <img src="<?php echo $tp['image_url']; ?>" alt="<?php echo $tp['name']; ?>">
                        <div class="tp-badge"><?php echo $tp['category']; ?></div>
                    </div>
                    <div class="tp-info">
                        <h3><?php echo $tp['name']; ?></h3>
                        <p><?php echo $tp['description']; ?></p>
                        <div class="tp-footer">
                            <div class="tp-price">$<?php echo number_format($tp['price'], 2); ?></div>
                            <a href="/checkout/template/<?php echo $tp['slug']; ?>" class="btn-buy-tp"><?php echo __('Buy Now'); ?></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/parts/footer.php'; ?>
