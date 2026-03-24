<?php
session_start();

$_SESSION['cart_page_active'] = false;

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/user/login.php');
    exit;
}

if (isset($_POST['add_to_cart'])) {
    header('Content-Type: application/json');

    $productId = (int) ($_POST['product_id'] ?? 0);

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($productId <= 0) {
        echo json_encode(['status' => 'error']);
        exit;
    }

    if (isset($_SESSION['cart'][$productId])) {
        echo json_encode([
            'status' => 'exists',
            'count' => count($_SESSION['cart']),
        ]);
        exit;
    }

    $_SESSION['cart'][$productId] = 1;

    echo json_encode([
        'status' => 'added',
        'count' => count($_SESSION['cart']),
    ]);
    exit;
}

function short_text(string $text, int $limit = 120): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 3)) . '...';
    }

    if (strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(substr($text, 0, $limit - 3)) . '...';
}

function slugify_category(string $name, int $id): string
{
    $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    return $slug !== '' ? $slug . '-' . $id : 'category-' . $id;
}

function category_theme(int $index): array
{
    $themes = [
        ['primary' => '#0f766e', 'secondary' => '#115e59', 'accent' => '#ccfbf1'],
        ['primary' => '#1d4ed8', 'secondary' => '#1e3a8a', 'accent' => '#dbeafe'],
        ['primary' => '#c2410c', 'secondary' => '#9a3412', 'accent' => '#ffedd5'],
        ['primary' => '#7c3aed', 'secondary' => '#581c87', 'accent' => '#ede9fe'],
        ['primary' => '#be123c', 'secondary' => '#881337', 'accent' => '#ffe4e6'],
        ['primary' => '#15803d', 'secondary' => '#166534', 'accent' => '#dcfce7'],
    ];

    return $themes[$index % count($themes)];
}

function category_description(string $name): string
{
    return 'Slide through the latest ' . $name . ' products, search inside the category, and jump into the full collection whenever you want.';
}

$userId = (int) $_SESSION['user_id'];
$userStmt = $conn->prepare('SELECT name, profile_image FROM users WHERE id = ?');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc() ?: ['name' => 'User', 'profile_image' => 'default.png'];
$userStmt->close();

$categories = [];
$previewCatalog = [];
$categoryResult = $conn->query('SELECT id, name FROM categories ORDER BY name ASC');
$categoryIndex = 0;

if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categoryId = (int) $row['id'];
        $categoryName = trim((string) $row['name']);
        $slug = slugify_category($categoryName, $categoryId);
        $theme = category_theme($categoryIndex);

        $categories[] = [
            'id' => $categoryId,
            'name' => $categoryName,
            'slug' => $slug,
        ];

        $previewCatalog[$slug] = [
            'slug' => $slug,
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'title' => $categoryName . ' Specials',
            'eyebrow' => 'Category Spotlight',
            'description' => category_description($categoryName),
            'primary' => $theme['primary'],
            'secondary' => $theme['secondary'],
            'accent' => $theme['accent'],
            'products' => [],
        ];

        $categoryIndex++;
    }
}

$selectedCategoryId = isset($_GET['category']) ? (int) $_GET['category'] : null;
$defaultPreviewSlug = array_key_first($previewCatalog) ?: '';

foreach ($categories as $category) {
    if ($selectedCategoryId === $category['id']) {
        $defaultPreviewSlug = $category['slug'];
        break;
    }
}

if ($selectedCategoryId > 0) {
    $productStmt = $conn->prepare('SELECT * FROM products WHERE category_id = ? ORDER BY id DESC');
    $productStmt->bind_param('i', $selectedCategoryId);
    $productStmt->execute();
    $products = $productStmt->get_result();
} else {
    $products = $conn->query('SELECT * FROM products ORDER BY id DESC');
}

foreach ($previewCatalog as &$panel) {
    $panelStmt = $conn->prepare(
        'SELECT id, name, description, price, offer_price, image
         FROM products
         WHERE category_id = ?
         ORDER BY id DESC
         LIMIT 12'
    );
    $panelStmt->bind_param('i', $panel['category_id']);
    $panelStmt->execute();
    $panelResult = $panelStmt->get_result();

    while ($product = $panelResult->fetch_assoc()) {
        $panel['products'][] = [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'description' => short_text((string) $product['description'], 100),
            'price' => number_format((float) $product['price'], 2),
            'offer_price' => number_format((float) $product['offer_price'], 2),
            'image' => '../uploads/products/' . rawurlencode((string) $product['image']),
            'link' => 'product_page.php?category=' . $panel['category_id'] . '#product-' . (int) $product['id'],
        ];
    }

    $panelStmt->close();
}
unset($panel);

$cartProductIds = array_map('strval', array_keys($_SESSION['cart'] ?? []));
$initialCartCount = count($cartProductIds);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopSphere | Products</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif}
body{background:radial-gradient(circle at top left,rgba(56,189,248,.18),transparent 28%),radial-gradient(circle at bottom right,rgba(249,115,22,.12),transparent 24%),#0f172a;padding:30px 40px 40px;color:#fff}
header{margin-bottom:40px;border-bottom:1px solid rgba(255,255,255,.1);padding-bottom:25px}
.header-top{display:flex;justify-content:space-between;align-items:center;gap:20px}
.logo{font-size:42px;font-weight:900;text-shadow:0 0 15px #38bdf8,0 0 40px #38bdf8}
.header-right{display:flex;align-items:center;gap:25px}
.cart-wrapper{position:relative}
.cart-btn{padding:10px 18px;border-radius:25px;background:linear-gradient(135deg,#ff8a00,#ff3d00);color:#fff;text-decoration:none;font-size:14px}
.cart-count{position:absolute;top:-6px;right:-8px;background:#22c55e;color:#020617;font-size:11px;font-weight:700;padding:4px 7px;border-radius:50%;opacity:0;transform:scale(0);transition:.35s ease;box-shadow:0 0 20px rgba(34,197,94,.9)}
.cart-count.show{opacity:1;transform:scale(1)}
.profile-box{text-align:center;cursor:pointer}
.profile-box img{width:52px;height:52px;border-radius:50%;border:2px solid #38bdf8;object-fit:cover;transition:.45s}
.profile-box:hover img{transform:scale(1.25);box-shadow:0 0 35px rgba(56,189,248,.9)}
.profile-box span{display:block;font-size:12px;margin-top:6px}
.search-area{margin-top:25px;display:flex;justify-content:center}
.search-area input{width:420px;padding:14px 20px;border-radius:30px;border:none;outline:none;font-size:15px;background:#020617;color:#fff;box-shadow:0 0 25px rgba(56,189,248,.4)}
.category-nav{position:absolute;top:140px;left:30px;z-index:999}
.nav-circle{width:60px;height:60px;border-radius:50%;border:none;background:linear-gradient(135deg,#38bdf8,#2563eb);display:flex;align-items:center;justify-content:center;font-size:26px;color:#fff;cursor:pointer;box-shadow:0 0 30px rgba(56,189,248,.7);transition:transform .4s ease}
.category-nav.open .nav-circle{transform:rotate(180deg)}
.category-flyout{margin-top:16px;display:none;width:min(980px,calc(100vw - 120px));padding:22px;border-radius:28px;background:rgba(2,6,23,.94);border:1px solid rgba(255,255,255,.08);box-shadow:0 30px 60px rgba(0,0,0,.45);backdrop-filter:blur(10px)}
.category-nav.open .category-flyout{display:grid;gap:18px}
.category-rail-shell{display:grid;grid-template-columns:52px 1fr 52px;gap:12px;align-items:center}
.rail-btn,.preview-scroll-btn{width:52px;height:52px;border:none;border-radius:18px;background:rgba(255,255,255,.08);color:#fff;font-size:24px;cursor:pointer;transition:.25s ease}
.rail-btn:hover,.preview-scroll-btn:hover{background:rgba(56,189,248,.18);transform:translateY(-2px)}
.category-rail{display:flex;gap:12px;overflow-x:auto;scroll-behavior:smooth;padding:2px;scrollbar-width:none}
.category-rail::-webkit-scrollbar,.preview-results::-webkit-scrollbar{display:none}
.cat-item{border:none;border-radius:999px;padding:12px 18px;background:rgba(255,255,255,.06);color:#bfdbfe;white-space:nowrap;font-size:14px;font-weight:700;cursor:pointer;transition:.25s ease}
.cat-item:hover,.cat-item.active-preview{background:#38bdf8;color:#020617;transform:translateY(-2px);box-shadow:0 12px 24px rgba(56,189,248,.25)}
.category-preview-wrap{position:relative;min-height:420px}
.category-preview-placeholder,.category-preview-panel{min-height:420px;border-radius:28px;overflow:hidden;box-shadow:0 32px 70px rgba(0,0,0,.45)}
.category-preview-placeholder{display:flex;align-items:center;justify-content:center;padding:32px;background:linear-gradient(135deg,rgba(15,23,42,.98),rgba(30,41,59,.96));border:1px solid rgba(255,255,255,.08)}
.placeholder-copy{max-width:420px;text-align:center}
.placeholder-copy span{display:inline-block;padding:8px 16px;border-radius:999px;background:rgba(56,189,248,.12);color:#93c5fd;margin-bottom:14px;letter-spacing:.08em;text-transform:uppercase;font-size:12px}
.placeholder-copy h3{font-size:34px;margin-bottom:10px}
.placeholder-copy p{color:#cbd5e1;line-height:1.6}
.category-preview-panel{display:none;position:absolute;inset:0;grid-template-columns:minmax(260px,320px) 1fr;background:radial-gradient(circle at top right,rgba(255,255,255,.16),transparent 28%),linear-gradient(135deg,var(--panel-primary),var(--panel-secondary))}
.category-preview-panel.active{display:grid;animation:panelIn .3s ease}
@keyframes panelIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.preview-copy{padding:32px 28px;display:flex;flex-direction:column;gap:16px;color:#fff}
.preview-copy .eyebrow{display:inline-flex;width:max-content;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,.16);letter-spacing:.12em;text-transform:uppercase;font-size:11px}
.preview-copy h3{font-size:40px;line-height:.95}
.preview-copy p{color:rgba(255,255,255,.9);line-height:1.7}
.preview-search{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:999px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18)}
.preview-search input{width:100%;background:transparent;border:none;outline:none;color:#fff;font-size:14px}
.preview-search input::placeholder{color:rgba(255,255,255,.72)}
.preview-status{min-height:20px;font-size:13px;color:var(--panel-accent)}
.preview-status.empty{color:#fff;font-weight:700}
.preview-open-link{margin-top:auto;width:max-content;padding:12px 18px;border-radius:999px;background:#fff;color:#111827;text-decoration:none;font-size:13px;font-weight:700}
.preview-gallery{padding:24px;display:grid;grid-template-rows:auto 1fr;gap:16px;min-width:0}
.preview-toolbar{display:flex;justify-content:flex-end}
.preview-scroll-actions{display:flex;gap:10px}
.preview-results{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(220px,1fr);gap:16px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;padding-bottom:4px}
.preview-product-card{min-height:308px;display:flex;flex-direction:column;gap:14px;padding:16px;border-radius:24px;background:rgba(5,10,24,.72);color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.08);backdrop-filter:blur(10px);scroll-snap-align:start;transition:transform .25s ease,box-shadow .25s ease}
.preview-product-card:hover{transform:translateY(-6px);box-shadow:0 20px 40px rgba(0,0,0,.25)}
.preview-product-image{width:100%;height:150px;border-radius:18px;overflow:hidden}
.preview-product-image img{width:100%;height:100%;object-fit:cover}
.preview-product-content{display:flex;flex-direction:column;gap:10px;flex:1}
.preview-product-content h4{font-size:18px}
.preview-product-content p{font-size:13px;line-height:1.55;color:rgba(255,255,255,.84)}
.preview-price{margin-top:auto;display:flex;align-items:center;gap:10px;font-weight:700}
.preview-price strong{font-size:19px}
.preview-price span{font-size:13px;text-decoration:line-through;color:rgba(255,255,255,.72)}
.preview-empty-card{width:min(320px,100%);align-self:center;padding:28px;border-radius:24px;background:rgba(5,10,24,.72);border:1px dashed rgba(255,255,255,.18);color:#fff;text-align:center}
.product-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:24px}
.product-card{background:#fff;border-radius:16px;height:420px;overflow:hidden;position:relative;transition:.35s;box-shadow:0 12px 30px rgba(0,0,0,.2)}
.product-card:hover{transform:translateY(-10px);box-shadow:0 25px 60px rgba(0,0,0,.35)}
.price{position:absolute;top:12px;right:12px;background:#111;color:#fff;padding:6px 14px;border-radius:20px;font-size:13px}
.product-img{height:180px;overflow:hidden}
.product-img img{width:100%;height:100%;object-fit:cover;transition:.4s}
.product-card:hover img{transform:scale(1.1)}
.card-body{padding:16px;height:calc(100% - 180px);display:flex;flex-direction:column;color:#000}
.desc{font-size:14px;color:#555;max-height:48px;overflow:hidden;transition:max-height .4s ease}
.product-card:hover .desc{max-height:120px;overflow-y:auto;padding-right:4px}
.desc::-webkit-scrollbar{width:4px}
.desc::-webkit-scrollbar-thumb{background:#38bdf8;border-radius:10px}
.card-footer{margin-top:auto;display:flex;justify-content:space-between;align-items:center}
.offer{font-size:18px;font-weight:700;color:#16a34a}
.add-cart-btn{padding:8px 14px;border-radius:20px;border:none;cursor:pointer;background:linear-gradient(135deg,#38bdf8,#2563eb);color:#fff;font-size:13px;transition:.3s}
.add-cart-btn:hover{transform:scale(1.08);box-shadow:0 0 30px rgba(56,189,248,.9)}
.back-btn{display:block;margin-top:10px;padding:10px 16px;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;text-decoration:none;border-radius:12px;font-weight:bold;font-size:13px;text-align:center;width:120px;transition:.3s}
.back-btn:hover{transform:scale(1.05);box-shadow:0 0 20px rgba(239,68,68,.8)}
footer{margin-top:80px;padding-top:30px;text-align:center;border-top:1px solid rgba(255,255,255,.1)}
footer span{color:#38bdf8;font-weight:700;text-shadow:0 0 12px #38bdf8,0 0 30px #38bdf8}
#dupAlert{position:fixed;top:20px;right:20px;background:#dc2626;color:#fff;padding:14px 22px;border-radius:12px;font-weight:700;display:none;box-shadow:0 0 25px #dc2626;z-index:9999}
@media(max-width:1400px){.product-grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:1240px){.category-flyout{width:min(920px,calc(100vw - 60px))}.category-preview-panel{grid-template-columns:1fr}}
@media(max-width:1100px){.product-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){body{padding:24px 20px 30px}.category-nav{position:relative;top:auto;left:auto;margin-bottom:24px}.category-flyout{width:100%;padding:18px}.header-top{flex-direction:column;align-items:flex-start}.header-right{width:100%;justify-content:space-between;flex-wrap:wrap}.search-area input{width:100%}}
@media(max-width:800px){.product-grid{grid-template-columns:repeat(2,1fr)}.category-rail-shell{grid-template-columns:44px 1fr 44px}.rail-btn,.preview-scroll-btn{width:44px;height:44px;border-radius:14px}.preview-results{grid-auto-columns:minmax(210px,85%)}}
@media(max-width:560px){.product-grid{grid-template-columns:1fr}.logo{font-size:34px}.profile-box span{display:none}.preview-copy h3{font-size:32px}}
</style>
</head>
<body>

<div class="category-nav" id="categoryNav">
    <button class="nav-circle" type="button" id="categoryToggle" aria-expanded="false">&#9776;</button>

    <div class="category-flyout">
        <div class="category-rail-shell">
            <button class="rail-btn" type="button" data-scroll-target="categoryRail" data-scroll-direction="-1">&#8249;</button>
            <div class="category-rail" id="categoryRail">
                <?php foreach ($categories as $category): ?>
                    <button class="cat-item<?= $defaultPreviewSlug === $category['slug'] ? ' active-preview' : '' ?>" type="button" data-preview-target="<?= htmlspecialchars($category['slug']) ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <button class="rail-btn" type="button" data-scroll-target="categoryRail" data-scroll-direction="1">&#8250;</button>
        </div>

        <div class="category-preview-wrap">
            <div class="category-preview-placeholder" id="previewPlaceholder">
                <div class="placeholder-copy">
                    <span>Category Preview</span>
                    <h3>Browse Every Category</h3>
                    <p>Open the menu, slide through categories, and preview products from every category, including any new categories added in the future.</p>
                </div>
            </div>

            <?php foreach ($previewCatalog as $slug => $panel): ?>
                <section class="category-preview-panel" data-preview-panel="<?= htmlspecialchars($slug) ?>" style="--panel-primary: <?= htmlspecialchars($panel['primary']) ?>; --panel-secondary: <?= htmlspecialchars($panel['secondary']) ?>; --panel-accent: <?= htmlspecialchars($panel['accent']) ?>;">
                    <div class="preview-copy">
                        <span class="eyebrow"><?= htmlspecialchars($panel['eyebrow']) ?></span>
                        <h3><?= htmlspecialchars($panel['title']) ?></h3>
                        <p><?= htmlspecialchars($panel['description']) ?></p>

                        <label class="preview-search">
                            <span>Search</span>
                            <input type="search" data-preview-search="<?= htmlspecialchars($slug) ?>" placeholder="Search in <?= htmlspecialchars($panel['category_name']) ?>" autocomplete="off">
                        </label>

                        <div class="preview-status" data-preview-status="<?= htmlspecialchars($slug) ?>"></div>

                        <a class="preview-open-link" href="?category=<?= (int) $panel['category_id'] ?>">Open <?= htmlspecialchars($panel['category_name']) ?></a>
                    </div>

                    <div class="preview-gallery">
                        <div class="preview-toolbar">
                            <div class="preview-scroll-actions">
                                <button class="preview-scroll-btn" type="button" data-scroll-target="preview-<?= htmlspecialchars($slug) ?>" data-scroll-direction="-1">&#8249;</button>
                                <button class="preview-scroll-btn" type="button" data-scroll-target="preview-<?= htmlspecialchars($slug) ?>" data-scroll-direction="1">&#8250;</button>
                            </div>
                        </div>

                        <div class="preview-results" id="preview-<?= htmlspecialchars($slug) ?>" data-preview-results="<?= htmlspecialchars($slug) ?>"></div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<header>
    <div class="header-top">
        <div class="logo">ShopSphere</div>

        <div class="header-right">
            <div class="cart-wrapper">
                <a href="../cart/view.php" class="cart-btn">Cart</a>
                <div class="cart-count<?= $initialCartCount > 0 ? ' show' : '' ?>" id="cartCount"><?= $initialCartCount ?></div>
            </div>

            <div class="profile-box" onclick="location.href='../profile/profile.php'">
                <img src="../uploads/profile/<?= htmlspecialchars($user['profile_image'] ?: 'default.png') ?>" alt="Profile">
                <span><?= htmlspecialchars($user['name']) ?></span>
            </div>

            <?php if ($selectedCategoryId): ?>
                <a href="product_page.php" class="back-btn">Back To All</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="search-area">
        <input type="text" placeholder="Search for products..." onclick="location.href='search.php'" readonly>
    </div>
</header>

<div class="product-grid">
<?php if ($products): ?>
    <?php while ($row = $products->fetch_assoc()): ?>
        <div class="product-card" id="product-<?= (int) $row['id'] ?>" data-product-id="<?= (int) $row['id'] ?>">
            <div class="price">Rs <?= number_format((float) $row['price'], 2) ?></div>

            <div class="product-img">
                <img src="../uploads/products/<?= rawurlencode((string) $row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
            </div>

            <div class="card-body">
                <h3><?= htmlspecialchars($row['name']) ?></h3>
                <p class="desc"><?= htmlspecialchars($row['description']) ?></p>

                <div class="card-footer">
                    <span class="offer">Rs <?= number_format((float) $row['offer_price'], 2) ?></span>
                    <button class="add-cart-btn" type="button" data-product-id="<?= (int) $row['id'] ?>">Add to Cart</button>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>
</div>

<?php if ($selectedCategoryId && isset($productStmt)): ?>
    <?php $productStmt->close(); ?>
<?php endif; ?>

<footer>
    <p>Made with care by <span>Saksham</span> | ShopSphere | Premium | Secure</p>
</footer>

<div id="dupAlert">Product already in cart</div>

<script>
const navBar = document.getElementById('categoryNav');
const navToggle = document.getElementById('categoryToggle');
const previewPlaceholder = document.getElementById('previewPlaceholder');
const previewLinks = Array.from(document.querySelectorAll('[data-preview-target]'));
const previewPanels = Array.from(document.querySelectorAll('[data-preview-panel]'));
const previewCatalog = <?= json_encode($previewCatalog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const defaultPreviewSlug = <?= json_encode($defaultPreviewSlug, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

function renderPreviewCards(slug, items) {
    const resultsContainer = document.querySelector('[data-preview-results="' + slug + '"]');
    const statusNode = document.querySelector('[data-preview-status="' + slug + '"]');

    if (!resultsContainer || !statusNode) {
        return;
    }

    resultsContainer.scrollLeft = 0;

    if (!items.length) {
        resultsContainer.innerHTML = '<div class="preview-empty-card">No products available right now.</div>';
        statusNode.textContent = 'No products available right now.';
        statusNode.classList.add('empty');
        return;
    }

    statusNode.classList.remove('empty');
    statusNode.textContent = items.length + ' product' + (items.length === 1 ? '' : 's') + ' ready to explore';

    resultsContainer.innerHTML = items.map((item) => `
        <a class="preview-product-card" href="${item.link}">
            <div class="preview-product-image">
                <img src="${item.image}" alt="${item.name}">
            </div>
            <div class="preview-product-content">
                <h4>${item.name}</h4>
                <p>${item.description}</p>
                <div class="preview-price">
                    <strong>Rs ${item.offer_price}</strong>
                    <span>Rs ${item.price}</span>
                </div>
            </div>
        </a>
    `).join('');
}

function openPreview(slug) {
    let found = false;

    previewLinks.forEach((button) => {
        button.classList.toggle('active-preview', button.dataset.previewTarget === slug);
    });

    previewPanels.forEach((panel) => {
        const isActive = panel.dataset.previewPanel === slug;
        panel.classList.toggle('active', isActive);
        found = found || isActive;
    });

    previewPlaceholder.style.display = found ? 'none' : 'flex';
}

function filterPreview(slug, query) {
    const items = previewCatalog[slug] ? previewCatalog[slug].products : [];
    const normalizedQuery = query.trim().toLowerCase();

    if (!normalizedQuery) {
        renderPreviewCards(slug, items);
        return;
    }

    renderPreviewCards(slug, items.filter((item) => {
        return item.name.toLowerCase().includes(normalizedQuery)
            || item.description.toLowerCase().includes(normalizedQuery);
    }));
}

Object.keys(previewCatalog).forEach((slug) => {
    renderPreviewCards(slug, previewCatalog[slug].products || []);
});

previewLinks.forEach((button) => {
    const slug = button.dataset.previewTarget;
    button.addEventListener('mouseenter', () => openPreview(slug));
    button.addEventListener('focus', () => openPreview(slug));
    button.addEventListener('click', () => openPreview(slug));
});

document.querySelectorAll('[data-preview-search]').forEach((input) => {
    input.addEventListener('input', () => {
        filterPreview(input.dataset.previewSearch, input.value);
    });
});

document.querySelectorAll('[data-scroll-target]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.scrollTarget);

        if (!target) {
            return;
        }

        const direction = Number(button.dataset.scrollDirection || '1');
        const distance = Math.max(target.clientWidth * 0.82, 260) * direction;
        target.scrollBy({left: distance, behavior: 'smooth'});
    });
});

navToggle.addEventListener('click', () => {
    const isOpen = navBar.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', String(isOpen));

    if (isOpen && defaultPreviewSlug) {
        openPreview(defaultPreviewSlug);
    }

    if (!isOpen) {
        openPreview('');
    }
});

if (window.matchMedia('(min-width: 901px)').matches) {
    navBar.addEventListener('mouseenter', () => {
        navBar.classList.add('open');
        navToggle.setAttribute('aria-expanded', 'true');

        if (defaultPreviewSlug) {
            openPreview(defaultPreviewSlug);
        }
    });

    navBar.addEventListener('mouseleave', () => {
        navBar.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
        openPreview('');
    });
}

let cartCount = <?= (int) $initialCartCount ?>;
const cartProducts = new Set(<?= json_encode($cartProductIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
let cartStopped = false;
const cartCountEl = document.getElementById('cartCount');
const dupAlert = document.getElementById('dupAlert');

document.querySelectorAll('.add-cart-btn').forEach((button) => {
    button.addEventListener('click', async () => {
        if (cartStopped) {
            return;
        }

        const productId = button.dataset.productId;

        if (cartProducts.has(productId)) {
            dupAlert.style.display = 'block';
            setTimeout(() => { dupAlert.style.display = 'none'; }, 1500);
            return;
        }

        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'add_to_cart=1&product_id=' + encodeURIComponent(productId),
            });

            const data = await response.json();

            if (data.status === 'exists') {
                dupAlert.style.display = 'block';
                setTimeout(() => { dupAlert.style.display = 'none'; }, 1500);
                return;
            }

            if (data.status === 'added') {
                cartProducts.add(productId);
                cartCount = data.count;
                cartCountEl.textContent = cartCount;
                cartCountEl.classList.add('show');
            }
        } catch (error) {
            console.error(error);
        }
    });
});

document.querySelector('.cart-btn').addEventListener('click', () => {
    cartStopped = true;
});
</script>

</body>
</html>
