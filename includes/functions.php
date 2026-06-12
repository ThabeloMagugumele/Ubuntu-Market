<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        header('Location: ' . SITE_URL . '/index.php?error=access_denied');
        exit;
    }
}

function requireAdmin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isLoggedIn() || !in_array($_SESSION['user_role'] ?? '', ['admin','superadmin'], true)) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
         . sanitize($flash['message'])
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

function getListings(array $filters = [], int $limit = 12, int $offset = 0): array {
    $pdo = getDB();
    $where = ['l.status = "active"'];
    $params = [];

    if (!empty($filters['category_id'])) {
        $where[] = 'l.category_id = ?';
        $params[] = (int)$filters['category_id'];
    }
    if (!empty($filters['search'])) {
        $where[] = 'MATCH(l.title, l.description) AGAINST(? IN BOOLEAN MODE)';
        $params[] = $filters['search'] . '*';
    }
    if (!empty($filters['min_price'])) {
        $where[] = 'l.price >= ?';
        $params[] = (float)$filters['min_price'];
    }
    if (!empty($filters['max_price'])) {
        $where[] = 'l.price <= ?';
        $params[] = (float)$filters['max_price'];
    }
    if (!empty($filters['condition_type'])) {
        $where[] = 'l.condition_type = ?';
        $params[] = $filters['condition_type'];
    }

    $whereSQL = implode(' AND ', $where);
    $orderBy = match($filters['sort'] ?? 'newest') {
        'price_asc'  => 'l.price ASC',
        'price_desc' => 'l.price DESC',
        'popular'    => 'l.views DESC',
        default      => 'l.created_at DESC',
    };

    $sql = "SELECT l.*, c.name AS category_name, u.first_name, u.last_name, u.city
            FROM listings l
            JOIN categories c ON l.category_id = c.id
            JOIN users u ON l.seller_id = u.id
            WHERE {$whereSQL}
            ORDER BY {$orderBy}
            LIMIT {$limit} OFFSET {$offset}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getListing(int $id): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'SELECT l.*, c.name AS category_name, u.first_name, u.last_name, u.city, u.profile_image,
                u.phone, u.id AS user_id_col
         FROM listings l
         JOIN categories c ON l.category_id = c.id
         JOIN users u ON l.seller_id = u.id
         WHERE l.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function countListings(array $filters = []): int {
    $pdo = getDB();
    $where = ['l.status = "active"'];
    $params = [];
    if (!empty($filters['category_id'])) {
        $where[] = 'l.category_id = ?';
        $params[] = (int)$filters['category_id'];
    }
    if (!empty($filters['search'])) {
        $where[] = 'MATCH(l.title, l.description) AGAINST(? IN BOOLEAN MODE)';
        $params[] = $filters['search'] . '*';
    }
    $sql = 'SELECT COUNT(*) FROM listings l WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function getCategories(): array {
    $pdo = getDB();
    return $pdo->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();
}

function getCartCount(int $userId): int {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getCartItems(int $userId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'SELECT c.*, l.title, l.price, l.image_main, l.status AS listing_status, u.first_name, u.last_name
         FROM cart c
         JOIN listings l ON c.listing_id = l.id
         JOIN users u ON l.seller_id = u.id
         WHERE c.user_id = ?'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUnreadCount(int $userId): int {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function uploadImage(array $file, string $prefix = 'img'): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;

    $allowedMimes = ['image/jpeg','image/png','image/webp','image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedMimes, true)) return false;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . uniqid('', true) . '.' . strtolower($ext);
    $dest = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

    return 'uploads/' . $filename;
}

function paginate(int $total, int $perPage, int $currentPage, string $urlPattern): string {
    if ($total <= $perPage) return '';
    $pages = (int)ceil($total / $perPage);
    $html = '<nav><ul class="pagination justify-content-center flex-wrap">';
    for ($i = 1; $i <= $pages; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $url = str_replace('{page}', $i, $urlPattern);
        $html .= "<li class=\"page-item{$active}\"><a class=\"page-link\" href=\"{$url}\">{$i}</a></li>";
    }
    $html .= '</ul></nav>';
    return $html;
}

function formatPrice(float $price): string {
    return 'R ' . number_format($price, 2);
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    return match(true) {
        $diff < 60      => 'Just now',
        $diff < 3600    => floor($diff/60) . ' min ago',
        $diff < 86400   => floor($diff/3600) . ' hr ago',
        $diff < 604800  => floor($diff/86400) . ' days ago',
        default         => date('d M Y', strtotime($datetime)),
    };
}

function conditionLabel(string $c): string {
    return match($c) {
        'new'           => '<span class="badge bg-success">New</span>',
        'used_like_new' => '<span class="badge bg-primary">Like New</span>',
        'used_good'     => '<span class="badge bg-info text-dark">Good</span>',
        'used_fair'     => '<span class="badge bg-warning text-dark">Fair</span>',
        default         => '<span class="badge bg-secondary">Unknown</span>',
    };
}

function getSellerRating(int $sellerId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE reviewed_id = ?');
    $stmt->execute([$sellerId]);
    return $stmt->fetch();
}
