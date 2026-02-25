<?php include __DIR__ . '/parts/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero container">
            <div class="hero-content">
                <div class="hero-badge">🚀 #1 Trusted Digital Marketplace</div>
                <h1>Empower your <span class="gradient-text">digital ecosystem</span> with premium assets</h1>
                <p>Unlock thousands of enterprise-grade themes, plugins, and software designed to streamline your development and scale your business faster.</p>
                <div class="hero-btns">
                    <a href="/marketplace" class="btn-orange">Explore Marketplace</a>
                    <a href="/register" class="btn-outline">Start Free Trial</a>
                </div>
                <div style="margin-top: 30px; display: flex; align-items: center; gap: 10px;">
                    <div class="avatars" style="display: flex;">
                        <img src="https://i.pravatar.cc/40?u=1" style="border-radius: 50%; border: 2px solid white; margin-right: -10px;">
                        <img src="https://i.pravatar.cc/40?u=2" style="border-radius: 50%; border: 2px solid white; margin-right: -10px;">
                        <img src="https://i.pravatar.cc/40?u=3" style="border-radius: 50%; border: 2px solid white;">
                    </div>
                    <div style="font-size: 14px; font-weight: 600; color: var(--boltz-text-dark);">Trusted by 10,000+ developers worldwide</div>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&q=80&w=800" alt="Professional Tech Development">
            </div>
        </section>

        <!-- Domain Section -->
        <section class="domain-section">
            <div class="container">
                <h2>Secure Your Competitive Edge with the Perfect Domain</h2>
                <div class="domain-search-bar">
                    <input type="text" placeholder="Search your ideal domain name...">
                    <button>Check Availability</button>
                </div>
                <div class="domain-tips">
                    <span>.com <b>$9.99</b></span>
                    <span>.net <b>$12.50</b></span>
                    <span>.org <b>$8.00</b></span>
                    <span>.io <b>$35.00</b></span>
                </div>
            </div>
        </section>

        <!-- Ecosystem -->
        <section class="ecosystem container">
            <div class="section-header">
                <h2>The DigitalCore Solutions Suite</h2>
                <a href="/catalog">Explore All Categories →</a>
            </div>
            <div class="ecosystem-grid">
                <div class="eco-card">
                    <div class="eco-icon blue">🎨</div>
                    <h3>Premium Themes</h3>
                    <p>Curated, SEO-optimized layouts designed for conversion and high-speed performance.</p>
                    <a href="/themes" class="read-more">Browse Collection →</a>
                </div>
                <div class="eco-card">
                    <div class="eco-icon purple">🔌</div>
                    <h3>Scalable Plugins</h3>
                    <p>Extend your platform functionality with robust, developer-vetted plugins and tools.</p>
                    <a href="/plugins" class="read-more">Explore Add-ons →</a>
                </div>
                <div class="eco-card">
                    <div class="eco-icon orange">💻</div>
                    <h3>Business Software</h3>
                    <p>Enterprise-grade licenses for design, development, and operational efficiency.</p>
                    <a href="/software" class="read-more">View Software →</a>
                </div>
                <div class="eco-card">
                    <div class="eco-icon cyan">👑</div>
                    <h3>VIP Memberships</h3>
                    <p>Infinite access to our entire resource library for a single, predictable periodic fee.</p>
                    <a href="/membership" class="read-more">See Plans →</a>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="featured">
            <div class="container">
                <h2>Top-Rated Digital Assets</h2>
                <div class="products-grid">
                    <?php 
                    $display_services = !empty($web_services) ? $web_services : [
                        ['name' => 'Tokoo Theme', 'category' => 'Theme', 'base_price' => 59.00, 'img' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&q=80&w=300'],
                        ['name' => 'Elementor Pro', 'category' => 'Plugin', 'base_price' => 9.00, 'img' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=300'],
                        ['name' => 'Adobe Creative Cloud', 'category' => 'Software', 'base_price' => 15.00, 'img' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?auto=format&fit=crop&q=80&w=300'],
                        ['name' => 'Rank Math SEO Pro', 'category' => 'Plugin', 'base_price' => 5.00, 'img' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=300']
                    ];
                    foreach($display_services as $s): 
                        $img = $s['img'] ?? 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=300';
                    ?>
                    <div class="product-card">
                        <div class="product-thumb">
                            <img src="<?php echo $img; ?>" alt="">
                        </div>
                        <div class="product-info">
                            <span class="product-tag"><?php echo ucfirst($s['category']); ?></span>
                            <h3><?php echo $s['name']; ?></h3>
                            <div class="product-footer">
                                <span class="product-price">$<?php echo number_format((float)$s['base_price'], 2); ?></span>
                                <button style="background: #f1f5f9; border: none; padding: 4px; border-radius: 4px; cursor: pointer;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="pricing container">
            <div class="pricing-header">
                <p style="color: var(--secondary); font-weight: 700; margin-bottom: 10px;">PLAN YOUR GROWTH</p>
                <h2 style="font-size: 36px; font-weight: 800;">Enterprise Performance. Accessible Pricing.</h2>
            </div>
            <div class="pricing-grid">
                <div class="price-card">
                    <div class="price-name">Essentials</div>
                    <div class="price-amt">$3.99 <span>/ month</span></div>
                    <ul class="price-features">
                        <li>✅ 01 Active Project</li>
                        <li>✅ 10GB Secure Hosting</li>
                        <li>✅ Unlimited Data Transfer</li>
                        <li>✅ Daily Backups</li>
                    </ul>
                    <a href="/checkout/starter" class="btn-price">Start Free Trial</a>
                </div>
                <div class="price-card featured-plan">
                    <div class="price-name">Professional</div>
                    <div class="price-amt">$9.99 <span>/ month</span></div>
                    <ul class="price-features">
                        <li>✅ 10 Active Projects</li>
                        <li>✅ 50GB NVMe Storage</li>
                        <li>✅ Priority Technical Support</li>
                        <li>✅ White-label Service</li>
                    </ul>
                    <a href="/checkout/pro" class="btn-price">Get Started Now</a>
                </div>
                <div class="price-card">
                    <div class="price-name">Enterprise</div>
                    <div class="price-amt">$24.99 <span>/ month</span></div>
                    <ul class="price-features">
                        <li>✅ Unlimited Active Projects</li>
                        <li>✅ 200GB Storage & CDN</li>
                        <li>✅ Dedicated Account Manager</li>
                        <li>✅ 99.9% Uptime Guarantee</li>
                    </ul>
                    <a href="/contact-sales" class="btn-price">Contact Sales</a>
                </div>
            </div>
        </section>

        <!-- CTA Banner -->
        <section class="cta-banner container">
            <div class="cta-inner">
                <div class="cta-content">
                    <p style="text-transform: uppercase; font-weight: 700; margin-bottom: 20px;">EXCLUSIVE OPPORTUNITY</p>
                    <h2>Unlock Unlimited Potential with VIP Access.</h2>
                    <p>For just <b>$29.99/mo</b>, gain complete access to 5,000+ premium resources with weekly updates and direct license management.</p>
                    <a href="/membership/vip" class="btn-white">Join the VIP Circle</a>
                </div>
                <div class="cta-vignette"></div>
            </div>
        </section>

        <!-- Highlights -->
        <section class="highlights container">
            <div class="highlights-grid">
                <div class="highlight-item">
                    <div class="highlight-icon">⚡</div>
                    <b>High-Performance</b>
                    <p>Optimized for lightning-fast speeds</p>
                </div>
                <div class="highlight-item">
                    <div class="highlight-icon">🛡️</div>
                    <b>Secure & Compliant</b>
                    <p>AES-256 bank-grade encryption</p>
                </div>
                <div class="highlight-item">
                    <div class="highlight-icon">💬</div>
                    <b>24/7 Expert Support</b>
                    <p>Human-led assistance, any time</p>
                </div>
                <div class="highlight-item">
                    <div class="highlight-icon">🔄</div>
                    <b>Lifetime Updates</b>
                    <p>Always sync with current tech</p>
                </div>
            </div>
        </section>
    </main>

<?php include __DIR__ . '/parts/footer.php'; ?>
