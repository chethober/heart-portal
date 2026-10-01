<?php
// Shared navigation for the clinical pages (ported from the legacy navbar.php).
$navUser = auth()->user();
$routeName = request()->route()?->getName();
$navNationalID = trim((string) request()->query('nationalID', ''));
$navStudy = request()->route('study');
$navReportID = $navStudy ? (is_object($navStudy) ? $navStudy->getKey() : $navStudy) : '';

$isFormPage = $routeName === 'studies.create';
$isEchosPage = $routeName === 'patients.index';
$isReportPage = $routeName === 'studies.show';

$canCreate = $navUser->can('studies.create');
$formUrl = route('studies.create');
$echosUrl = route('patients.index', $navNationalID !== '' ? ['nationalID' => $navNationalID] : []);
$reportUrl = $navReportID !== '' ? route('studies.show', $navReportID) : '#';
$helpUrl = route('help');
?>

<!-- Persistent Navigation Bar -->
<header class="app-navbar no-print">
    <div class="navbar-brand-wrapper">
        <a href="<?= e(route('dashboard')) ?>" class="navbar-brand">
            <span class="brand-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </span>
            <span class="brand-text"><?= e(__(':hospital Echo Portal', ['hospital' => $navUser->hospital->name])) ?></span>
        </a>
    </div>

    <!-- Desktop Navigation -->
    <nav class="navbar-desktop-nav" aria-label="<?= e(__('Desktop Navigation')) ?>">
        <ul class="navbar-menu">
            <?php if ($canCreate) { ?>
            <li>
                <a href="<?= e($formUrl) ?>" class="navbar-item <?= $isFormPage ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        <path d="M9 14l2 2 4-4"></path>
                    </svg>
                    <span><?= e(__('Echo Form')) ?></span>
                </a>
            </li>
            <?php } ?>
            <li>
                <a href="<?= e($echosUrl) ?>" class="navbar-item <?= $isEchosPage ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span><?= e(__('Patient Echos')) ?></span>
                </a>
            </li>
            <li>
                <?php if (! empty($navReportID)) { ?>
                    <a href="<?= e($reportUrl) ?>" class="navbar-item <?= $isReportPage ? 'active' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <line x1="10" y1="9" x2="8" y2="9"></line>
                        </svg>
                        <span><?= e(__('Detailed Report')) ?></span>
                    </a>
                <?php } else { ?>
                    <a href="#" class="navbar-item disabled" title="<?= e(__('Search a patient and view a report first')) ?>" tabindex="-1" aria-disabled="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <line x1="10" y1="9" x2="8" y2="9"></line>
                        </svg>
                        <span><?= e(__('Detailed Report')) ?></span>
                    </a>
                <?php } ?>
            </li>
            <li>
                <a href="<?= e($helpUrl) ?>" class="navbar-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span><?= e(__('Help')) ?></span>
                </a>
            </li>
            <li>
                <a href="<?= e(route('dashboard')) ?>" class="navbar-item" title="<?= e($navUser->name) ?>">
                    <span><?= e($navUser->name) ?></span>
                </a>
            </li>
            <li>
                <form method="POST" action="<?= e(route('logout')) ?>" style="margin:0">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="navbar-item" style="background:none;border:0;cursor:pointer;font:inherit"><?= e(__('Log out')) ?></button>
                </form>
            </li>
        </ul>

        <!-- Inline quick search -->
        <form action="<?= e(route('patients.index')) ?>" method="get" class="navbar-search-inline" role="search">
            <input type="text" name="nationalID" class="no-voice" placeholder="<?= e(__('Quick Search ID...')) ?>" value="<?= e($navNationalID) ?>" required aria-label="<?= e(__('National ID Quick Search')) ?>">
            <button type="submit"><?= e(__('Search')) ?></button>
        </form>
    </nav>

    <!-- Hamburger Toggle Button for Mobile / Tablet -->
    <button type="button" class="navbar-toggle" id="navbarToggle" aria-label="<?= e(__('Toggle navigation menu')) ?>" aria-expanded="false" aria-controls="navbarDrawer">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
    </button>
</header>

<!-- Mobile Navigation Drawer & Backdrop -->
<div class="navbar-backdrop no-print" id="navbarBackdrop"></div>
<aside class="navbar-drawer no-print" id="navbarDrawer" aria-label="<?= e(__('Mobile Navigation')) ?>" aria-hidden="true">
    <div class="drawer-header">
        <div class="drawer-brand">
            <span class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </span>
            <span class="drawer-title"><?= e(__('Navigation')) ?></span>
        </div>
        <button type="button" class="drawer-close-btn" id="navbarDrawerClose" aria-label="<?= e(__('Close navigation menu')) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <!-- Mobile Quick Search Form -->
    <form action="<?= e(route('patients.index')) ?>" method="get" class="drawer-search-form" role="search">
        <div class="drawer-search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#718096" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="drawer-search-icon">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="nationalID" placeholder="<?= e(__('Search National ID...')) ?>" value="<?= e($navNationalID) ?>" required aria-label="<?= e(__('Search National ID')) ?>">
            <button type="submit" aria-label="<?= e(__('Submit search')) ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
    </form>

    <ul class="drawer-menu">
        <?php if ($canCreate) { ?>
        <li>
            <a href="<?= e($formUrl) ?>" class="drawer-item <?= $isFormPage ? 'active' : '' ?>">
                <span class="item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        <path d="M9 14l2 2 4-4"></path>
                    </svg>
                </span>
                <span class="item-label"><?= e(__('Echo Form')) ?></span>
            </a>
        </li>
        <?php } ?>
        <li>
            <a href="<?= e($echosUrl) ?>" class="drawer-item <?= $isEchosPage ? 'active' : '' ?>">
                <span class="item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <span class="item-label"><?= e(__('Patient Echos')) ?></span>
            </a>
        </li>
        <li>
            <?php if (! empty($navReportID)) { ?>
                <a href="<?= e($reportUrl) ?>" class="drawer-item <?= $isReportPage ? 'active' : '' ?>">
                    <span class="item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <line x1="10" y1="9" x2="8" y2="9"></line>
                        </svg>
                    </span>
                    <span class="item-label"><?= e(__('Detailed Report')) ?></span>
                </a>
            <?php } else { ?>
                <a href="#" class="drawer-item disabled" title="<?= e(__('Search a patient and view a report first')) ?>" tabindex="-1" aria-disabled="true">
                    <span class="item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <line x1="10" y1="9" x2="8" y2="9"></line>
                        </svg>
                    </span>
                    <span class="item-label"><?= e(__('Detailed Report')) ?></span>
                </a>
            <?php } ?>
        </li>
        <li>
            <a href="<?= e($helpUrl) ?>" class="drawer-item">
                <span class="item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
                <span class="item-label"><?= e(__('Help Center')) ?></span>
            </a>
        </li>
        <li>
            <a href="<?= e(route('dashboard')) ?>" class="drawer-item">
                <span class="item-label"><?= e($navUser->name) ?> — <?= e(__('Dashboard')) ?></span>
            </a>
        </li>
        <li>
            <form method="POST" action="<?= e(route('logout')) ?>" style="margin:0">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="drawer-item" style="background:none;border:0;width:100%;text-align:left;cursor:pointer;font:inherit">
                    <span class="item-label"><?= e(__('Log out')) ?></span>
                </button>
            </form>
        </li>
    </ul>
</aside>

<!-- Accessible Navbar Interaction Script -->
<script>
(function() {
    function initNavbar() {
        var toggle = document.getElementById('navbarToggle');
        var drawer = document.getElementById('navbarDrawer');
        var backdrop = document.getElementById('navbarBackdrop');
        var closeBtn = document.getElementById('navbarDrawerClose');
        
        if (!toggle || !drawer || !backdrop) return;
        
        function openDrawer() {
            drawer.classList.add('open');
            backdrop.classList.add('open');
            toggle.classList.add('open');
            toggle.setAttribute('aria-expanded', 'true');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.classList.add('navbar-drawer-locked');
        }
        
        function closeDrawer() {
            drawer.classList.remove('open');
            backdrop.classList.remove('open');
            toggle.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('navbar-drawer-locked');
        }
        
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (drawer.classList.contains('open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });
        
        backdrop.addEventListener('click', closeDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && drawer.classList.contains('open')) {
                closeDrawer();
            }
        });

        // Auto close on window resize to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 860 && drawer.classList.contains('open')) {
                closeDrawer();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavbar);
    } else {
        initNavbar();
    }
})();
</script>

<link type="text/css" rel="stylesheet" href="/legacy/voice-input.css">
<script type="text/javascript" src="/legacy/voice-input.js" defer></script>
