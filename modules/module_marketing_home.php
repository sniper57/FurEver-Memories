<?php
$marketingTitle = 'FurEver Memories';
$marketingTagline = 'Forever in our hearts';
$marketingLogo = rtrim(BASE_URL, '/') . '/assets/images/logo-furever-memories.png';
?>
<header class="marketing-header">
    <nav class="navbar navbar-expand-lg marketing-navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 marketing-brand" href="<?= e(rtrim(BASE_URL, '/') . '/index.php') ?>">
                <img src="<?= e($marketingLogo) ?>" alt="FurEver Memories logo" class="marketing-brand-logo">
                <span><?= e($marketingTitle) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#marketingNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="marketingNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#products">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="#why-furever">Why FurEver</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                    <li class="nav-item"><button type="button" class="btn btn-outline-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#marketingLoginModal">Sign In</button></li>
                    <li class="nav-item"><a class="btn btn-dark rounded-pill px-3" href="login.php">Create a Memorial</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="marketing-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="marketing-hero-copy">
                        <img src="<?= e($marketingLogo) ?>" alt="FurEver Memories logo" class="marketing-hero-logo mb-4">
                        <span class="marketing-kicker">Digital + Physical Pet Memorials</span>
                        <h1 class="marketing-hero-title">Celebrate a life well loved with a memorial made for modern pet families.</h1>
                        <p class="marketing-hero-text"><?= e($marketingTitle) ?> helps pet parents preserve photos, videos, stories, tribute playlists, QR memory galleries, and printed keepsakes in one warm and beautiful experience.</p>
                        <div class="marketing-hero-actions">
                            <a href="login.php" class="btn btn-dark btn-lg rounded-pill px-4">Create a Memorial Page</a>
                            <a href="#how-it-works" class="btn btn-outline-dark btn-lg rounded-pill px-4">See How It Works</a>
                        </div>
                        <div class="marketing-pill-row">
                            <span class="marketing-pill">Warm, not funeral-like</span>
                            <span class="marketing-pill">QR memory galleries</span>
                            <span class="marketing-pill">Printed keepsakes</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="marketing-hero-card">
                        <div class="marketing-hero-card-top">
                            <span class="marketing-kicker">Brand Promise</span>
                            <h2><?= e($marketingTagline) ?></h2>
                            <p>Apple meets Hallmark, but for pet memories. Premium, peaceful, and deeply personal.</p>
                        </div>
                        <div class="marketing-hero-stat-grid">
                            <div class="marketing-stat-card">
                                <strong>Digital pages</strong>
                                <span>Photos, videos, stories, timelines, and message walls</span>
                            </div>
                            <div class="marketing-stat-card">
                                <strong>QR sharing</strong>
                                <span>Let family and friends scan and revisit memories instantly</span>
                            </div>
                            <div class="marketing-stat-card">
                                <strong>Printed keepsakes</strong>
                                <span>Frames, video books, and remembrance gifts that last</span>
                            </div>
                            <div class="marketing-stat-card">
                                <strong>Tribute content</strong>
                                <span>Slideshows and videos designed for healing and celebration</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</header>

<main class="marketing-main">
    <section class="marketing-section" id="how-it-works">
        <div class="container">
            <div class="marketing-section-heading text-center">
                <span class="marketing-kicker">How It Works</span>
                <h2>Honor your pet in 3 simple steps</h2>
                <p>Create a memorial that feels peaceful, premium, and easy to share with the people who loved them too.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <article class="marketing-step-card">
                        <span class="marketing-step-number">01</span>
                        <h3>Tell their story</h3>
                        <p>Add your pet's name, key dates, favorite photos, tribute videos, and the moments that made them unforgettable.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="marketing-step-card">
                        <span class="marketing-step-number">02</span>
                        <h3>Choose a beautiful memorial style</h3>
                        <p>Build a digital page with curated visuals, soft music, QR access, and a layout designed to celebrate life with warmth.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="marketing-step-card">
                        <span class="marketing-step-number">03</span>
                        <h3>Share with family and friends</h3>
                        <p>Invite loved ones to light candles, send hearts, leave memories, and revisit the page anytime through a private link or QR code.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="marketing-section marketing-section-soft" id="why-furever">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5">
                    <span class="marketing-kicker">Why FurEver Memories</span>
                    <h2 class="marketing-side-heading">Built for remembrance that feels loving, peaceful, and celebratory.</h2>
                    <p class="marketing-side-copy">We are not a sad funeral brand. We help families preserve joy, personality, and shared memories in a way that feels modern enough to share online and meaningful enough to keep for years.</p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="marketing-value-card">
                                <h3>Pets are family</h3>
                                <p>Your memorial should reflect the love, rituals, and everyday moments that made your bond special.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="marketing-value-card">
                                <h3>Grief needs a beautiful space</h3>
                                <p>Photos, stories, music, and videos help people remember with tenderness instead of silence.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="marketing-value-card">
                                <h3>Technology can feel human</h3>
                                <p>QR galleries, digital pages, and tribute content make memories easier to share across families and generations.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="marketing-value-card">
                                <h3>Meaningful keepsakes matter</h3>
                                <p>Printed memorial products turn digital memories into display-worthy, giftable pieces for the home.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="marketing-section" id="products">
        <div class="container">
            <div class="marketing-section-heading text-center">
                <span class="marketing-kicker">Signature Offers</span>
                <h2>Everything needed to preserve a pet's story in one brand experience</h2>
                <p>Digital storytelling at the center, supported by thoughtful physical keepsakes.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                    <article class="marketing-offer-card">
                        <h3>Memorial Pages</h3>
                        <p>Mini memorial websites with stories, galleries, tribute timelines, music, guest messages, and reactions.</p>
                    </article>
                </div>
                <div class="col-md-6 col-xl-3">
                    <article class="marketing-offer-card">
                        <h3>QR Memory Galleries</h3>
                        <p>Scan-to-view remembrance experiences for frames, cards, altars, urn displays, and memorial tables.</p>
                    </article>
                </div>
                <div class="col-md-6 col-xl-3">
                    <article class="marketing-offer-card">
                        <h3>Printed Keepsakes</h3>
                        <p>Elegant frames, video books, cards, and boxed mementos designed for modern Filipino homes.</p>
                    </article>
                </div>
                <div class="col-md-6 col-xl-3">
                    <article class="marketing-offer-card">
                        <h3>Tribute Videos</h3>
                        <p>Slideshows and cinematic remembrance edits ready for anniversaries, birthdays, and memorial gatherings.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="marketing-section marketing-section-dark">
        <div class="container">
            <div class="marketing-section-heading text-center">
                <span class="marketing-kicker">Package Ideas</span>
                <h2>Premium, accessible bundles for every kind of remembrance</h2>
                <p>Designed to help FurEver Memories become the #1 modern pet memorial brand in the Philippines.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="marketing-package-card">
                        <span class="marketing-package-tier">Classic Pawprint</span>
                        <h3>For first keepsakes</h3>
                        <ul>
                            <li>Digital memorial page</li>
                            <li>Photo gallery and tribute story</li>
                            <li>Shareable QR link</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="marketing-package-card marketing-package-card--featured">
                        <span class="marketing-package-tier">Golden Legacy</span>
                        <h3>For families who want everything in one place</h3>
                        <ul>
                            <li>Premium memorial page with timeline and music</li>
                            <li>QR frame or keepsake insert</li>
                            <li>Tribute video slideshow</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="marketing-package-card">
                        <span class="marketing-package-tier">Forever Home</span>
                        <h3>For heirloom remembrance</h3>
                        <ul>
                            <li>All Golden Legacy inclusions</li>
                            <li>Printed video book or luxury frame</li>
                            <li>Priority design assistance</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="marketing-section">
        <div class="container">
            <div class="marketing-section-heading text-center">
                <span class="marketing-kicker">What Makes Us Different</span>
                <h2>Emotional storytelling powered by technology</h2>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="marketing-diff-card">
                        <h3>More than a photo album</h3>
                        <p>We combine stories, music, reactions, guest messages, videos, and printable keepsakes in one memorial experience.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="marketing-diff-card">
                        <h3>More than traditional memorial products</h3>
                        <p>Instead of static remembrance alone, families get a living digital space they can revisit, update, and share.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="marketing-diff-card">
                        <h3>More than generic video templates</h3>
                        <p>Every page is built around the pet's unique story, with warmth, beauty, and a premium brand experience.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="marketing-section marketing-section-soft" id="faq">
        <div class="container">
            <div class="marketing-section-heading text-center">
                <span class="marketing-kicker">Frequently Asked Questions</span>
                <h2>Everything pet parents usually ask before they begin</h2>
            </div>
            <div class="marketing-faq-list">
                <details class="marketing-faq-item" open>
                    <summary>Can I include photos, videos, stories, and music on one memorial page?</summary>
                    <p>Yes. FurEver Memories is designed for multimedia remembrance, so families can preserve photos, tribute stories, timelines, guest messages, videos, and background music in one place.</p>
                </details>
                <details class="marketing-faq-item">
                    <summary>Can family and friends add messages and reactions?</summary>
                    <p>Yes. Loved ones can visit the memorial page, light a candle, send hearts, and leave messages that the page owner can review and manage.</p>
                </details>
                <details class="marketing-faq-item">
                    <summary>What makes FurEver Memories feel different from a sad memorial site?</summary>
                    <p>Our visual language is warm, loving, peaceful, and celebratory. We focus on honoring life and preserving joy, not creating a cold funeral atmosphere.</p>
                </details>
                <details class="marketing-faq-item">
                    <summary>Can this also connect to printed products and QR codes?</summary>
                    <p>Yes. The brand is built for both digital and physical remembrance, from QR memory galleries to printed keepsakes and tribute products.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="marketing-section marketing-cta-section">
        <div class="container">
            <div class="marketing-cta-panel">
                <span class="marketing-kicker">FurEver Memories</span>
                <h2>Forever in our hearts</h2>
                <p>Create a memorial that feels modern, deeply personal, and beautiful enough to share with the people who loved them most.</p>
                <div class="marketing-hero-actions justify-content-center">
                    <a href="login.php" class="btn btn-dark btn-lg rounded-pill px-4">Start Your Memorial</a>
                    <a href="#how-it-works" class="btn btn-outline-dark btn-lg rounded-pill px-4">Explore Features</a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="marketing-footer">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <div class="marketing-footer-brand d-flex align-items-center gap-3">
                    <img src="<?= e($marketingLogo) ?>" alt="FurEver Memories logo" class="marketing-footer-logo">
                    <div>
                        <h2><?= e($marketingTitle) ?></h2>
                        <p><?= e($marketingTagline) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="login.php" class="btn btn-dark rounded-pill px-4">Create a Memorial Page</a>
            </div>
        </div>
    </div>
</footer>

<div class="modal fade marketing-login-modal" id="marketingLoginModal" tabindex="-1" aria-labelledby="marketingLoginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-body p-0">
                <button type="button" class="btn-close marketing-login-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="row g-0">
                    <div class="col-lg-5 d-none d-lg-block">
                        <section class="login-brand-panel marketing-login-brand-panel h-100">
                            <a href="<?= e(rtrim(BASE_URL, '/') . '/index.php') ?>" class="login-brand-mark text-decoration-none">
                                <img src="<?= e($marketingLogo) ?>" alt="FurEver Memories logo" class="login-brand-logo">
                                <span><?= e($marketingTitle) ?></span>
                            </a>
                            <span class="marketing-kicker"><?= e($marketingTagline) ?></span>
                            <h2 class="login-hero-title">Sign in and continue building a beautiful place for treasured pet memories.</h2>
                            <p class="login-hero-copy">Access memorial pages, QR sharing, gallery uploads, tribute music, and every detail that keeps your pet's story alive with warmth.</p>
                            <div class="login-brand-pills">
                                <span class="marketing-pill">Memorial builder</span>
                                <span class="marketing-pill">QR sharing</span>
                                <span class="marketing-pill">Guest tributes</span>
                            </div>
                        </section>
                    </div>
                    <div class="col-lg-7">
                        <section class="login-form-panel marketing-login-form-panel">
                            <div class="login-form-wrap">
                                <div class="login-form-copy">
                                    <span class="login-form-kicker">Sign In</span>
                                    <h2 id="marketingLoginModalLabel">Administrator / Client Login</h2>
                                    <p>Open your FurEver Memories dashboard and continue where you left off.</p>
                                </div>
                                <?php if (!empty($marketingLoginWarning ?? '')): ?><div class="alert alert-warning"><?= e($marketingLoginWarning) ?></div><?php endif; ?>
                                <?php if (!empty($marketingLoginError ?? '')): ?><div class="alert alert-danger"><?= e($marketingLoginError) ?></div><?php endif; ?>
                                <form method="post" action="index.php" class="login-form">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="marketing_login">
                                    <div class="mb-3">
                                        <label class="form-label login-form-label">Email address</label>
                                        <input type="email" name="email" class="form-control login-form-control" value="<?= e($marketingLoginEmail ?? '') ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label login-form-label">Password</label>
                                        <input type="password" name="password" class="form-control login-form-control" required>
                                    </div>
                                    <button class="btn login-submit-btn w-100">Login</button>
                                </form>
                                <div class="login-form-footer">
                                    <a href="login.php" class="text-decoration-none">Open full login page</a>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
