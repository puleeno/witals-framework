<?php include __DIR__ . '/parts/header.php'; ?>

<style>
    /* Premium Boltz Portal Theme - Refined Contrast */
    :root {
        --boltz-bg: #F4F7FE;
        --boltz-primary: #4318FF;
        --boltz-text-dark: #1B2559;
        --boltz-text-gray: #475467; /* Darker than before for readability */
        --boltz-text-muted: #68769F; /* Darker than before for readability */
        --boltz-white: #FFFFFF;
    }

    .portal-wrapper {
        background-color: var(--boltz-bg);
        min-height: 100vh;
        padding: 40px 0 80px;
        font-family: 'Inter', sans-serif;
        color: var(--boltz-text-gray);
    }
    
    .portal-main-grid {
        display: grid;
        grid-template-columns: 280px 1fr 340px;
        gap: 24px;
        align-items: start;
    }

    /* Shared Card Style */
    .glass-card {
        background: var(--boltz-white);
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.02);
    }

    /* Left Sidebar */
    .portal-sidebar {
        padding: 30px 20px;
    }
    
    .sidebar-brand-mini {
        padding: 0 15px 30px;
        border-bottom: 1px solid #E0E5F2;
        margin-bottom: 30px;
    }
    
    .sidebar-user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-avatar-small {
        width: 44px;
        height: 44px;
        background: var(--boltz-primary);
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 14px;
    }
    
    .user-text h4 { font-size: 15px; margin: 0; color: var(--boltz-text-dark); }
    .user-text span { font-size: 12px; color: var(--boltz-text-muted); }

    .portal-nav-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .portal-nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        border-radius: 16px;
        text-decoration: none;
        color: var(--boltz-text-muted);
        font-weight: 600;
        font-size: 14px;
        transition: 0.2s;
    }
    
    .portal-nav-link:hover {
        background: #E9EFFF;
        color: var(--boltz-primary);
    }
    
    .portal-nav-link.active {
        background: #E9EFFF;
        color: var(--boltz-primary);
    }

    /* Central Content Area */
    .portal-stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .stat-card-mini {
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .stat-card-mini .icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: var(--boltz-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    
    .stat-card-mini .data {
        display: flex;
        flex-direction: column;
    }
    
    .stat-card-mini .label { font-size: 12px; color: var(--boltz-text-muted); font-weight: 600; margin-bottom: 2px; }
    .stat-card-mini .value { font-size: 18px; font-weight: 800; color: var(--boltz-text-dark); }

    /* Content Card */
    .content-main-card {
        padding: 30px;
        color: var(--boltz-text-gray);
    }

    /* Right Sidebar */
    .portal-right-sidebar {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .profile-boltz-card {
        padding: 30px;
        text-align: center;
    }
    
    .profile-boltz-card .avatar-main {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 15px;
        border: 4px solid var(--boltz-bg);
    }
    
    .profile-boltz-card h3 { font-size: 20px; font-weight: 800; color: var(--boltz-text-dark); margin: 0 0 5px; }
    .profile-boltz-card .tag { font-size: 13px; color: var(--boltz-text-muted); font-weight: 600; margin-bottom: 20px; display: block; }
    
    .horizontal-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        padding: 20px 0;
        border-top: 1px solid #E0E5F2;
        border-bottom: 1px solid #E0E5F2;
        margin-bottom: 20px;
    }
    
    .h-stat-item .val { font-size: 15px; font-weight: 800; color: var(--boltz-text-dark); }
    .h-stat-item .lbl { font-size: 11px; color: var(--boltz-text-muted); font-weight: 500; }

    .weekly-revenue-mock {
        padding: 30px;
    }

    .weekly-revenue-mock h4 { font-size: 14px; font-weight: 800; color: var(--boltz-text-dark); margin: 0 0 15px; }

    .mock-chart {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        height: 60px;
        padding-top: 10px;
    }
    
    .bar { 
        flex: 1; 
        background: var(--boltz-primary); 
        border-radius: 4px; 
        opacity: 0.6; 
        transition: 0.3s; 
    }
    .bar:hover { opacity: 1; transform: scaleY(1.1); }

    /* Portal Page Header */
    .portal-page-header {
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .portal-page-header h2 { font-size: 24px; font-weight: 800; color: var(--boltz-text-dark); margin: 0; }
    .portal-breadcrumb { font-size: 13px; color: var(--boltz-text-muted); font-weight: 500; }

    /* Responsive */
    @media (max-width: 1200px) {
        .portal-main-grid { grid-template-columns: 240px 1fr; }
        .portal-right-sidebar { display: none; }
    }
    @media (max-width: 900px) {
        .portal-main-grid { grid-template-columns: 1fr; }
        .portal-sidebar { display: none; }
    }
</style>

<div class="portal-wrapper">
    <div class="container portal-main-grid">
        <!-- Sidebar Navigation -->
        <aside class="glass-card portal-sidebar">
            <div class="sidebar-brand-mini">
                <div class="sidebar-user-info">
                    <div class="user-avatar-small">AD</div>
                    <div class="user-text">
                        <h4>Alexander Dev</h4>
                        <span>VIP Member</span>
                    </div>
                </div>
            </div>
            
            <nav class="portal-nav-list">
                <a href="/portal" class="portal-nav-link <?php echo $_SERVER['REQUEST_URI'] === '/portal' ? 'active' : ''; ?>">
                    <span class="nav-icon-box">🏠</span> <?php echo __('Bảng điều khiển'); ?>
                </a>
                <a href="/portal/services" class="portal-nav-link <?php echo str_contains($_SERVER['REQUEST_URI'], '/services') ? 'active' : ''; ?>">
                    <span class="nav-icon-box">📦</span> <?php echo __('Sản phẩm đã mua'); ?>
                </a>
                <a href="/portal/billing" class="portal-nav-link <?php echo str_contains($_SERVER['REQUEST_URI'], '/billing') ? 'active' : ''; ?>">
                    <span class="nav-icon-box">💳</span> <?php echo __('Thanh toán'); ?>
                </a>
                <a href="/portal/tickets" class="portal-nav-link <?php echo str_contains($_SERVER['REQUEST_URI'], '/tickets') ? 'active' : ''; ?>">
                    <span class="nav-icon-box">🎫</span> <?php echo __('Hỗ trợ / Tickets'); ?>
                </a>
                <a href="/portal/affiliates" class="portal-nav-link <?php echo str_contains($_SERVER['REQUEST_URI'], '/affiliates') ? 'active' : ''; ?>">
                    <span class="nav-icon-box">🤝</span> <?php echo __('Cộng tác viên'); ?>
                </a>
                <a href="/portal/profile" class="portal-nav-link <?php echo str_contains($_SERVER['REQUEST_URI'], '/profile') ? 'active' : ''; ?>">
                    <span class="nav-icon-box">⚙️</span> <?php echo __('Cài đặt tài khoản'); ?>
                </a>
                <div style="margin-top: 20px; border-top: 1px solid #F4F7FE; padding-top: 20px;">
                    <a href="/auth/logout" class="portal-nav-link" style="color: #ef4444;">
                        <span class="nav-icon-box">🚪</span> <?php echo __('Đăng xuất'); ?>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content Central Area -->
        <main class="portal-center-content">
            <!-- Header for current page -->
            <div class="portal-page-header">
                <h2><?php echo $page['title'] ?? 'Bảng điều khiển'; ?></h2>
                <div class="portal-breadcrumb" style="font-size: 13px; color: #A3AED0;">
                    Portal / <?php echo $page['title'] ?? 'Dashboard'; ?>
                </div>
            </div>

            <!-- Stats Overview - Desktop only for dashboard -->
            <?php if ($_SERVER['REQUEST_URI'] === '/portal'): ?>
            <div class="portal-stats-row">
                <div class="glass-card stat-card-mini">
                    <div class="icon-wrap" style="color: #4318FF;">🖥️</div>
                    <div class="data">
                        <span class="label">Sản phẩm</span>
                        <span class="value">3</span>
                    </div>
                </div>
                <div class="glass-card stat-card-mini">
                    <div class="icon-wrap" style="color: #05CD99;">🌐</div>
                    <div class="data">
                        <span class="label">Tên miền</span>
                        <span class="value">1</span>
                    </div>
                </div>
                <div class="glass-card stat-card-mini">
                    <div class="icon-wrap" style="color: #FFB547;">🎫</div>
                    <div class="data">
                        <span class="label">Tickets</span>
                        <span class="value">2</span>
                    </div>
                </div>
                <div class="glass-card stat-card-mini">
                    <div class="icon-wrap" style="color: #EE5D50;">📄</div>
                    <div class="data">
                        <span class="label">Hóa đơn</span>
                        <span class="value">0</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Page Actual Content -->
            <div class="glass-card content-main-card">
                <?php echo $page['content'] ?? 'Nội dung đang được cập nhật...'; ?>
            </div>
        </main>

        <!-- Right Side Sidebar -->
        <aside class="portal-right-sidebar">
            <div class="glass-card profile-boltz-card">
                <img src="https://i.pravatar.cc/150?u=alex" class="avatar-main" alt="">
                <h3>Alexander Dev</h3>
                <span class="tag">@alex_developer</span>
                
                <div class="horizontal-stats">
                    <div class="h-stat-item"><span class="val">18</span><span class="lbl">Posts</span></div>
                    <div class="h-stat-item"><span class="val">9.5k</span><span class="lbl">Sales</span></div>
                    <div class="h-stat-item"><span class="val">421</span><span class="lbl">Projects</span></div>
                </div>
                
                <p style="font-size: 13px; color: #A3AED0; line-height: 1.6; margin: 20px 0;">
                    Thành viên từ: <b>24/03/2017</b><br>
                    Loại tài khoản: <b>VIP</b>
                </p>
                
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button style="background: #F4F7FE; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 700; color: #1B2559; font-size: 13px; cursor: pointer;">Hồ sơ</button>
                    <button style="background: #4318FF; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 700; color: white; font-size: 13px; cursor: pointer;">Nạp tiền</button>
                </div>
            </div>
            
            <div class="glass-card weekly-revenue-mock">
                <h4>Hoạt động tuần này</h4>
                <div class="mock-chart">
                    <div class="bar" style="height: 40%;"></div>
                    <div class="bar" style="height: 70%;"></div>
                    <div class="bar" style="height: 55%;"></div>
                    <div class="bar" style="height: 90%;"></div>
                    <div class="bar" style="height: 35%;"></div>
                    <div class="bar" style="height: 60%;"></div>
                    <div class="bar" style="height: 80%;"></div>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/parts/footer.php'; ?>
