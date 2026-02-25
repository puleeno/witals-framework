<?php include __DIR__ . '/parts/header.php'; ?>

<main class="container" style="padding: 100px 0;">
    <article style="max-width: 800px; margin: 0 auto; background: white; padding: 60px; border-radius: 32px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
        <h1 style="font-size: 48px; font-weight: 800; margin-bottom: 40px;" class="gradient-text"><?php echo $page['title'] ?? 'Sample Page'; ?></h1>
        
        <div class="entry-content" style="font-size: 18px; line-height: 1.8; color: #334155;">
            <?php echo isset($page['content']) ? nl2br($page['content']) : 'Nội dung đang được cập nhật...'; ?>
        </div>
    </article>
</main>

<?php include __DIR__ . '/parts/footer.php'; ?>
