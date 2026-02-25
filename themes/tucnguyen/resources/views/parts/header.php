<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'DigitalCore'; ?> - Elevate Your Digital Projects</title>
    <?php echo app(\Witals\Framework\Support\AssetManager::class)->renderCss(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3B82F6;
            --secondary: #6366F1;
            --dark: #0F172A;
            --gray: #64748B;
            --light: #F8FAFC;
            --orange: #F59E0B;
            --cyan: #06B6D4;
        }
        body { font-family: 'Inter', sans-serif; margin: 0; color: var(--dark); background: #fff; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        header { background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(12px); position: sticky; top: 0; z-index: 9999; border-bottom: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .header-inner { height: 80px; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-size: 24px; font-weight: 800; display: flex; align-items: center; gap: 10px; color: var(--dark); text-decoration: none; }
        .logo span { color: var(--primary); }
        nav ul { display: flex; list-style: none; gap: 30px; margin: 0; padding: 0; }
        nav a { text-decoration: none; color: var(--gray); font-weight: 500; font-size: 15px; transition: 0.3s; }
        nav a:hover, nav a.active { color: var(--primary); }
        
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .btn-login { text-decoration: none; color: var(--dark); font-weight: 600; font-size: 15px; }
        .btn-primary { background: var(--primary); color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn-primary:hover { background: #2563EB; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }

        .gradient-text { background: linear-gradient(90deg, #3B82F6, #6366F1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        footer { background: #0b1120; color: #94a3b8; padding: 80px 0 30px; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1.5fr; gap: 50px; border-bottom: 1px solid #1e293b; padding-bottom: 50px; }
        .footer-col h4 { color: white; margin-bottom: 25px; font-size: 18px; }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li { margin-bottom: 15px; }
        .footer-col ul a { color: #94a3b8; text-decoration: none; transition: 0.3s; }
        .footer-col ul a:hover { color: var(--primary); }
        .newsletter { display: flex; gap: 10px; margin-top: 20px; }
        .newsletter input { background: #1e293b; border: none; padding: 12px 15px; border-radius: 6px; color: white; flex: 1; }
        .newsletter button { background: var(--primary); border: none; color: white; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .footer-bottom { display: flex; justify-content: space-between; padding-top: 30px; font-size: 14px; }
        .footer-bottom a { color: #94a3b8; text-decoration: none; }
        
        /* Blog Specific Styles */
        .blog-header { text-align: center; padding: 100px 0 60px; position: relative; z-index: 1; }
        .blog-header h1 { font-size: 48px; font-weight: 800; margin-bottom: 15px; }
        .blog-header p { color: var(--gray); max-width: 600px; margin: 0 auto; line-height: 1.6; }
        
        .featured-card { display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.05); margin-bottom: 60px; }
        .featured-img img { width: 100%; height: 100%; object-fit: cover; }
        .featured-body { padding: 40px; display: flex; flex-direction: column; justify-content: center; }
        .badge-featured { background: #FDE68A; color: #92400E; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; width: fit-content; margin-bottom: 20px; }
        .featured-body h2 { font-size: 32px; font-weight: 800; margin-bottom: 20px; line-height: 1.3; }
        .featured-body p { color: var(--gray); line-height: 1.7; margin-bottom: 30px; }
        
        .blog-grid-layout { display: grid; grid-template-columns: 3fr 1fr; gap: 40px; }
        .posts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
        .post-card { background: white; border-radius: 16px; overflow: hidden; border: 1px solid #f1f5f9; transition: 0.3s; }
        .post-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .post-thumb { height: 200px; position: relative; }
        .post-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .post-category-badge { position: absolute; top: 15px; left: 15px; background: var(--primary); color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .post-info { padding: 25px; }
        .post-meta { font-size: 13px; color: var(--gray); margin-bottom: 12px; display: flex; gap: 15px; }
        .post-info h3 { font-size: 20px; margin-bottom: 15px; line-height: 1.4; }
        .post-info p { color: var(--gray); font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
        
        .sidebar { position: sticky; top: 100px; height: fit-content; }
        .widget { background: white; border-radius: 16px; border: 1px solid #f1f5f9; padding: 25px; margin-bottom: 30px; }
        .widget-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; padding-left: 10px; border-left: 4px solid var(--primary); }
        .search-widget { display: flex; gap: 10px; }
        .search-widget input { flex: 1; padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .cat-list { list-style: none; padding: 0; margin: 0; }
        .cat-list li { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .cat-list a { text-decoration: none; color: var(--gray); font-weight: 500; font-size: 14px; }
        .cat-count { background: #f1f5f9; padding: 2px 8px; border-radius: 10px; font-size: 12px; color: var(--gray); }

        /* Dropdown Stylings */
        .nav-item-has-dropdown { position: relative; }
        .nav-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            transform: translateY(10px);
            background: white;
            min-width: 850px;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.12);
            border: 1px solid #f1f5f9;
            padding: 30px;
            opacity: 0;
            visibility: hidden;
            transition: 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            z-index: 10000;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .nav-dropdown.wide-2col { min-width: 650px; grid-template-columns: repeat(2, 1fr); }

        .nav-dropdown.single-col { min-width: 250px; grid-template-columns: 1fr; }
        
        .nav-item-has-dropdown:hover .nav-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* User Avatar & Dropdown */
        .user-profile-nav {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 5px;
            border-radius: 99px;
            transition: 0.3s;
        }
        .user-profile-nav:hover { background: #f1f5f9; }
        
        .user-avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            border: 2px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 240px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid #f1f5f9;
            padding: 10px;
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: 0.3s;
            z-index: 10001;
        }
        
        .user-profile-nav:hover .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-dropdown-header {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 10px;
        }
        .user-dropdown-header .name { display: block; font-weight: 700; color: var(--dark); font-size: 14px; }
        .user-dropdown-header .email { display: block; font-size: 12px; color: var(--gray); }
        
        .user-dropdown-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--dark);
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }
        .user-dropdown-link:hover { background: #f8fafc; color: var(--primary); }
        .user-dropdown-link i { font-size: 16px; }
        .user-dropdown-link.logout { color: #ef4444; }
        .user-dropdown-link.logout:hover { background: #fef2f2; color: #dc2626; }

        .dropdown-group-title {
            grid-column: span 1;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary);
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-left: 12px;
        }
        
        .dropdown-link {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 12px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark);
            transition: 0.2s;
        }
        .dropdown-link:hover { background: #f8fafc; }
        .dropdown-link .icon { 
            width: 40px; height: 40px; 
            background: #eff6ff; border-radius: 10px; 
            display: flex; align-items: center; justify-content: center; 
            color: var(--primary); font-size: 20px;
            flex-shrink: 0;
        }
        .dropdown-link .text { display: flex; flex-direction: column; }
        .dropdown-link .title { font-weight: 700; font-size: 14px; margin-bottom: 2px; white-space: nowrap; }
        .dropdown-link .desc { font-size: 12px; color: var(--gray); font-weight: 400; line-height: 1.4; display: block; }
        .nav-arrow { font-size: 9px; margin-left: 6px; vertical-align: middle; }
    </style>
</head>
<body>
    <header>
        <div class="container header-inner">
            <a href="/" class="logo">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="8" fill="#3B82F6"/>
                    <path d="M12 10H20C21.1046 10 22 10.8954 22 12V20C22 21.1046 21.1046 22 20 22H12C10.8954 22 10 21.1046 10 20V12C10 10.8954 10.8954 10 12 10Z" stroke="white" stroke-width="2"/>
                    <path d="M16 14V18" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <path d="M14 16H18" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Digital<span>Core.</span>
            </a>
            <nav>
                <ul>
                    <li class="nav-item-has-dropdown">
                        <a href="#"><?php echo __('Products'); ?> <span class="nav-arrow">▼</span></a>
                        <div class="nav-dropdown">
                            <div>
                                <div class="dropdown-group-title"><?php echo __('Cloud & Infrastructure'); ?></div>
                                <a href="/hosting" class="dropdown-link">
                                    <div class="icon">☁️</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Hosting'); ?></span>
                                        <span class="desc"><?php echo __('High-speed web hosting'); ?></span>
                                    </div>
                                </a>
                                <a href="/vps" class="dropdown-link">
                                    <div class="icon">⚡</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('VPS Server'); ?></span>
                                        <span class="desc"><?php echo __('Powerful virtual servers'); ?></span>
                                    </div>
                                </a>
                                <a href="/domains" class="dropdown-link">
                                    <div class="icon">🌐</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Domains'); ?></span>
                                        <span class="desc"><?php echo __('Register your brand'); ?></span>
                                    </div>
                                </a>
                                <a href="/ssl" class="dropdown-link">
                                    <div class="icon">🔒</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('SSL Certificates'); ?></span>
                                        <span class="desc"><?php echo __('Security for website'); ?></span>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <div class="dropdown-group-title"><?php echo __('Digital Assets'); ?></div>
                                <a href="/code/themes" class="dropdown-link">
                                    <div class="icon">🎨</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Premium Themes'); ?></span>
                                        <span class="desc"><?php echo __('Beautiful web designs'); ?></span>
                                    </div>
                                </a>
                                <a href="/code/plugins" class="dropdown-link">
                                    <div class="icon">🔌</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Powerful Plugins'); ?></span>
                                        <span class="desc"><?php echo __('Extend site features'); ?></span>
                                    </div>
                                </a>
                                <a href="<?php echo route_url('web-templates'); ?>" class="dropdown-link">
                                    <div class="icon">🍱</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Website Templates'); ?></span>
                                        <span class="desc"><?php echo __('Pre-made professional sites'); ?></span>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <div class="dropdown-group-title"><?php echo __('Software & Tools'); ?></div>
                                <a href="/software" class="dropdown-link">
                                    <div class="icon">💻</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Utility Software'); ?></span>
                                        <span class="desc"><?php echo __('Useful desktop apps'); ?></span>
                                    </div>
                                </a>
                                <a href="/tools" class="dropdown-link">
                                    <div class="icon">💰</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('MMO Tools'); ?></span>
                                        <span class="desc"><?php echo __('Make money online'); ?></span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item-has-dropdown">
                        <a href="#"><?php echo __('Pro Services'); ?> <span class="nav-arrow">▼</span></a>
                        <div class="nav-dropdown wide-2col">
                            <div>
                                <div class="dropdown-group-title"><?php echo __('Speed & Performance'); ?></div>
                                <a href="/services/speed" class="dropdown-link">
                                    <div class="icon">🚀</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Speed Optimization'); ?></span>
                                        <span class="desc"><?php echo __('Lightning fast loading'); ?></span>
                                    </div>
                                </a>
                                <a href="/services/pagespeed" class="dropdown-link">
                                    <div class="icon">📈</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Google PageSpeed'); ?></span>
                                        <span class="desc"><?php echo __('Score 90+ on mobile'); ?></span>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <div class="dropdown-group-title"><?php echo __('Cyber Security'); ?></div>
                                <a href="/services/security" class="dropdown-link">
                                    <div class="icon">🛡️</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Web Security'); ?></span>
                                        <span class="desc"><?php echo __('Full site protection'); ?></span>
                                    </div>
                                </a>
                                <a href="/services/malware" class="dropdown-link">
                                    <div class="icon">🦠</div>
                                    <div class="text">
                                        <span class="title"><?php echo __('Malware Removal'); ?></span>
                                        <span class="desc"><?php echo __('WordPress & PHP cleanup'); ?></span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item-has-dropdown">
                        <a href="#"><?php echo __('Partner'); ?> <span class="nav-arrow">▼</span></a>
                        <div class="nav-dropdown single-col">
                            <a href="/memberships" class="dropdown-link">
                                <div class="icon">💎</div>
                                <div class="text">
                                    <span class="title"><?php echo __('VIP Membership'); ?></span>
                                    <span class="desc"><?php echo __('Exclusive privileges'); ?></span>
                                </div>
                            </a>
                            <a href="/affiliates" class="dropdown-link">
                                <div class="icon">🤝</div>
                                <div class="text">
                                    <span class="title"><?php echo __('Affiliate Program'); ?></span>
                                    <span class="desc"><?php echo __('Earn high commission'); ?></span>
                                </div>
                            </a>
                        </div>
                    </li>
                    <li><a href="<?php echo route_url('web-templates'); ?>"><?php echo __('Website Templates'); ?></a></li>
                    <li><a href="/blog"><?php echo __('Blog'); ?></a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php include __DIR__ . '/lang-switcher.php'; ?>
                
                <?php if (is_authenticated()): 
                    $user = auth_user();
                    $initials = strtoupper(substr($user['name'] ?? $user['email'] ?? 'U', 0, 2));
                ?>
                    <div class="user-profile-nav">
                        <div class="user-avatar-circle">
                            <?php echo $initials; ?>
                        </div>
                        <span class="nav-arrow" style="margin-left: -5px; color: var(--gray);">▼</span>
                        
                        <div class="user-dropdown">
                            <div class="user-dropdown-header">
                                <span class="name"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></span>
                                <span class="email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                            </div>
                            <a href="/portal" class="user-dropdown-link">
                                <span>🏠</span> <?php echo __('Bảng điều khiển'); ?>
                            </a>
                            <a href="/portal/services" class="user-dropdown-link">
                                <span>📦</span> <?php echo __('Dịch vụ của tôi'); ?>
                            </a>
                            <a href="/portal/profile" class="user-dropdown-link">
                                <span>⚙️</span> <?php echo __('Cài đặt'); ?>
                            </a>
                            <div style="height: 1px; background: #f1f5f9; margin: 8px 0;"></div>
                            <a href="/logout" class="user-dropdown-link logout">
                                <span>🚪</span> <?php echo __('Đăng xuất'); ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login" class="btn-login"><?php echo __('Sign In'); ?></a>
                    <a href="/register" class="btn-primary"><?php echo __('Get Started'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>
