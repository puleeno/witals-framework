<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?> - PrestoWorld Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.5);
            --accent: #f472b6;
            --bg: #030712;
            --card-bg: rgba(17, 24, 39, 0.7);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(244, 114, 182, 0.15) 0%, transparent 50%);
            color: #f1f5f9;
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .header-glow {
            position: absolute;
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            width: 1000px;
            height: 400px;
            background: var(--primary);
            filter: blur(150px);
            opacity: 0.1;
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            padding: 100px 20px;
        }

        .back-nav {
            margin-bottom: 40px;
        }

        .back-nav a {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }

        .back-nav a:hover {
            color: var(--primary);
        }

        .article-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 60px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .category-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        h1 {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
            line-height: 1.1;
            background: linear-gradient(to bottom right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .post-content {
            font-size: 1.25rem;
            color: #cbd5e1;
        }

        .post-content p {
            margin-bottom: 1.8rem;
        }

        .post-content b, .post-content strong {
            color: #fff;
        }

        .footer-action {
            margin-top: 60px;
            padding-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
        }

        .btn-glow {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 16px 32px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .btn-glow:hover {
            transform: translateY(-4px);
            box-shadow: 0 0 30px var(--primary-glow);
            filter: brightness(1.1);
        }

        /* Micro-animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .floating-icon {
            animation: float 4s ease-in-out infinite;
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="header-glow"></div>
    
    <div class="container">
        <nav class="back-nav">
            <a href="<?php echo home_url('/'); ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.8333 10H4.16667M4.16667 10L10 15.8333M4.16667 10L10 4.16667" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back to Pulse
            </a>
        </nav>

        <article class="article-card">
            <span class="floating-icon">🚀</span>
            <span class="category-badge">Standard Post</span>
            <h1><?php the_title(); ?></h1>
            
            <div class="post-meta">
                <span>By Admin</span>
                <span>•</span>
                <span>Jan 30, 2026</span>
                <span>•</span>
                <span>ID: <?php echo get_the_ID(); ?></span>
            </div>

            <div class="post-content">
                <?php the_content(); ?>
                
                <p><b>PrestoWorld Insight:</b> You are viewing this content melalui <strong>Isolation Sandbox</strong> transformer. Mặc dù đây là nội dung WordPress truyền thống, nó đang được render với hiệu năng cực cao bằng Native Engine và RoadRunner.</p>
            </div>

            <div class="footer-action">
                <a href="<?php echo home_url('/'); ?>" class="btn-glow">Explore PrestoNative</a>
            </div>
        </article>
    </div>
</body>
</html>
