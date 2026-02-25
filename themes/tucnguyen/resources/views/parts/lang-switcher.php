<?php

$translator = app()->translator();
$currentLocale = $translator->getLocale();
$locales = [
    'en' => ['name' => 'English', 'flag' => '🇺🇸'],
    'ja' => ['name' => '日本語', 'flag' => '🇯🇵'],
    'ko' => ['name' => '한국어', 'flag' => '🇰🇷'],
    'fr' => ['name' => 'Français', 'flag' => '🇫🇷'],
    'vi' => ['name' => 'Tiếng Việt', 'flag' => '🇻🇳']
];

?>

<div class="lang-switcher">
    <button class="lang-btn" id="langBtn">
        <span class="flag"><?php echo $locales[$currentLocale]['flag'] ?? ''; ?></span>
        <span class="lang-name"><?php echo $locales[$currentLocale]['name'] ?? $currentLocale; ?></span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M2 4L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>
    <div class="lang-dropdown" id="langDropdown">
        <?php foreach ($locales as $code => $info): ?>
            <a href="<?php echo current_url_with_lang($code); ?>" class="lang-item <?php echo $currentLocale === $code ? 'active' : ''; ?>">
                <span class="flag"><?php echo $info['flag']; ?></span>
                <?php echo $info['name']; ?>
            </a>
        <?php endforeach; ?>
    </div>

</div>

<style>
    .lang-switcher {
        position: relative;
        display: inline-block;
    }
    .lang-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(241, 245, 249, 0.5);
        border: 1px solid #e2e8f0;
        padding: 6px 12px;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
        transition: 0.2s;
    }
    .lang-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .lang-name {
        margin-right: 4px;
    }
    .lang-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border: 1px solid #f1f5f9;
        min-width: 160px;
        display: none;
        z-index: 10002;
        overflow: hidden;
        animation: slideDown 0.2s ease-out;
    }
    .lang-dropdown.show {
        display: block;
    }
    .lang-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        text-decoration: none;
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
        transition: 0.2s;
    }
    .lang-item:hover {
        background: #f8fafc;
        color: #3b82f6;
    }
    .lang-item.active {
        background: #eff6ff;
        color: #3b82f6;
        font-weight: 700;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('langBtn');
        const dropdown = document.getElementById('langDropdown');
        
        if (btn && dropdown) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });
            
            document.addEventListener('click', function() {
                dropdown.classList.remove('show');
            });
        }
    });
</script>
