<?php
// ============================================================
//  admin/index.php  —  full admin panel
//  Hero + Story + Marquee + Menu + Feature + Gallery + Values + Quote CRUD
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------- Auth ---------- */
$isAdmin = false;
$adminUser = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $adminUser = $stmt->fetch() ?: null;
    $isAdmin = $adminUser && (int)$adminUser['id'] === 1;
}

/* ---------- Logout ---------- */
if (($_GET['action'] ?? '') === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ../index.php');
    exit;
}
if (!$isAdmin) { header('Location: ../user/login.php'); exit; }

/* ---------- Page ---------- */
$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard', 'users', 'reservations', 'story', 'hero', 'marquee', 'menu', 'feature', 'gallery', 'values', 'quote'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';

$flash = null;

/* ---------- Handle POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* --- Users --- */
    if ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid && $uid !== (int)$adminUser['id']) {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            $flash = ['success', 'User deleted.'];
        } else {
            $flash = ['error', 'You cannot delete your own account.'];
        }
    }

    /* --- Reservations --- */
    if ($action === 'update_reservation') {
        $rid = (int)($_POST['reservation_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($rid && in_array($status, ['pending','confirmed','cancelled'], true)) {
            $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?')->execute([$status, $rid]);
            $flash = ['success', 'Reservation updated.'];
        }
    }
    if ($action === 'delete_reservation') {
        $rid = (int)($_POST['reservation_id'] ?? 0);
        if ($rid) {
            $pdo->prepare('DELETE FROM reservations WHERE id = ?')->execute([$rid]);
            $flash = ['success', 'Reservation deleted.'];
        }
    }

    /* --- Story: INSERT --- */
    if ($action === 'story_insert') {
        $imagePath = '';
        if (!empty($_FILES['story_image']['name']) && $_FILES['story_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['story_image'];
            if ($file['size'] <= 5 * 1024 * 1024) {
                $mime = mime_content_type($file['tmp_name']);
                $allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                if (isset($allowedMime[$mime])) {
                    $filename = 'story-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                    $uploadDir = __DIR__ . '/uploads/story/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $imagePath = 'uploads/story/' . $filename;
                    }
                }
            }
        }

        $sql = 'INSERT INTO story_section
                  (eyebrow, title_line1, title_line2, paragraph1, paragraph2,
                   button_text, button_link, image_path, stamp_text, is_active)
                VALUES (?,?,?,?,?,?,?,?,?,1)';
        $pdo->prepare($sql)->execute([
            trim($_POST['eyebrow'] ?? ''),
            trim($_POST['title_line1'] ?? ''),
            trim($_POST['title_line2'] ?? ''),
            trim($_POST['paragraph1'] ?? ''),
            trim($_POST['paragraph2'] ?? ''),
            trim($_POST['button_text'] ?? ''),
            trim($_POST['button_link'] ?? ''),
            $imagePath,
            trim($_POST['stamp_text'] ?? ''),
        ]);
        $flash = ['success', 'Story section created.'];
    }

    /* --- Story: UPDATE --- */
    if ($action === 'story_update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $cur = $pdo->prepare('SELECT * FROM story_section WHERE id = ? LIMIT 1');
            $cur->execute([$id]);
            $current = $cur->fetch();

            $imagePath = $current['image_path'] ?? '';
            $removeImage = isset($_POST['remove_image']);

            if (!empty($_FILES['story_image']['name']) && $_FILES['story_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['story_image'];
                if ($file['size'] <= 5 * 1024 * 1024) {
                    $mime = mime_content_type($file['tmp_name']);
                    $allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                    if (isset($allowedMime[$mime])) {
                        $filename = 'story-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                        $uploadDir = __DIR__ . '/uploads/story/';
                        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            if (!empty($current['image_path']) && strpos($current['image_path'], 'uploads/story/') !== false) {
                                $old = __DIR__ . '/' . $current['image_path'];
                                if (is_file($old)) @unlink($old);
                            }
                            $imagePath = 'uploads/story/' . $filename;
                            $removeImage = false;
                        }
                    }
                }
            }

            if ($removeImage && !empty($current['image_path'])) {
                if (strpos($current['image_path'], 'uploads/story/') !== false) {
                    $old = __DIR__ . '/' . $current['image_path'];
                    if (is_file($old)) @unlink($old);
                }
                $imagePath = '';
            }

            $sql = 'UPDATE story_section SET
                      eyebrow = ?, title_line1 = ?, title_line2 = ?,
                      paragraph1 = ?, paragraph2 = ?, button_text = ?, button_link = ?,
                      image_path = ?, stamp_text = ?, is_active = ?
                    WHERE id = ?';
            $pdo->prepare($sql)->execute([
                trim($_POST['eyebrow'] ?? ''),
                trim($_POST['title_line1'] ?? ''),
                trim($_POST['title_line2'] ?? ''),
                trim($_POST['paragraph1'] ?? ''),
                trim($_POST['paragraph2'] ?? ''),
                trim($_POST['button_text'] ?? ''),
                trim($_POST['button_link'] ?? ''),
                $imagePath,
                trim($_POST['stamp_text'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $id,
            ]);
            $flash = ['success', 'Story section updated.'];
        }
    }

    /* --- Story: DELETE --- */
    if ($action === 'story_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('DELETE FROM story_section WHERE id = ?')->execute([$id]);
            $flash = ['success', 'Story section deleted.'];
        }
    }

    /* --- HERO: UPDATE --- */
    if ($action === 'hero_update') {
        $id = (int)($_POST['id'] ?? 0);
        $title1 = trim($_POST['title_line1'] ?? '');
        $title2 = trim($_POST['title_line2'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');

        if ($id && $title1 !== '' && $subtitle !== '') {
            $cur = $pdo->prepare('SELECT * FROM hero_section WHERE id = ? LIMIT 1');
            $cur->execute([$id]);
            $current = $cur->fetch();

            $newImagePath = $current['image_path'] ?? '';
            $removeImage = isset($_POST['remove_image']);

            if (!empty($_FILES['hero_image']['name']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['hero_image'];
                if ($file['size'] > 5 * 1024 * 1024) {
                    $flash = ['error', 'Image is too large (max 5 MB).'];
                } else {
                    $mime = mime_content_type($file['tmp_name']);
                    $allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                    if (!isset($allowedMime[$mime])) {
                        $flash = ['error', 'Invalid image type. Use JPG, PNG, WebP, or GIF.'];
                    } else {
                        $filename = 'hero-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                        $uploadDir = __DIR__ . '/uploads/hero/';
                        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            if (!empty($current['image_path']) && strpos($current['image_path'], 'uploads/hero/') !== false) {
                                $oldFile = __DIR__ . '/' . $current['image_path'];
                                if (is_file($oldFile)) @unlink($oldFile);
                            }
                            $newImagePath = 'uploads/hero/' . $filename;
                            $removeImage = false;
                        } else {
                            $flash = ['error', 'Could not save the uploaded image.'];
                        }
                    }
                }
            }

            if ($removeImage) {
                if (!empty($current['image_path']) && strpos($current['image_path'], 'uploads/hero/') !== false) {
                    $oldFile = __DIR__ . '/' . $current['image_path'];
                    if (is_file($oldFile)) @unlink($oldFile);
                }
                $newImagePath = '';
            }

            if (!$flash || $flash[0] !== 'error') {
                $pdo->prepare('UPDATE hero_section SET title_line1 = ?, title_line2 = ?, subtitle = ?, image_path = ? WHERE id = ?')
                    ->execute([$title1, $title2, $subtitle, $newImagePath, $id]);
                $flash = ['success', 'Hero section updated.'];
            }
        } else {
            $flash = ['error', 'Title line 1 and subtitle are required.'];
        }
    }

    /* --- MARQUEE: UPDATE --- */
    if ($action === 'marquee_update') {
        $id = (int)($_POST['id'] ?? 0);
        $items = trim($_POST['items'] ?? '');
        if ($id && $items !== '') {
            $pdo->prepare('UPDATE marquee_section SET items = ? WHERE id = ?')
                ->execute([$items, $id]);
            $flash = ['success', 'Marquee section updated.'];
        } else {
            $flash = ['error', 'Please enter at least one phrase.'];
        }
    }

    /* --- MENU SECTION: UPDATE --- */
    if ($action === 'menu_section_update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('UPDATE menu_section SET
                eyebrow = ?, title_line1 = ?, title_line2 = ?,
                section_copy = ?, footnote = ?,
                cta_text = ?, cta_link = ?, is_active = ?
                WHERE id = ?')
                ->execute([
                    trim($_POST['eyebrow'] ?? ''),
                    trim($_POST['title_line1'] ?? ''),
                    trim($_POST['title_line2'] ?? ''),
                    trim($_POST['section_copy'] ?? ''),
                    trim($_POST['footnote'] ?? ''),
                    trim($_POST['cta_text'] ?? ''),
                    trim($_POST['cta_link'] ?? '#visit'),
                    isset($_POST['is_active']) ? 1 : 0,
                    $id,
                ]);
            $flash = ['success', 'Menu section header updated.'];
        }
    }

    /* --- MENU ITEM: INSERT --- */
    if ($action === 'menu_item_insert') {
        $imagePath = '';
        if (!empty($_FILES['menu_image']['name']) && $_FILES['menu_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['menu_image'];
            if ($file['size'] <= 5 * 1024 * 1024) {
                $mime = mime_content_type($file['tmp_name']);
                $allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                if (isset($allowedMime[$mime])) {
                    $filename = 'menu-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                    $uploadDir = __DIR__ . '/uploads/menu/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $imagePath = 'uploads/menu/' . $filename;
                    }
                }
            }
        }

        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($categoryId && $name !== '') {
            $pdo->prepare('INSERT INTO menu_items
                (category_id, name, description, price, image_path, is_active, sort_order)
                VALUES (?,?,?,?,?,?,?)')
                ->execute([
                    $categoryId,
                    $name,
                    trim($_POST['description'] ?? ''),
                    (float)($_POST['price'] ?? 0),
                    $imagePath,
                    isset($_POST['is_active']) ? 1 : 0,
                    (int)($_POST['sort_order'] ?? 0),
                ]);
            $flash = ['success', 'Menu item created.'];
        } else {
            $flash = ['error', 'Category and item name are required.'];
        }
    }

    /* --- MENU ITEM: UPDATE --- */
    if ($action === 'menu_item_update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $cur = $pdo->prepare('SELECT * FROM menu_items WHERE id = ? LIMIT 1');
            $cur->execute([$id]);
            $current = $cur->fetch();

            $imagePath = $current['image_path'] ?? '';
            $removeImage = isset($_POST['remove_image']);

            if (!empty($_FILES['menu_image']['name']) && $_FILES['menu_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['menu_image'];
                if ($file['size'] <= 5 * 1024 * 1024) {
                    $mime = mime_content_type($file['tmp_name']);
                    $allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                    if (isset($allowedMime[$mime])) {
                        $filename = 'menu-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                        $uploadDir = __DIR__ . '/uploads/menu/';
                        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            if (!empty($current['image_path']) && strpos($current['image_path'], 'uploads/menu/') !== false) {
                                $old = __DIR__ . '/' . $current['image_path'];
                                if (is_file($old)) @unlink($old);
                            }
                            $imagePath = 'uploads/menu/' . $filename;
                            $removeImage = false;
                        }
                    }
                }
            }

            if ($removeImage && !empty($current['image_path'])) {
                if (strpos($current['image_path'], 'uploads/menu/') !== false) {
                    $old = __DIR__ . '/' . $current['image_path'];
                    if (is_file($old)) @unlink($old);
                }
                $imagePath = '';
            }

            $pdo->prepare('UPDATE menu_items SET
                category_id = ?, name = ?, description = ?, price = ?,
                image_path = ?, is_active = ?, sort_order = ?
                WHERE id = ?')
                ->execute([
                    (int)($_POST['category_id'] ?? 0),
                    trim($_POST['name'] ?? ''),
                    trim($_POST['description'] ?? ''),
                    (float)($_POST['price'] ?? 0),
                    $imagePath,
                    isset($_POST['is_active']) ? 1 : 0,
                    (int)($_POST['sort_order'] ?? 0),
                    $id,
                ]);
            $flash = ['success', 'Menu item updated.'];
        }
    }

    /* --- MENU ITEM: DELETE --- */
    if ($action === 'menu_item_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $cur = $pdo->prepare('SELECT image_path FROM menu_items WHERE id = ? LIMIT 1');
            $cur->execute([$id]);
            $row = $cur->fetch();
            if ($row && !empty($row['image_path']) && strpos($row['image_path'], 'uploads/menu/') !== false) {
                $old = __DIR__ . '/' . $row['image_path'];
                if (is_file($old)) @unlink($old);
            }
            $pdo->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
            $flash = ['success', 'Menu item deleted.'];
        }
    }

    /* --- FEATURE: UPDATE --- */
    if ($action === 'feature_update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $cur = $pdo->prepare('SELECT * FROM feature_section WHERE id = ? LIMIT 1');
            $cur->execute([$id]);
            $current = $cur->fetch();

            $bgImagePath = $current['bg_image_path'] ?? '';
            $removeImage = isset($_POST['remove_image']);

            if (!empty($_FILES['feature_bg']['name']) && $_FILES['feature_bg']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['feature_bg'];
                if ($file['size'] > 5 * 1024 * 1024) {
                    $flash = ['error', 'Image is too large (max 5 MB).'];
                } else {
                    $mime = mime_content_type($file['tmp_name']);
                    $allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                    if (!isset($allowedMime[$mime])) {
                        $flash = ['error', 'Invalid image type. Use JPG, PNG, WebP, or GIF.'];
                    } else {
                        $filename = 'feature-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                        $uploadDir = __DIR__ . '/uploads/feature/';
                        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            if (!empty($current['bg_image_path']) && strpos($current['bg_image_path'], 'uploads/feature/') !== false) {
                                $oldFile = __DIR__ . '/' . $current['bg_image_path'];
                                if (is_file($oldFile)) @unlink($oldFile);
                            }
                            $bgImagePath = 'uploads/feature/' . $filename;
                            $removeImage = false;
                        } else {
                            $flash = ['error', 'Could not save the uploaded image.'];
                        }
                    }
                }
            }

            if ($removeImage) {
                if (!empty($current['bg_image_path']) && strpos($current['bg_image_path'], 'uploads/feature/') !== false) {
                    $oldFile = __DIR__ . '/' . $current['bg_image_path'];
                    if (is_file($oldFile)) @unlink($oldFile);
                }
                $bgImagePath = '';
            }

            if (!$flash || $flash[0] !== 'error') {
                $color = trim($_POST['title_line2_color'] ?? '#d8b38d');
                if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) $color = '#d8b38d';

                $pdo->prepare('UPDATE feature_section SET
                    eyebrow = ?, title_line1 = ?, title_line2 = ?, title_line2_color = ?,
                    description = ?, cta_text = ?, cta_link = ?, bg_image_path = ?, is_active = ?
                    WHERE id = ?')
                    ->execute([
                        trim($_POST['eyebrow'] ?? ''),
                        trim($_POST['title_line1'] ?? ''),
                        trim($_POST['title_line2'] ?? ''),
                        $color,
                        trim($_POST['description'] ?? ''),
                        trim($_POST['cta_text'] ?? ''),
                        trim($_POST['cta_link'] ?? '#menu'),
                        $bgImagePath,
                        isset($_POST['is_active']) ? 1 : 0,
                        $id,
                    ]);
                $flash = ['success', 'Feature section updated.'];
            }
        } else {
            $flash = ['error', 'Invalid feature section id.'];
        }
    }

    /* --- GALLERY SECTION: UPDATE --- */
    if ($action === 'gallery_section_update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('UPDATE gallery_section SET
                eyebrow = ?, title_line1 = ?, title_line2 = ?,
                section_copy = ?, is_active = ?
                WHERE id = ?')
                ->execute([
                    trim($_POST['eyebrow'] ?? ''),
                    trim($_POST['title_line1'] ?? ''),
                    trim($_POST['title_line2'] ?? ''),
                    trim($_POST['section_copy'] ?? ''),
                    isset($_POST['is_active']) ? 1 : 0,
                    $id,
                ]);
            $flash = ['success', 'Gallery section header updated.'];
        }
    }

    /* --- GALLERY IMAGE: INSERT --- */
    if ($action === 'gallery_image_insert') {
        $imagePath = '';
        $fullImagePath = '';

        $uploadDir = __DIR__ . '/uploads/gallery/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];

        if (!empty($_FILES['gallery_image']['name']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['gallery_image'];
            if ($file['size'] <= 5 * 1024 * 1024) {
                $mime = mime_content_type($file['tmp_name']);
                if (isset($allowedMime[$mime])) {
                    $filename = 'gallery-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $imagePath = 'uploads/gallery/' . $filename;
                    }
                }
            }
        }

        if (!empty($_FILES['gallery_full_image']['name']) && $_FILES['gallery_full_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['gallery_full_image'];
            if ($file['size'] <= 8 * 1024 * 1024) {
                $mime = mime_content_type($file['tmp_name']);
                if (isset($allowedMime[$mime])) {
                    $filename = 'gallery-full-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $fullImagePath = 'uploads/gallery/' . $filename;
                    }
                }
            }
        }

        $altText = trim($_POST['alt_text'] ?? '');
        if ($imagePath !== '') {
            $pdo->prepare('INSERT INTO gallery_images
                (image_path, full_image_path, alt_text, is_active, sort_order)
                VALUES (?,?,?,?,?)')
                ->execute([
                    $imagePath,
                    $fullImagePath,
                    $altText,
                    isset($_POST['is_active']) ? 1 : 0,
                    (int)($_POST['sort_order'] ?? 0),
                ]);
            $flash = ['success', 'Gallery image added.'];
        } else {
            $flash = ['error', 'Please upload a valid thumbnail image (JPG/PNG/WebP/GIF, max 5 MB).'];
        }
    }

    /* --- GALLERY IMAGE: UPDATE --- */
    if ($action === 'gallery_image_update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $cur = $pdo->prepare('SELECT * FROM gallery_images WHERE id = ? LIMIT 1');
            $cur->execute([$id]);
            $current = $cur->fetch();

            $imagePath = $current['image_path'] ?? '';
            $fullImagePath = $current['full_image_path'] ?? '';
            $removeImage = isset($_POST['remove_image']);
            $removeFullImage = isset($_POST['remove_full_image']);

            $uploadDir = __DIR__ . '/uploads/gallery/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            $allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];

            if (!empty($_FILES['gallery_image']['name']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['gallery_image'];
                if ($file['size'] <= 5 * 1024 * 1024) {
                    $mime = mime_content_type($file['tmp_name']);
                    if (isset($allowedMime[$mime])) {
                        $filename = 'gallery-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            if (!empty($current['image_path']) && strpos($current['image_path'], 'uploads/gallery/') !== false) {
                                $old = __DIR__ . '/' . $current['image_path'];
                                if (is_file($old)) @unlink($old);
                            }
                            $imagePath = 'uploads/gallery/' . $filename;
                            $removeImage = false;
                        }
                    }
                }
            }

            if (!empty($_FILES['gallery_full_image']['name']) && $_FILES['gallery_full_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['gallery_full_image'];
                if ($file['size'] <= 8 * 1024 * 1024) {
                    $mime = mime_content_type($file['tmp_name']);
                    if (isset($allowedMime[$mime])) {
                        $filename = 'gallery-full-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            if (!empty($current['full_image_path']) && strpos($current['full_image_path'], 'uploads/gallery/') !== false) {
                                $old = __DIR__ . '/' . $current['full_image_path'];
                                if (is_file($old)) @unlink($old);
                            }
                            $fullImagePath = 'uploads/gallery/' . $filename;
                            $removeFullImage = false;
                        }
                    }
                }
            }

            if ($removeImage && !empty($current['image_path'])) {
                if (strpos($current['image_path'], 'uploads/gallery/') !== false) {
                    $old = __DIR__ . '/' . $current['image_path'];
                    if (is_file($old)) @unlink($old);
                }
                $imagePath = '';
            }

            if ($removeFullImage && !empty($current['full_image_path'])) {
                if (strpos($current['full_image_path'], 'uploads/gallery/') !== false) {
                    $old = __DIR__ . '/' . $current['full_image_path'];
                    if (is_file($old)) @unlink($old);
                }
                $fullImagePath = '';
            }

            $pdo->prepare('UPDATE gallery_images SET
                image_path = ?, full_image_path = ?, alt_text = ?, is_active = ?, sort_order = ?
                WHERE id = ?')
                ->execute([
                    $imagePath,
                    $fullImagePath,
                    trim($_POST['alt_text'] ?? ''),
                    isset($_POST['is_active']) ? 1 : 0,
                    (int)($_POST['sort_order'] ?? 0),
                    $id,
                ]);
            $flash = ['success', 'Gallery image updated.'];
        }
    }

    /* --- GALLERY IMAGE: DELETE --- */
    if ($action === 'gallery_image_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $cur = $pdo->prepare('SELECT image_path, full_image_path FROM gallery_images WHERE id = ? LIMIT 1');
            $cur->execute([$id]);
            $row = $cur->fetch();
            if ($row) {
                if (!empty($row['image_path']) && strpos($row['image_path'], 'uploads/gallery/') !== false) {
                    $old = __DIR__ . '/' . $row['image_path'];
                    if (is_file($old)) @unlink($old);
                }
                if (!empty($row['full_image_path']) && strpos($row['full_image_path'], 'uploads/gallery/') !== false) {
                    $old = __DIR__ . '/' . $row['full_image_path'];
                    if (is_file($old)) @unlink($old);
                }
            }
            $pdo->prepare('DELETE FROM gallery_images WHERE id = ?')->execute([$id]);
            $flash = ['success', 'Gallery image deleted.'];
        }
    }

    /* --- VALUES SECTION: UPDATE --- */
    if ($action === 'values_section_update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('UPDATE values_section SET
                eyebrow = ?, title = ?, is_active = ?
                WHERE id = ?')
                ->execute([
                    trim($_POST['eyebrow'] ?? ''),
                    trim($_POST['title'] ?? ''),
                    isset($_POST['is_active']) ? 1 : 0,
                    $id,
                ]);
            $flash = ['success', 'Values section header updated.'];
        }
    }

    /* --- VALUES CARD: INSERT --- */
    if ($action === 'values_card_insert') {
        $icon = trim($_POST['icon_class'] ?? '');
        $title = trim($_POST['title'] ?? '');
        if ($icon !== '' && $title !== '') {
            $pdo->prepare('INSERT INTO values_cards
                (icon_class, title, description, is_active, sort_order)
                VALUES (?,?,?,?,?)')
                ->execute([
                    $icon,
                    $title,
                    trim($_POST['description'] ?? ''),
                    isset($_POST['is_active']) ? 1 : 0,
                    (int)($_POST['sort_order'] ?? 0),
                ]);
            $flash = ['success', 'Value card created.'];
        } else {
            $flash = ['error', 'Icon class and title are required.'];
        }
    }

    /* --- VALUES CARD: UPDATE --- */
    if ($action === 'values_card_update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('UPDATE values_cards SET
                icon_class = ?, title = ?, description = ?, is_active = ?, sort_order = ?
                WHERE id = ?')
                ->execute([
                    trim($_POST['icon_class'] ?? ''),
                    trim($_POST['title'] ?? ''),
                    trim($_POST['description'] ?? ''),
                    isset($_POST['is_active']) ? 1 : 0,
                    (int)($_POST['sort_order'] ?? 0),
                    $id,
                ]);
            $flash = ['success', 'Value card updated.'];
        }
    }

    /* --- VALUES CARD: DELETE --- */
    if ($action === 'values_card_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('DELETE FROM values_cards WHERE id = ?')->execute([$id]);
            $flash = ['success', 'Value card deleted.'];
        }
    }

    /* --- QUOTE: UPDATE --- */
    if ($action === 'quote_update') {
        $id = (int)($_POST['id'] ?? 0);
        $quoteText = trim($_POST['quote_text'] ?? '');
        if ($id && $quoteText !== '') {
            $pdo->prepare('UPDATE quote_section SET
                quote_text = ?, author = ?, is_active = ?
                WHERE id = ?')
                ->execute([
                    $quoteText,
                    trim($_POST['author'] ?? ''),
                    isset($_POST['is_active']) ? 1 : 0,
                    $id,
                ]);
            $flash = ['success', 'Quote section updated.'];
        } else {
            $flash = ['error', 'Quote text is required.'];
        }
    }
}

/* ---------- Stats ---------- */
$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalReservations = $pendingReservations = $todayReservations = 0;
try {
    $totalReservations = (int)$pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
    $pendingReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
    $todayReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE res_date = CURDATE()")->fetchColumn();
} catch (PDOException $e) {}

/* ---------- Data for pages ---------- */
$users = $reservations = $stories = [];
$editingStory = null;
$hero = null;
$marquee = null;
$menuSection = null;
$menuCategories = [];
$menuItems = [];
$editingMenuItem = null;
$feature = null;
$gallerySection = null;
$galleryImages = [];
$editingGalleryImage = null;
$valuesSection = null;
$valuesCards = [];
$editingValueCard = null;
$quote = null;

if ($page === 'users') {
    $users = $pdo->query('SELECT id, full_name, email, phone, created_at FROM users ORDER BY id DESC')->fetchAll();
}
if ($page === 'reservations') {
    try {
        $reservations = $pdo->query(
            'SELECT id, name, phone, email, res_date, res_time, guests, message, user_id, status, ip_address, created_at, updated_at
             FROM reservations
             ORDER BY res_date DESC, id DESC'
        )->fetchAll();
    } catch (PDOException $e) {
        $reservations = [];
    }
}
if ($page === 'story') {
    try { $stories = $pdo->query('SELECT * FROM story_section ORDER BY id DESC')->fetchAll(); }
    catch (PDOException $e) { $stories = []; }
    if (isset($_GET['edit'])) {
        $eid = (int)$_GET['edit'];
        try {
            $s = $pdo->prepare('SELECT * FROM story_section WHERE id = ? LIMIT 1');
            $s->execute([$eid]);
            $editingStory = $s->fetch() ?: null;
        } catch (PDOException $e) {}
    }
}
if ($page === 'hero') {
    try {
        $hero = $pdo->query('SELECT * FROM hero_section ORDER BY id DESC LIMIT 1')->fetch();
    } catch (PDOException $e) {
        $hero = null;
    }
}
if ($page === 'marquee') {
    try {
        $marquee = $pdo->query('SELECT * FROM marquee_section ORDER BY id DESC LIMIT 1')->fetch();
    } catch (PDOException $e) {
        $marquee = null;
    }
}
if ($page === 'menu') {
    try { $menuSection = $pdo->query('SELECT * FROM menu_section ORDER BY id DESC LIMIT 1')->fetch(); }
    catch (PDOException $e) { $menuSection = null; }

    try { $menuCategories = $pdo->query('SELECT * FROM menu_categories ORDER BY sort_order, id')->fetchAll(); }
    catch (PDOException $e) { $menuCategories = []; }

    try {
        $menuItems = $pdo->query('SELECT mi.*, mc.slug AS category_slug, mc.label AS category_label
                                  FROM menu_items mi
                                  JOIN menu_categories mc ON mc.id = mi.category_id
                                  ORDER BY mc.sort_order, mi.sort_order, mi.id')->fetchAll();
    } catch (PDOException $e) { $menuItems = []; }

    if (isset($_GET['edit_item'])) {
        $eid = (int)$_GET['edit_item'];
        try {
            $s = $pdo->prepare('SELECT * FROM menu_items WHERE id = ? LIMIT 1');
            $s->execute([$eid]);
            $editingMenuItem = $s->fetch() ?: null;
        } catch (PDOException $e) {}
    }
}
if ($page === 'feature') {
    try {
        $feature = $pdo->query('SELECT * FROM feature_section ORDER BY id DESC LIMIT 1')->fetch();
    } catch (PDOException $e) {
        $feature = null;
    }
}
if ($page === 'gallery') {
    try {
        $gallerySection = $pdo->query('SELECT * FROM gallery_section ORDER BY id DESC LIMIT 1')->fetch();
    } catch (PDOException $e) {
        $gallerySection = null;
    }

    try {
        $galleryImages = $pdo->query('SELECT * FROM gallery_images ORDER BY sort_order, id')->fetchAll();
    } catch (PDOException $e) { $galleryImages = []; }

    if (isset($_GET['edit_image'])) {
        $eid = (int)$_GET['edit_image'];
        try {
            $s = $pdo->prepare('SELECT * FROM gallery_images WHERE id = ? LIMIT 1');
            $s->execute([$eid]);
            $editingGalleryImage = $s->fetch() ?: null;
        } catch (PDOException $e) {}
    }
}
if ($page === 'values') {
    try {
        $valuesSection = $pdo->query('SELECT * FROM values_section ORDER BY id DESC LIMIT 1')->fetch();
    } catch (PDOException $e) {
        $valuesSection = null;
    }

    try {
        $valuesCards = $pdo->query('SELECT * FROM values_cards ORDER BY sort_order, id')->fetchAll();
    } catch (PDOException $e) { $valuesCards = []; }

    if (isset($_GET['edit_card'])) {
        $eid = (int)$_GET['edit_card'];
        try {
            $s = $pdo->prepare('SELECT * FROM values_cards WHERE id = ? LIMIT 1');
            $s->execute([$eid]);
            $editingValueCard = $s->fetch() ?: null;
        } catch (PDOException $e) {}
    }
}
if ($page === 'quote') {
    try {
        $quote = $pdo->query('SELECT * FROM quote_section ORDER BY id DESC LIMIT 1')->fetch();
    } catch (PDOException $e) {
        $quote = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#211914">
  <title>Admin — Café Krunal</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root{
      --ink:#211914; --coffee:#4a3024; --cream:#f4efe7; --paper:#fbf9f5;
      --tan:#c59b70; --muted:#83766b; --line:rgba(49,35,27,.14);
      --serif:"Playfair Display",Georgia,serif; --sans:"DM Sans",Arial,sans-serif;
      --danger:#a33a2a; --success:#3d6b45;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{margin:0;background:var(--cream);color:var(--ink);font-family:var(--sans);-webkit-font-smoothing:antialiased;font-size:.9rem}
    a{color:inherit;text-decoration:none}
    img{display:block;max-width:100%}

    .admin-wrap{display:flex;min-height:100vh}
    .admin-sidebar{width:260px;flex:0 0 260px;background:var(--ink);color:#f4efe7;
      display:flex;flex-direction:column;padding:28px 0;position:sticky;top:0;height:100vh;overflow-y:auto}
    .admin-brand{display:flex;align-items:center;gap:10px;padding:0 26px 26px;
      font:600 1.2rem var(--serif);letter-spacing:-.04em;color:#fff;
      border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:18px}
    .admin-brand .brand-mark{width:34px;height:34px;display:grid;place-items:center;
      border:1px solid var(--tan);border-radius:50%;color:#e5c5a2;font:500 1rem var(--serif);flex:0 0 auto}
    .admin-brand small{display:block;font:400 .6rem var(--sans);letter-spacing:.18em;
      text-transform:uppercase;color:var(--tan);margin-top:2px}
    .admin-nav{display:flex;flex-direction:column;gap:2px;padding:0 14px}
    .admin-nav a{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:6px;
      color:rgba(255,255,255,.7);font-size:.82rem;font-weight:500;transition:.2s}
    .admin-nav a i{font-size:1rem;width:18px;text-align:center}
    .admin-nav a:hover{background:rgba(255,255,255,.06);color:#fff}
    .admin-nav a.active{background:var(--coffee);color:#fff;box-shadow:inset 3px 0 0 var(--tan)}
    .admin-nav .nav-label{padding:18px 14px 6px;font-size:.6rem;letter-spacing:.18em;
      text-transform:uppercase;color:rgba(255,255,255,.35)}
    .admin-sidebar-foot{margin-top:auto;padding:20px 20px 0;border-top:1px solid rgba(255,255,255,.08)}
    .admin-sidebar-foot .who{display:flex;align-items:center;gap:10px;margin-bottom:14px}
    .admin-sidebar-foot .who .av{width:34px;height:34px;display:grid;place-items:center;
      border-radius:50%;background:var(--tan);color:var(--ink);font:600 .85rem var(--serif);flex:0 0 auto}
    .admin-sidebar-foot .who .nm{font-size:.78rem;font-weight:600;color:#fff;line-height:1.2}
    .admin-sidebar-foot .who .em{font-size:.68rem;color:rgba(255,255,255,.45);
      max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .admin-sidebar-foot button{width:100%;display:inline-flex;align-items:center;justify-content:center;
      gap:8px;padding:10px;background:transparent;border:1px solid rgba(255,255,255,.14);
      color:#f4efe7;font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
      cursor:pointer;transition:.25s}
    .admin-sidebar-foot button:hover{background:rgba(163,58,42,.18);
      border-color:rgba(163,58,42,.5);color:#f1c8bf}

    .admin-main{flex:1;min-width:0;padding:32px 40px 60px}
    .admin-topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;
      flex-wrap:wrap;margin-bottom:32px;padding-bottom:20px;border-bottom:1px solid var(--line)}
    .admin-topbar h1{margin:0;font:400 clamp(1.6rem,3vw,2.3rem)/1.1 var(--serif);letter-spacing:-.03em}
    .admin-topbar .crumb{color:var(--muted);font-size:.72rem;letter-spacing:.08em;text-transform:uppercase}

    .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
      gap:18px;margin-bottom:32px}
    .stat-card{background:var(--paper);border:1px solid var(--line);padding:22px 24px;
      display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden}
    .stat-card:before{content:"";position:absolute;top:0;left:0;width:4px;height:100%;background:var(--tan)}
    .stat-card .label{font-size:.64rem;font-weight:700;letter-spacing:.16em;
      text-transform:uppercase;color:var(--muted)}
    .stat-card .value{font:400 2.4rem/1 var(--serif);letter-spacing:-.04em;color:var(--ink)}
    .stat-card .icon{position:absolute;top:20px;right:20px;font-size:1.4rem;color:var(--tan);opacity:.7}

    .panel{background:var(--paper);border:1px solid var(--line);overflow:hidden;margin-bottom:24px}
    .panel-head{display:flex;align-items:center;justify-content:space-between;gap:14px;
      padding:18px 24px;border-bottom:1px solid var(--line);flex-wrap:wrap}
    .panel-head h2{margin:0;font:500 1.2rem var(--serif);letter-spacing:-.02em}
    .panel-body{padding:0}
    .panel-body.pad{padding:24px}

    .table{margin:0;font-size:.82rem}
    .table thead th{background:var(--cream);color:var(--muted);font-size:.62rem;font-weight:700;
      letter-spacing:.12em;text-transform:uppercase;border-bottom:1px solid var(--line);
      padding:12px 18px;white-space:nowrap}
    .table tbody td{padding:14px 18px;border-bottom:1px solid var(--line);vertical-align:middle}
    .table tbody tr:last-child td{border-bottom:0}
    .table tbody tr:hover{background:rgba(197,155,112,.06)}

    .badge-soft{display:inline-block;padding:4px 10px;border-radius:999px;
      font-size:.62rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
    .badge-pending{background:#fdf3d8;color:#8a6d0b}
    .badge-confirmed{background:#e3f0e4;color:#2d5233}
    .badge-cancelled{background:#fbe6e1;color:#7d2c1f}

    .btn-sm-coffee{display:inline-flex;align-items:center;gap:5px;padding:6px 11px;
      border:1px solid var(--line);background:#fff;color:var(--ink);font-size:.66rem;
      font-weight:700;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;
      transition:.2s;text-decoration:none}
    .btn-sm-coffee:hover{background:var(--ink);color:#fff;border-color:var(--ink)}
    .btn-sm-danger{border-color:#d9a99c;color:var(--danger)}
    .btn-sm-danger:hover{background:var(--danger);border-color:var(--danger);color:#fff}

    .form-label{color:#6e5d50;font-size:.63rem;font-weight:700;letter-spacing:.12em;
      text-transform:uppercase;margin-bottom:6px}
    .form-control,.form-select{border:1px solid #ddd1c4;border-radius:0;background:#fffdf9;
      font-size:.85rem;padding:10px 12px;min-height:42px}
    .form-control:focus,.form-select:focus{border-color:#a77b54;
      box-shadow:0 0 0 .2rem rgba(167,123,84,.12)}
    textarea.form-control{min-height:90px}
    input[type="color"].form-control{min-height:42px;padding:4px 6px}

    .btn-coffee{display:inline-flex;align-items:center;justify-content:center;gap:10px;
      padding:12px 22px;border:1px solid var(--ink);background:var(--ink);color:#fff;
      font-size:.7rem;font-weight:700;letter-spacing:.13em;text-transform:uppercase;
      cursor:pointer;transition:.3s;text-decoration:none}
    .btn-coffee:hover{background:var(--tan);border-color:var(--tan);color:var(--ink)}

    .flash{padding:12px 18px;margin-bottom:22px;border-left:3px solid;font-size:.82rem}
    .flash.success{background:#e3f0e4;border-color:var(--success);color:#2d5233}
    .flash.error{background:#fbe6e1;border-color:var(--danger);color:#7d2c1f}

    .empty-state{padding:50px 24px;text-align:center;color:var(--muted)}
    .empty-state i{font-size:2.4rem;color:var(--tan);margin-bottom:12px;display:block}
    .empty-state p{margin:0;font-size:.85rem}

    /* Story cards */
    .story-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
    .story-card{background:var(--paper);border:1px solid var(--line);overflow:hidden;
      display:flex;flex-direction:column;transition:.2s}
    .story-card:hover{border-color:var(--tan);box-shadow:0 8px 24px rgba(32,23,17,.06)}
    .story-card .thumb{height:140px;background:#e6dcd0 center/cover no-repeat}
    .story-card .body{padding:16px 18px;flex:1}
    .story-card h3{margin:0 0 6px;font:500 1.05rem var(--serif)}
    .story-card .meta{font-size:.68rem;color:var(--muted);letter-spacing:.06em;
      text-transform:uppercase;margin-bottom:8px}
    .story-card .excerpt{font-size:.78rem;color:var(--muted);line-height:1.5;
      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .story-card .actions{display:flex;gap:6px;padding:12px 18px;border-top:1px solid var(--line);
      background:#faf6ef}
    .story-card .actions .btn-sm-coffee{flex:1;justify-content:center}
    .toggle-badge{display:inline-block;padding:3px 8px;border-radius:999px;
      font-size:.58rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
    .toggle-badge.on{background:#e3f0e4;color:#2d5233}
    .toggle-badge.off{background:#f0eae0;color:#8a7b6a}

    /* Image upload preview */
    .hero-preview{
      width:100%;height:260px;
      background:#e6dcd0 center/cover no-repeat;
      border:1px solid var(--line);
      display:flex;align-items:center;justify-content:center;
      color:var(--muted);font-size:.8rem;margin-bottom:14px;
    }
    .hero-preview.has-img{color:transparent}
    .file-drop{
      border:2px dashed #cdb9a4;
      background:#fffdf9;
      padding:22px;
      text-align:center;
      cursor:pointer;
      transition:.2s;
      margin-bottom:14px;
    }
    .file-drop:hover{border-color:var(--tan);background:#fff8ef}
    .file-drop i{font-size:1.8rem;color:var(--tan);display:block;margin-bottom:8px}
    .file-drop small{color:var(--muted);font-size:.7rem;display:block;margin-top:4px}
    .file-drop input{display:none}

    /* Marquee preview */
    .marquee-preview{
      background:var(--coffee);
      color:#f3e7d9;
      padding:16px 22px;
      display:flex;flex-wrap:wrap;gap:16px;align-items:center;
      font:400 1.15rem var(--serif);
      border:1px solid var(--line);
    }
    .marquee-preview span.star{color:var(--tan);font-size:.95rem}

    /* Feature live preview */
    .feature-live-preview{
      position:relative;overflow:hidden;
      background:var(--coffee) center/cover no-repeat;
      color:#f4efe7;padding:46px 32px;
      border:1px solid var(--line);
    }
    .feature-live-preview::before{
      content:"";position:absolute;inset:0;
      background:linear-gradient(90deg,rgba(33,25,20,.85),rgba(33,25,20,.35));
      z-index:0;
    }
    .feature-live-preview > *{position:relative;z-index:1}
    .feature-live-preview .fp-eyebrow{
      font-size:.62rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;
      color:#d8b38d;margin-bottom:10px;
    }
    .feature-live-preview .fp-title{
      font:400 clamp(1.4rem,3vw,2rem)/1.15 var(--serif);letter-spacing:-.03em;margin:0 0 12px;
    }
    .feature-live-preview .fp-copy{
      font-size:.84rem;line-height:1.6;color:rgba(244,239,231,.85);margin:0 0 16px;max-width:520px;
    }
    .feature-live-preview .fp-btn{
      display:inline-flex;align-items:center;gap:8px;
      padding:10px 18px;border:1px solid var(--tan);color:var(--tan);
      font-size:.62rem;font-weight:700;letter-spacing:.13em;text-transform:uppercase;
    }

    /* Gallery cards */
    .gallery-grid-admin{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
    .gallery-card{background:var(--paper);border:1px solid var(--line);overflow:hidden;
      display:flex;flex-direction:column;transition:.2s}
    .gallery-card:hover{border-color:var(--tan);box-shadow:0 8px 24px rgba(32,23,17,.06)}
    .gallery-card .thumb{height:150px;background:#e6dcd0 center/cover no-repeat}
    .gallery-card .body{padding:12px 14px;flex:1}
    .gallery-card .alt{font-size:.76rem;color:var(--ink);line-height:1.4;margin-bottom:6px;
      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .gallery-card .meta{font-size:.62rem;color:var(--muted);letter-spacing:.06em;text-transform:uppercase}
    .gallery-card .actions{display:flex;gap:6px;padding:10px 14px;border-top:1px solid var(--line);
      background:#faf6ef}
    .gallery-card .actions .btn-sm-coffee{flex:1;justify-content:center}

    /* Values cards */
    .values-grid-admin{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
    .value-card-admin{background:var(--paper);border:1px solid var(--line);padding:22px;
      display:flex;flex-direction:column;gap:10px;transition:.2s}
    .value-card-admin:hover{border-color:var(--tan);box-shadow:0 8px 24px rgba(32,23,17,.06)}
    .value-card-admin .v-icon{font-size:1.8rem;color:var(--tan);line-height:1}
    .value-card-admin h3{margin:0;font:500 1.05rem var(--serif)}
    .value-card-admin p{margin:0;font-size:.8rem;color:var(--muted);line-height:1.5;flex:1}
    .value-card-admin .meta{display:flex;justify-content:space-between;align-items:center;
      font-size:.62rem;color:var(--muted);letter-spacing:.06em;text-transform:uppercase}
    .value-card-admin .actions{display:flex;gap:6px;padding-top:10px;border-top:1px solid var(--line)}
    .value-card-admin .actions .btn-sm-coffee{flex:1;justify-content:center}
    .value-preview-box{background:var(--cream);border:1px solid var(--line);padding:22px;
      display:flex;align-items:center;gap:14px;margin-top:6px}
    .value-preview-box .vp-icon{font-size:2rem;color:var(--tan);line-height:1}
    .value-preview-box .vp-title{font:500 1rem var(--serif);margin:0 0 2px}
    .value-preview-box .vp-desc{font-size:.76rem;color:var(--muted);margin:0}

    /* Quote live preview */
    .quote-live-preview{
      position:relative;background:var(--cream);border:1px solid var(--line);
      padding:48px 32px;text-align:center;
    }
    .quote-live-preview .q-mark{
      font:400 5rem/1 var(--serif);color:var(--tan);opacity:.4;margin-bottom:6px;
    }
    .quote-live-preview .q-text{
      font:400 clamp(1.2rem,2.4vw,1.6rem)/1.4 var(--serif);color:var(--ink);
      max-width:640px;margin:0 auto 14px;letter-spacing:-.01em;
    }
    .quote-live-preview .q-author{
      font-size:.68rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;
      color:var(--muted);
    }

    @media(max-width:900px){
      .admin-wrap{flex-direction:column}
      .admin-sidebar{width:100%;flex:0 0 auto;height:auto;position:static;padding:18px 0}
      .admin-brand{padding:0 20px 16px}
      .admin-nav{flex-direction:row;overflow-x:auto;padding:0 12px;gap:4px}
      .admin-nav .nav-label{display:none}
      .admin-nav a{white-space:nowrap;padding:10px 14px;font-size:.74rem}
      .admin-nav a i{display:none}
      .admin-sidebar-foot{padding:14px 20px 0;display:flex;align-items:center;gap:14px}
      .admin-sidebar-foot .who{margin:0;flex:1}
      .admin-sidebar-foot button{width:auto;padding:8px 14px}
      .admin-main{padding:22px 18px 40px}
    }
    @media(max-width:600px){
      .stat-card .value{font-size:1.9rem}
      .table{font-size:.76rem}
      .table thead th,.table tbody td{padding:10px 12px}
    }
  </style>
</head>
<body>
<div class="admin-wrap">

  <!-- ===== Sidebar ===== -->
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <span class="brand-mark">K</span>
      <div>
        café krunal<span style="color:#b58a62">.</span>
        <small>Admin Panel</small>
      </div>
    </div>

    <nav class="admin-nav">
      <div class="nav-label">Overview</div>
      <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-grid"></i> Dashboard
      </a>
      <div class="nav-label">Manage</div>
      <a href="?page=users" class="<?= $page === 'users' ? 'active' : '' ?>">
        <i class="bi bi-people"></i> Users
      </a>
      <a href="?page=reservations" class="<?= $page === 'reservations' ? 'active' : '' ?>">
        <i class="bi bi-calendar2-check"></i> Reservations
      </a>
      <div class="nav-label">Content</div>
      <a href="?page=hero" class="<?= $page === 'hero' ? 'active' : '' ?>">
        <i class="bi bi-card-image"></i> Hero Section
      </a>
      <a href="?page=marquee" class="<?= $page === 'marquee' ? 'active' : '' ?>">
        <i class="bi bi-textarea-t"></i> Marquee
      </a>
      <a href="?page=story" class="<?= $page === 'story' ? 'active' : '' ?>">
        <i class="bi bi-journal-text"></i> Our Story
      </a>
      <a href="?page=feature" class="<?= $page === 'feature' ? 'active' : '' ?>">
        <i class="bi bi-stars"></i> Feature
      </a>
      <a href="?page=values" class="<?= $page === 'values' ? 'active' : '' ?>">
        <i class="bi bi-heart"></i> Values
      </a>
      <a href="?page=gallery" class="<?= $page === 'gallery' ? 'active' : '' ?>">
        <i class="bi bi-images"></i> Gallery
      </a>
      <a href="?page=quote" class="<?= $page === 'quote' ? 'active' : '' ?>">
        <i class="bi bi-quote"></i> Quote
      </a>
      <a href="?page=menu" class="<?= $page === 'menu' ? 'active' : '' ?>">
        <i class="bi bi-cup-hot"></i> Menu
      </a>
      <div class="nav-label">Site</div>
      <a href="../index.php"><i class="bi bi-house"></i> View Website</a>
    </nav>

    <div class="admin-sidebar-foot">
      <div class="who">
        <div class="av"><?= e(mb_strtoupper(mb_substr($adminUser['full_name'], 0, 1))) ?></div>
        <div>
          <div class="nm"><?= e($adminUser['full_name']) ?></div>
          <div class="em"><?= e($adminUser['email']) ?></div>
        </div>
      </div>
      <form method="post" action="?action=logout" style="margin:0;">
        <input type="hidden" name="action" value="logout">
        <button type="submit" onclick="return confirm('Sign out of admin?');">
          <i class="bi bi-box-arrow-right"></i> Logout
        </button>
      </form>
    </div>
  </aside>

  <!-- ===== Main ===== -->
  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <div class="crumb">
          <i class="bi bi-<?= $page === 'hero' ? 'card-image' : ($page === 'story' ? 'journal-text' : ($page === 'marquee' ? 'textarea-t' : ($page === 'feature' ? 'stars' : ($page === 'values' ? 'heart' : ($page === 'gallery' ? 'images' : ($page === 'quote' ? 'quote' : ($page === 'menu' ? 'cup-hot' : ($page === 'users' ? 'people' : ($page === 'reservations' ? 'calendar2-check' : 'grid'))))))))) ?>"></i>
          Admin / <?= ucfirst($page) ?>
        </div>
        <h1>
          <?php if ($page === 'dashboard'): ?>Dashboard
          <?php elseif ($page === 'users'): ?>Users
          <?php elseif ($page === 'reservations'): ?>Reservations
          <?php elseif ($page === 'hero'): ?>Hero Section
          <?php elseif ($page === 'marquee'): ?>Marquee
          <?php elseif ($page === 'feature'): ?>Feature Section
          <?php elseif ($page === 'values'): ?>Values Section
          <?php elseif ($page === 'gallery'): ?>Gallery
          <?php elseif ($page === 'quote'): ?>Quote
          <?php elseif ($page === 'menu'): ?>Menu
          <?php else: ?>Our Story
          <?php endif; ?>
        </h1>
      </div>
      <?php if ($page === 'story' && !isset($_GET['edit'])): ?>
        <a href="?page=story&edit=new" class="btn-coffee">
          <i class="bi bi-plus-lg"></i> Add New Section
        </a>
      <?php endif; ?>
      <?php if ($page === 'menu' && !isset($_GET['edit_item'])): ?>
        <a href="?page=menu&edit_item=new" class="btn-coffee">
          <i class="bi bi-plus-lg"></i> Add Menu Item
        </a>
      <?php endif; ?>
      <?php if ($page === 'gallery' && !isset($_GET['edit_image'])): ?>
        <a href="?page=gallery&edit_image=new" class="btn-coffee">
          <i class="bi bi-plus-lg"></i> Add Gallery Image
        </a>
      <?php endif; ?>
      <?php if ($page === 'values' && !isset($_GET['edit_card'])): ?>
        <a href="?page=values&edit_card=new" class="btn-coffee">
          <i class="bi bi-plus-lg"></i> Add Value Card
        </a>
      <?php endif; ?>
    </div>

    <?php if ($flash): ?>
      <div class="flash <?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
    <?php endif; ?>

    <?php if ($page === 'dashboard'): ?>
      <!-- ===== DASHBOARD ===== -->
      <div class="stat-grid">
        <div class="stat-card">
          <span class="label">Total Users</span>
          <span class="value"><?= $totalUsers ?></span>
          <i class="bi bi-people icon"></i>
        </div>
        <div class="stat-card">
          <span class="label">Reservations</span>
          <span class="value"><?= $totalReservations ?></span>
          <i class="bi bi-calendar2-check icon"></i>
        </div>
        <div class="stat-card">
          <span class="label">Pending</span>
          <span class="value"><?= $pendingReservations ?></span>
          <i class="bi bi-hourglass-split icon"></i>
        </div>
        <div class="stat-card">
          <span class="label">Today's Bookings</span>
          <span class="value"><?= $todayReservations ?></span>
          <i class="bi bi-calendar-day icon"></i>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h2>Recent Users</h2>
          <a href="?page=users" class="btn-sm-coffee">View All <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="panel-body">
          <?php $recent = $pdo->query('SELECT id, full_name, email, created_at FROM users ORDER BY id DESC LIMIT 5')->fetchAll(); ?>
          <?php if ($recent): ?>
            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Joined</th></tr></thead>
                <tbody>
                  <?php foreach ($recent as $r): ?>
                    <tr>
                      <td><?= e($r['full_name']) ?></td>
                      <td><?= e($r['email']) ?></td>
                      <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state"><i class="bi bi-people"></i><p>No users yet.</p></div>
          <?php endif; ?>
        </div>
      </div>

    <?php elseif ($page === 'users'): ?>
      <!-- ===== USERS ===== -->
      <div class="panel">
        <div class="panel-head"><h2>All Users (<?= count($users) ?>)</h2></div>
        <div class="panel-body">
          <?php if ($users): ?>
            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th style="text-align:right;">Action</th></tr></thead>
                <tbody>
                  <?php foreach ($users as $u): ?>
                    <tr>
                      <td><?= (int)$u['id'] ?></td>
                      <td>
                        <strong><?= e($u['full_name']) ?></strong>
                        <?php if ((int)$u['id'] === (int)$adminUser['id']): ?>
                          <span class="badge-soft badge-confirmed" style="margin-left:6px;">You</span>
                        <?php endif; ?>
                      </td>
                      <td><?= e($u['email']) ?></td>
                      <td><?= e($u['phone'] ?: '—') ?></td>
                      <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                      <td style="text-align:right;">
                        <?php if ((int)$u['id'] !== (int)$adminUser['id']): ?>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" class="btn-sm-coffee btn-sm-danger">
                              <i class="bi bi-trash"></i> Delete
                            </button>
                          </form>
                        <?php else: ?>
                          <span style="color:var(--muted);font-size:.72rem;">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state"><i class="bi bi-people"></i><p>No users registered yet.</p></div>
          <?php endif; ?>
        </div>
      </div>

    <?php elseif ($page === 'reservations'): ?>
      <!-- ===== RESERVATIONS ===== -->
      <div class="panel">
        <div class="panel-head"><h2>All Reservations (<?= count($reservations) ?>)</h2></div>
        <div class="panel-body">
          <?php if ($reservations): ?>
            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>#</th><th>Guest</th><th>Phone</th><th>Date</th><th>Time</th><th>Guests</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($reservations as $r): ?>
                    <tr>
                      <td><?= (int)$r['id'] ?></td>
                      <td>
                        <strong><?= e($r['name']) ?></strong>
                        <?php if (!empty($r['email'])): ?>
                          <div style="color:var(--muted);font-size:.72rem;"><?= e($r['email']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($r['message'])): ?>
                          <div style="color:var(--muted);font-size:.68rem;font-style:italic;">“<?= e(mb_substr($r['message'], 0, 60)) ?>”</div>
                        <?php endif; ?>
                      </td>
                      <td><?= e($r['phone']) ?></td>
                      <td><?= date('M j, Y', strtotime($r['res_date'])) ?></td>
                      <td><?= e($r['res_time']) ?></td>
                      <td><?= (int)$r['guests'] ?></td>
                      <td><span class="badge-soft badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                      <td style="text-align:right;white-space:nowrap;">
                        <form method="post" style="display:inline;">
                          <input type="hidden" name="action" value="update_reservation">
                          <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                          <select name="status" onchange="this.form.submit()" class="btn-sm-coffee" style="padding:6px 10px;">
                            <option value="pending"   <?= $r['status'] === 'pending'   ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $r['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="cancelled" <?= $r['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                          </select>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this reservation?');">
                          <input type="hidden" name="action" value="delete_reservation">
                          <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                          <button type="submit" class="btn-sm-coffee btn-sm-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state"><i class="bi bi-calendar2-check"></i><p>No reservations yet.</p></div>
          <?php endif; ?>
        </div>
      </div>

    <?php elseif ($page === 'hero'): ?>
      <!-- ===== HERO ===== -->
      <?php if (!$hero): ?>
        <div class="panel"><div class="empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Hero section row missing. Run the SQL to create the <code>hero_section</code> table and seed a row.</p>
        </div></div>
      <?php else: ?>
        <div class="panel">
          <div class="panel-head">
            <h2>Edit Hero Section</h2>
            <span style="font-size:.7rem;color:var(--muted);">
              Updated <?= date('M j, Y', strtotime($hero['updated_at'])) ?>
            </span>
          </div>
          <div class="panel-body pad">
            <form method="post" action="?page=hero" enctype="multipart/form-data">
              <input type="hidden" name="action" value="hero_update">
              <input type="hidden" name="id" value="<?= (int)$hero['id'] ?>">

              <div class="row g-4">
                <div class="col-lg-7">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label" for="title_line1">Title — Line 1</label>
                      <input class="form-control" type="text" id="title_line1" name="title_line1"
                             value="<?= e($hero['title_line1']) ?>" maxlength="160" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="title_line2">Title — Line 2 (italic accent)</label>
                      <input class="form-control" type="text" id="title_line2" name="title_line2"
                             value="<?= e($hero['title_line2']) ?>" maxlength="160">
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="subtitle">Subtitle</label>
                      <textarea class="form-control" id="subtitle" name="subtitle" rows="4"
                                maxlength="500" required><?= e($hero['subtitle']) ?></textarea>
                    </div>
                  </div>
                </div>

                <div class="col-lg-5">
                  <label class="form-label">Hero Image</label>
                  <div class="hero-preview <?= !empty($hero['image_path']) ? 'has-img' : '' ?>"
                       id="heroPreview"
                       style="<?= !empty($hero['image_path']) ? "background-image:url('../" . e($hero['image_path']) . "');" : '' ?>">
                    <?= empty($hero['image_path']) ? 'No image uploaded yet' : '' ?>
                  </div>
                  <label class="file-drop" for="hero_image" style="display:block;">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <strong>Choose an image to upload</strong>
                    <small>JPG, PNG, WebP, or GIF — max 5 MB</small>
                    <input type="file" id="hero_image" name="hero_image"
                           accept="image/jpeg,image/png,image/webp,image/gif">
                  </label>
                  <div id="heroFileName" style="font-size:.72rem;color:var(--muted);margin-top:-6px;margin-bottom:10px;"></div>
                  <?php if (!empty($hero['image_path'])): ?>
                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:.8rem;color:#6e5d50;cursor:pointer;">
                      <input type="checkbox" name="remove_image" value="1"> Remove current image
                    </label>
                  <?php endif; ?>
                </div>

                <div class="col-12">
                  <button class="btn-coffee" type="submit">
                    <i class="bi bi-check2"></i> Save Hero Section
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>

    <?php elseif ($page === 'marquee'): ?>
      <!-- ===== MARQUEE ===== -->
      <?php if (!$marquee): ?>
        <div class="panel"><div class="empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Marquee row missing. Run the SQL to create the <code>marquee_section</code> table and seed a row.</p>
        </div></div>
      <?php else: ?>
        <div class="panel">
          <div class="panel-head">
            <h2>Edit Marquee Strip</h2>
            <span style="font-size:.7rem;color:var(--muted);">
              Updated <?= date('M j, Y', strtotime($marquee['updated_at'])) ?>
            </span>
          </div>
          <div class="panel-body pad">
            <form method="post" action="?page=marquee">
              <input type="hidden" name="action" value="marquee_update">
              <input type="hidden" name="id" value="<?= (int)$marquee['id'] ?>">

              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label" for="marquee_items">
                    Phrases (separate each phrase with a <code>|</code>)
                  </label>
                  <textarea class="form-control" id="marquee_items" name="items" rows="4"
                            placeholder="Freshly brewed|Thoughtfully made|Always welcoming|Stay for another cup"
                            required><?= e($marquee['items']) ?></textarea>
                  <small style="color:var(--muted);font-size:.7rem;display:block;margin-top:6px;">
                    Each phrase is separated by a <code>|</code>. Spaces around each phrase are trimmed automatically.
                  </small>
                </div>

                <div class="col-12">
                  <label class="form-label">Preview</label>
                  <div class="marquee-preview">
                    <?php
                    $previewItems = array_filter(array_map('trim', explode('|', $marquee['items'])));
                    foreach ($previewItems as $i => $p):
                      if ($i > 0): ?><span class="star">✳</span><?php endif; ?>
                      <span><?= e($p) ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div class="col-12">
                  <button class="btn-coffee" type="submit">
                    <i class="bi bi-check2"></i> Save Marquee
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>

    <?php elseif ($page === 'story'): ?>
      <!-- ===== OUR STORY ===== -->
      <?php if (isset($_GET['edit'])): ?>
        <?php
        $isNew = $_GET['edit'] === 'new';
        $s = $editingStory ?: [
            'id' => 0, 'eyebrow' => '', 'title_line1' => '', 'title_line2' => '',
            'paragraph1' => '', 'paragraph2' => '',
            'button_text' => 'Come say hello', 'button_link' => '#visit',
            'image_path' => '',
            'stamp_text' => 'Made with care & good coffee', 'is_active' => 1,
        ];
        ?>
        <div class="panel">
          <div class="panel-head">
            <h2><?= $isNew ? 'Add New Story Section' : 'Edit Story Section' ?></h2>
            <a href="?page=story" class="btn-sm-coffee"><i class="bi bi-arrow-left"></i> Back to list</a>
          </div>
          <div class="panel-body pad">
            <form method="post" action="?page=story" enctype="multipart/form-data">
              <input type="hidden" name="action" value="<?= $isNew ? 'story_insert' : 'story_update' ?>">
              <?php if (!$isNew): ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><?php endif; ?>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="eyebrow">Eyebrow (small label)</label>
                  <input class="form-control" type="text" id="eyebrow" name="eyebrow"
                         value="<?= e($s['eyebrow']) ?>" maxlength="120">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="stamp_text">Round Stamp Text</label>
                  <input class="form-control" type="text" id="stamp_text" name="stamp_text"
                         value="<?= e($s['stamp_text']) ?>" maxlength="160">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="title_line1">Title — Line 1</label>
                  <input class="form-control" type="text" id="title_line1" name="title_line1"
                         value="<?= e($s['title_line1']) ?>" maxlength="160" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="title_line2">Title — Line 2</label>
                  <input class="form-control" type="text" id="title_line2" name="title_line2"
                         value="<?= e($s['title_line2']) ?>" maxlength="160">
                </div>
                <div class="col-12">
                  <label class="form-label" for="paragraph1">Paragraph 1</label>
                  <textarea class="form-control" id="paragraph1" name="paragraph1" rows="3"><?= e($s['paragraph1']) ?></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label" for="paragraph2">Paragraph 2</label>
                  <textarea class="form-control" id="paragraph2" name="paragraph2" rows="3"><?= e($s['paragraph2']) ?></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="button_text">Button Text</label>
                  <input class="form-control" type="text" id="button_text" name="button_text"
                         value="<?= e($s['button_text']) ?>" maxlength="120">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="button_link">Button Link</label>
                  <input class="form-control" type="text" id="button_link" name="button_link"
                         value="<?= e($s['button_link']) ?>" maxlength="255">
                </div>

                <div class="col-12">
                  <label class="form-label">Story Image</label>
                  <div class="hero-preview <?= !empty($s['image_path']) ? 'has-img' : '' ?>"
                       id="storyPreview"
                       style="<?= !empty($s['image_path']) ? "background-image:url('../" . e($s['image_path']) . "');" : '' ?>">
                    <?= empty($s['image_path']) ? 'No image uploaded yet' : '' ?>
                  </div>
                  <label class="file-drop" for="story_image" style="display:block;">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <strong>Choose an image to upload</strong>
                    <small>JPG, PNG, WebP, or GIF — max 5 MB</small>
                    <input type="file" id="story_image" name="story_image"
                           accept="image/jpeg,image/png,image/webp,image/gif">
                  </label>
                  <div id="storyFileName" style="font-size:.72rem;color:var(--muted);margin-top:-6px;margin-bottom:10px;"></div>
                  <?php if (!empty($s['image_path'])): ?>
                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:.8rem;color:#6e5d50;cursor:pointer;">
                      <input type="checkbox" name="remove_image" value="1"> Remove current image
                    </label>
                  <?php endif; ?>
                </div>

                <div class="col-12">
                  <label style="display:inline-flex;align-items:center;gap:9px;font-size:.82rem;color:#6e5d50;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($s['is_active']) ? 'checked' : '' ?>>
                    Active (show on homepage)
                  </label>
                </div>
                <div class="col-12" style="display:flex;gap:10px;margin-top:8px;">
                  <button class="btn-coffee" type="submit">
                    <i class="bi bi-check2"></i> <?= $isNew ? 'Create Section' : 'Update Section' ?>
                  </button>
                  <a href="?page=story" class="btn-sm-coffee" style="padding:12px 22px;">Cancel</a>
                </div>
              </div>
            </form>
          </div>
        </div>

      <?php else: ?>
        <?php if ($stories): ?>
          <div class="story-grid">
            <?php foreach ($stories as $s): ?>
              <div class="story-card">
                <div class="thumb" style="background-image:url('<?= !empty($s['image_path']) ? '../' . e($s['image_path']) : 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=600&q=70' ?>');"></div>
                <div class="body">
                  <div class="meta">
                    <?= e($s['eyebrow'] ?: 'No eyebrow') ?>
                    <span class="toggle-badge <?= !empty($s['is_active']) ? 'on' : 'off' ?>" style="margin-left:6px;">
                      <?= !empty($s['is_active']) ? 'Active' : 'Inactive' ?>
                    </span>
                  </div>
                  <h3><?= e($s['title_line1']) ?> <?= e($s['title_line2']) ?></h3>
                  <p class="excerpt"><?= e(mb_substr($s['paragraph1'], 0, 120)) ?>…</p>
                </div>
                <div class="actions">
                  <a href="?page=story&edit=<?= (int)$s['id'] ?>" class="btn-sm-coffee">
                    <i class="bi bi-pencil"></i> Edit
                  </a>
                  <form method="post" action="?page=story" style="flex:1;margin:0;"
                        onsubmit="return confirm('Delete this story section?');">
                    <input type="hidden" name="action" value="story_delete">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn-sm-coffee btn-sm-danger" style="width:100%;justify-content:center;">
                      <i class="bi bi-trash"></i> Delete
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="panel">
            <div class="empty-state">
              <i class="bi bi-journal-text"></i>
              <p>No story sections yet. Click <strong>Add New Section</strong> to create one.</p>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

    <?php elseif ($page === 'feature'): ?>
      <!-- ===== FEATURE ===== -->
      <?php if (!$feature): ?>
        <div class="panel"><div class="empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Feature section row missing. Run the SQL to create the <code>feature_section</code> table and seed a row.</p>
        </div></div>
      <?php else: ?>
        <div class="panel">
          <div class="panel-head">
            <h2>Edit Feature Section</h2>
            <span style="font-size:.7rem;color:var(--muted);">
              Updated <?= date('M j, Y', strtotime($feature['updated_at'])) ?>
            </span>
          </div>
          <div class="panel-body pad">
            <form method="post" action="?page=feature" enctype="multipart/form-data">
              <input type="hidden" name="action" value="feature_update">
              <input type="hidden" name="id" value="<?= (int)$feature['id'] ?>">

              <div class="row g-4">
                <div class="col-lg-7">
                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label" for="eyebrow">Eyebrow</label>
                      <input class="form-control" type="text" id="eyebrow" name="eyebrow"
                             value="<?= e($feature['eyebrow']) ?>" maxlength="120">
                    </div>
                    <div class="col-md-8">
                      <label class="form-label" for="title_line1">Title — Line 1</label>
                      <input class="form-control" type="text" id="title_line1" name="title_line1"
                             value="<?= e($feature['title_line1']) ?>" maxlength="160" required>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label" for="title_line2_color">Accent Color</label>
                      <input class="form-control" type="color" id="title_line2_color" name="title_line2_color"
                             value="<?= e($feature['title_line2_color'] ?: '#d8b38d') ?>">
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="title_line2">Title — Line 2 (italic accent)</label>
                      <input class="form-control" type="text" id="title_line2" name="title_line2"
                             value="<?= e($feature['title_line2']) ?>" maxlength="160">
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="description">Description</label>
                      <textarea class="form-control" id="description" name="description" rows="3"
                                maxlength="500"><?= e($feature['description']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="cta_text">CTA Button Text</label>
                      <input class="form-control" type="text" id="cta_text" name="cta_text"
                             value="<?= e($feature['cta_text']) ?>" maxlength="120">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="cta_link">CTA Link</label>
                      <input class="form-control" type="text" id="cta_link" name="cta_link"
                             value="<?= e($feature['cta_link']) ?>" maxlength="255">
                    </div>
                  </div>
                </div>

                <div class="col-lg-5">
                  <label class="form-label">Background Image</label>
                  <div class="hero-preview <?= !empty($feature['bg_image_path']) ? 'has-img' : '' ?>"
                       id="featurePreview"
                       style="<?= !empty($feature['bg_image_path']) ? "background-image:url('../" . e($feature['bg_image_path']) . "');" : '' ?>">
                    <?= empty($feature['bg_image_path']) ? 'No background image uploaded yet' : '' ?>
                  </div>
                  <label class="file-drop" for="feature_bg" style="display:block;">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <strong>Choose a background image</strong>
                    <small>JPG, PNG, WebP, or GIF — max 5 MB</small>
                    <input type="file" id="feature_bg" name="feature_bg"
                           accept="image/jpeg,image/png,image/webp,image/gif">
                  </label>
                  <div id="featureFileName" style="font-size:.72rem;color:var(--muted);margin-top:-6px;margin-bottom:10px;"></div>
                  <?php if (!empty($feature['bg_image_path'])): ?>
                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:.8rem;color:#6e5d50;cursor:pointer;">
                      <input type="checkbox" name="remove_image" value="1"> Remove current image
                    </label>
                  <?php endif; ?>
                </div>

                <div class="col-12">
                  <label class="form-label">Live Preview</label>
                  <div class="feature-live-preview" id="featureLivePreview"
                       style="<?= !empty($feature['bg_image_path']) ? "background-image:url('../" . e($feature['bg_image_path']) . "');" : '' ?>">
                    <div class="fp-eyebrow" id="fpEyebrow"><?= e($feature['eyebrow']) ?></div>
                    <h3 class="fp-title">
                      <span id="fpTitle1"><?= e($feature['title_line1']) ?></span><br>
                      <em id="fpTitle2" style="color:<?= e($feature['title_line2_color'] ?: '#d8b38d') ?>"><?= e($feature['title_line2']) ?></em>
                    </h3>
                    <p class="fp-copy" id="fpCopy"><?= e($feature['description']) ?></p>
                    <span class="fp-btn" id="fpBtn">
                      <?= e($feature['cta_text']) ?> <i class="bi bi-arrow-up-right"></i>
                    </span>
                  </div>
                </div>

                <div class="col-12">
                  <label style="display:inline-flex;align-items:center;gap:9px;font-size:.82rem;color:#6e5d50;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($feature['is_active']) ? 'checked' : '' ?>>
                    Active (show on homepage)
                  </label>
                </div>

                <div class="col-12">
                  <button class="btn-coffee" type="submit">
                    <i class="bi bi-check2"></i> Save Feature Section
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>

    <?php elseif ($page === 'values'): ?>
      <!-- ===== VALUES ===== -->
      <?php if (!$valuesSection): ?>
        <div class="panel"><div class="empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Values section row missing. Run the SQL to create the <code>values_section</code> table and seed a row.</p>
        </div></div>
      <?php else: ?>

        <!-- Section header -->
        <div class="panel">
          <div class="panel-head">
            <h2>Values Section Header</h2>
            <span style="font-size:.7rem;color:var(--muted);">
              Updated <?= date('M j, Y', strtotime($valuesSection['updated_at'])) ?>
            </span>
          </div>
          <div class="panel-body pad">
            <form method="post" action="?page=values">
              <input type="hidden" name="action" value="values_section_update">
              <input type="hidden" name="id" value="<?= (int)$valuesSection['id'] ?>">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="eyebrow">Eyebrow</label>
                  <input class="form-control" type="text" id="eyebrow" name="eyebrow"
                         value="<?= e($valuesSection['eyebrow']) ?>" maxlength="120">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="title">Section Title</label>
                  <input class="form-control" type="text" id="title" name="title"
                         value="<?= e($valuesSection['title']) ?>" maxlength="160">
                </div>
                <div class="col-12">
                  <label style="display:inline-flex;align-items:center;gap:9px;font-size:.82rem;color:#6e5d50;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($valuesSection['is_active']) ? 'checked' : '' ?>>
                    Active (show on homepage)
                  </label>
                </div>
                <div class="col-12">
                  <button class="btn-coffee" type="submit"><i class="bi bi-check2"></i> Save Header</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Cards list -->
        <div class="panel">
          <div class="panel-head">
            <h2>Value Cards (<?= count($valuesCards) ?>)</h2>
          </div>
          <div class="panel-body pad">
            <?php if ($valuesCards): ?>
              <div class="values-grid-admin">
                <?php foreach ($valuesCards as $vc): ?>
                  <div class="value-card-admin">
                    <div class="v-icon"><i class="bi <?= e($vc['icon_class']) ?>"></i></div>
                    <h3><?= e($vc['title']) ?></h3>
                    <p><?= e($vc['description']) ?></p>
                    <div class="meta">
                      <span>Order <?= (int)$vc['sort_order'] ?></span>
                      <span class="toggle-badge <?= !empty($vc['is_active']) ? 'on' : 'off' ?>">
                        <?= !empty($vc['is_active']) ? 'Active' : 'Inactive' ?>
                      </span>
                    </div>
                    <div class="actions">
                      <a href="?page=values&edit_card=<?= (int)$vc['id'] ?>" class="btn-sm-coffee">
                        <i class="bi bi-pencil"></i> Edit
                      </a>
                      <form method="post" action="?page=values" style="flex:1;margin:0;"
                            onsubmit="return confirm('Delete this value card?');">
                        <input type="hidden" name="action" value="values_card_delete">
                        <input type="hidden" name="id" value="<?= (int)$vc['id'] ?>">
                        <button type="submit" class="btn-sm-coffee btn-sm-danger" style="width:100%;justify-content:center;">
                          <i class="bi bi-trash"></i> Delete
                        </button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-state"><i class="bi bi-heart"></i><p>No value cards yet.</p></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Add/Edit card form -->
        <?php if (isset($_GET['edit_card'])): ?>
          <?php
          $isNewCard = $_GET['edit_card'] === 'new';
          $vc = $editingValueCard ?: [
              'id' => 0, 'icon_class' => 'bi-cup-hot', 'title' => '',
              'description' => '', 'is_active' => 1, 'sort_order' => 0,
          ];
          ?>
          <div class="panel">
            <div class="panel-head">
              <h2><?= $isNewCard ? 'Add Value Card' : 'Edit Value Card' ?></h2>
              <a href="?page=values" class="btn-sm-coffee"><i class="bi bi-arrow-left"></i> Back to list</a>
            </div>
            <div class="panel-body pad">
              <form method="post" action="?page=values">
                <input type="hidden" name="action" value="<?= $isNewCard ? 'values_card_insert' : 'values_card_update' ?>">
                <?php if (!$isNewCard): ?><input type="hidden" name="id" value="<?= (int)$vc['id'] ?>"><?php endif; ?>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label" for="icon_class">Icon Class (Bootstrap Icons)</label>
                    <input class="form-control" type="text" id="icon_class" name="icon_class"
                           value="<?= e($vc['icon_class']) ?>" maxlength="80"
                           placeholder="bi-cup-hot" required>
                    <small style="color:var(--muted);font-size:.7rem;display:block;margin-top:6px;">
                      Examples: <code>bi-cup-hot</code>, <code>bi-flower1</code>, <code>bi-heart</code>,
                      <code>bi-star</code>, <code>bi-sun</code>, <code>bi-tree</code>
                    </small>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input class="form-control" type="number" id="sort_order" name="sort_order"
                           value="<?= (int)$vc['sort_order'] ?>" min="0">
                  </div>
                  <div class="col-md-3 d-flex align-items-end">
                    <label style="display:inline-flex;align-items:center;gap:9px;font-size:.82rem;color:#6e5d50;cursor:pointer;">
                      <input type="checkbox" name="is_active" value="1" <?= !empty($vc['is_active']) ? 'checked' : '' ?>>
                      Active
                    </label>
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="title">Card Title</label>
                    <input class="form-control" type="text" id="title" name="title"
                           value="<?= e($vc['title']) ?>" maxlength="160"
                           placeholder="Thoughtful coffee" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="description">Card Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"
                              placeholder="Good beans, careful brewing and a little love in every cup."><?= e($vc['description']) ?></textarea>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Live Preview</label>
                    <div class="value-preview-box">
                      <div class="vp-icon"><i class="bi <?= e($vc['icon_class']) ?>" id="vpIcon"></i></div>
                      <div>
                        <h4 class="vp-title" id="vpTitle"><?= e($vc['title'] ?: 'Card title') ?></h4>
                        <p class="vp-desc" id="vpDesc"><?= e($vc['description'] ?: 'Card description') ?></p>
                      </div>
                    </div>
                  </div>

                  <div class="col-12" style="display:flex;gap:10px;margin-top:8px;">
                    <button class="btn-coffee" type="submit">
                      <i class="bi bi-check2"></i> <?= $isNewCard ? 'Create Card' : 'Update Card' ?>
                    </button>
                    <a href="?page=values" class="btn-sm-coffee" style="padding:12px 22px;">Cancel</a>
                  </div>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>

      <?php endif; ?>

    <?php elseif ($page === 'quote'): ?>
      <!-- ===== QUOTE ===== -->
      <?php if (!$quote): ?>
        <div class="panel"><div class="empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Quote section row missing. Run the SQL to create the <code>quote_section</code> table and seed a row.</p>
        </div></div>
      <?php else: ?>
        <div class="panel">
          <div class="panel-head">
            <h2>Edit Quote Section</h2>
            <span style="font-size:.7rem;color:var(--muted);">
              Updated <?= date('M j, Y', strtotime($quote['updated_at'])) ?>
            </span>
          </div>
          <div class="panel-body pad">
            <form method="post" action="?page=quote">
              <input type="hidden" name="action" value="quote_update">
              <input type="hidden" name="id" value="<?= (int)$quote['id'] ?>">

              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label" for="quote_text">Quote Text</label>
                  <textarea class="form-control" id="quote_text" name="quote_text" rows="3"
                            maxlength="500" required
                            placeholder="Sometimes all you need is a good cup of coffee and a place that feels like yours."><?= e($quote['quote_text']) ?></textarea>
                  <small style="color:var(--muted);font-size:.7rem;display:block;margin-top:6px;">
                    The decorative <code>“ ”</code> quotation marks are added automatically on the homepage — enter only the plain quote here.
                  </small>
                </div>
                <div class="col-12">
                  <label class="form-label" for="author">Author / Attribution</label>
                  <input class="form-control" type="text" id="author" name="author"
                         value="<?= e($quote['author']) ?>" maxlength="255"
                         placeholder="Your table is waiting at Café Krunal">
                </div>

                <div class="col-12">
                  <label class="form-label">Live Preview</label>
                  <div class="quote-live-preview">
                    <div class="q-mark">“</div>
                    <p class="q-text" id="qText"><?= e($quote['quote_text']) ?></p>
                    <div class="q-author" id="qAuthor"><?= e($quote['author']) ?></div>
                  </div>
                </div>

                <div class="col-12">
                  <label style="display:inline-flex;align-items:center;gap:9px;font-size:.82rem;color:#6e5d50;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($quote['is_active']) ? 'checked' : '' ?>>
                    Active (show on homepage)
                  </label>
                </div>

                <div class="col-12">
                  <button class="btn-coffee" type="submit">
                    <i class="bi bi-check2"></i> Save Quote Section
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>

    <?php elseif ($page === 'gallery'): ?>
      <!-- ===== GALLERY ===== -->
      <?php if (!$gallerySection): ?>
        <div class="panel"><div class="empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Gallery section row missing. Run the SQL to create the <code>gallery_section</code> table and seed a row.</p>
        </div></div>
      <?php else: ?>

        <!-- Section header -->
        <div class="panel">
          <div class="panel-head">
            <h2>Gallery Section Header</h2>
            <span style="font-size:.7rem;color:var(--muted);">
              Updated <?= date('M j, Y', strtotime($gallerySection['updated_at'])) ?>
            </span>
          </div>
          <div class="panel-body pad">
            <form method="post" action="?page=gallery">
              <input type="hidden" name="action" value="gallery_section_update">
              <input type="hidden" name="id" value="<?= (int)$gallerySection['id'] ?>">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="eyebrow">Eyebrow</label>
                  <input class="form-control" type="text" id="eyebrow" name="eyebrow"
                         value="<?= e($gallerySection['eyebrow']) ?>" maxlength="120">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="title_line1">Title — Line 1</label>
                  <input class="form-control" type="text" id="title_line1" name="title_line1"
                         value="<?= e($gallerySection['title_line1']) ?>" maxlength="160">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="title_line2">Title — Line 2</label>
                  <input class="form-control" type="text" id="title_line2" name="title_line2"
                         value="<?= e($gallerySection['title_line2']) ?>" maxlength="160">
                </div>
                <div class="col-12">
                  <label class="form-label" for="section_copy">Section Copy</label>
                  <textarea class="form-control" id="section_copy" name="section_copy" rows="3"><?= e($gallerySection['section_copy']) ?></textarea>
                </div>
                <div class="col-12">
                  <label style="display:inline-flex;align-items:center;gap:9px;font-size:.82rem;color:#6e5d50;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($gallerySection['is_active']) ? 'checked' : '' ?>>
                    Active (show on homepage)
                  </label>
                </div>
                <div class="col-12">
                  <button class="btn-coffee" type="submit"><i class="bi bi-check2"></i> Save Header</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Images list -->
        <div class="panel">
          <div class="panel-head">
            <h2>Gallery Images (<?= count($galleryImages) ?>)</h2>
          </div>
          <div class="panel-body pad">
            <?php if ($galleryImages): ?>
              <div class="gallery-grid-admin">
                <?php foreach ($galleryImages as $gi): ?>
                  <div class="gallery-card">
                    <div class="thumb" style="background-image:url('<?= e($gi['image_path']) ?>');"></div>
                    <div class="body">
                      <div class="alt"><?= e($gi['alt_text'] ?: 'No alt text') ?></div>
                      <div class="meta">
                        Order <?= (int)$gi['sort_order'] ?> · 
                        <span class="toggle-badge <?= !empty($gi['is_active']) ? 'on' : 'off' ?>">
                          <?= !empty($gi['is_active']) ? 'Active' : 'Inactive' ?>
                        </span>
                      </div>
                    </div>
                    <div class="actions">
                      <a href="?page=gallery&edit_image=<?= (int)$gi['id'] ?>" class="btn-sm-coffee">
                        <i class="bi bi-pencil"></i> Edit
                      </a>
                      <form method="post" action="?page=gallery" style="flex:1;margin:0;"
                            onsubmit="return confirm('Delete this gallery image?');">
                        <input type="hidden" name="action" value="gallery_image_delete">
                        <input type="hidden" name="id" value="<?= (int)$gi['id'] ?>">
                        <button type="submit" class="btn-sm-coffee btn-sm-danger" style="width:100%;justify-content:center;">
                          <i class="bi bi-trash"></i> Delete
                        </button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-state"><i class="bi bi-images"></i><p>No gallery images yet.</p></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Add/Edit image form -->
        <?php if (isset($_GET['edit_image'])): ?>
          <?php
          $isNewGallery = $_GET['edit_image'] === 'new';
          $gi = $editingGalleryImage ?: [
              'id' => 0, 'image_path' => '', 'full_image_path' => '',
              'alt_text' => '', 'is_active' => 1, 'sort_order' => 0,
          ];
          ?>
          <div class="panel">
            <div class="panel-head">
              <h2><?= $isNewGallery ? 'Add Gallery Image' : 'Edit Gallery Image' ?></h2>
              <a href="?page=gallery" class="btn-sm-coffee"><i class="bi bi-arrow-left"></i> Back to list</a>
            </div>
            <div class="panel-body pad">
              <form method="post" action="?page=gallery" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $isNewGallery ? 'gallery_image_insert' : 'gallery_image_update' ?>">
                <?php if (!$isNewGallery): ?><input type="hidden" name="id" value="<?= (int)$gi['id'] ?>"><?php endif; ?>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label" for="alt_text">Alt Text (description)</label>
                    <input class="form-control" type="text" id="alt_text" name="alt_text"
                           value="<?= e($gi['alt_text']) ?>" maxlength="255"
                           placeholder="Warm and inviting café interior">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input class="form-control" type="number" id="sort_order" name="sort_order"
                           value="<?= (int)$gi['sort_order'] ?>" min="0">
                  </div>
                  <div class="col-md-3 d-flex align-items-end">
                    <label style="display:inline-flex;align-items:center;gap:9px;font-size:.82rem;color:#6e5d50;cursor:pointer;">
                      <input type="checkbox" name="is_active" value="1" <?= !empty($gi['is_active']) ? 'checked' : '' ?>>
                      Active
                    </label>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Thumbnail Image (grid)</label>
                    <div class="hero-preview <?= !empty($gi['image_path']) ? 'has-img' : '' ?>"
                         id="galleryThumbPreview"
                         style="height:180px;<?= !empty($gi['image_path']) ? "background-image:url('" . e($gi['image_path']) . "');" : '' ?>">
                      <?= empty($gi['image_path']) ? 'No thumbnail uploaded' : '' ?>
                    </div>
                    <label class="file-drop" for="gallery_image" style="display:block;">
                      <i class="bi bi-cloud-arrow-up"></i>
                      <strong>Upload thumbnail</strong>
                      <small>JPG, PNG, WebP, GIF — max 5 MB</small>
                      <input type="file" id="gallery_image" name="gallery_image"
                             accept="image/jpeg,image/png,image/webp,image/gif">
                    </label>
                    <?php if (!empty($gi['image_path'])): ?>
                      <label style="display:inline-flex;align-items:center;gap:8px;font-size:.8rem;color:#6e5d50;cursor:pointer;">
                        <input type="checkbox" name="remove_image" value="1"> Remove current thumbnail
                      </label>
                    <?php endif; ?>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Full Image (lightbox, optional)</label>
                    <div class="hero-preview <?= !empty($gi['full_image_path']) ? 'has-img' : '' ?>"
                         id="galleryFullPreview"
                         style="height:180px;<?= !empty($gi['full_image_path']) ? "background-image:url('" . e($gi['full_image_path']) . "');" : '' ?>">
                      <?= empty($gi['full_image_path']) ? 'No full image uploaded (uses thumbnail)' : '' ?>
                    </div>
                    <label class="file-drop" for="gallery_full_image" style="display:block;">
                      <i class="bi bi-cloud-arrow-up"></i>
                      <strong>Upload full-size image</strong>
                      <small>JPG, PNG, WebP, GIF — max 8 MB</small>
                      <input type="file" id="gallery_full_image" name="gallery_full_image"
                             accept="image/jpeg,image/png,image/webp,image/gif">
                    </label>
                    <?php if (!empty($gi['full_image_path'])): ?>
                      <label style="display:inline-flex;align-items:center;gap:8px;font-size:.8rem;color:#6e5d50;cursor:pointer;">
                        <input type="checkbox" name="remove_full_image" value="1"> Remove full image
                      </label>
                    <?php endif; ?>
                  </div>

                  <div class="col-12" style="display:flex;gap:10px;margin-top:8px;">
                    <button class="btn-coffee" type="submit">
                      <i class="bi bi-check2"></i> <?= $isNewGallery ? 'Add Image' : 'Update Image' ?>
                    </button>
                    <a href="?page=gallery" class="btn-sm-coffee" style="padding:12px 22px;">Cancel</a>
                  </div>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>

      <?php endif; ?>

    <?php elseif ($page === 'menu'): ?>
      <!-- ===== MENU ===== -->
      <?php if (!$menuSection): ?>
        <div class="panel"><div class="empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Menu section row missing. Run the SQL to create the <code>menu_section</code> table and seed a row.</p>
        </div></div>
      <?php else: ?>

        <!-- Section header -->
        <div class="panel">
          <div class="panel-head">
            <h2>Menu Section Header</h2>
            <span style="font-size:.7rem;color:var(--muted);">
              Updated <?= date('M j, Y', strtotime($menuSection['updated_at'])) ?>
            </span>
          </div>
          <div class="panel-body pad">
            <form method="post" action="?page=menu">
              <input type="hidden" name="action" value="menu_section_update">
              <input type="hidden" name="id" value="<?= (int)$menuSection['id'] ?>">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="eyebrow">Eyebrow</label>
                  <input class="form-control" type="text" id="eyebrow" name="eyebrow"
                         value="<?= e($menuSection['eyebrow']) ?>" maxlength="120">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="title_line1">Title — Line 1</label>
                  <input class="form-control" type="text" id="title_line1" name="title_line1"
                         value="<?= e($menuSection['title_line1']) ?>" maxlength="160">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="title_line2">Title — Line 2</label>
                  <input class="form-control" type="text" id="title_line2" name="title_line2"
                         value="<?= e($menuSection['title_line2']) ?>" maxlength="160">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="footnote">Footnote</label>
                  <input class="form-control" type="text" id="footnote" name="footnote"
                         value="<?= e($menuSection['footnote']) ?>" maxlength="255">
                </div>
                <div class="col-12">
                  <label class="form-label" for="section_copy">Section Copy</label>
                  <textarea class="form-control" id="section_copy" name="section_copy" rows="3"><?= e($menuSection['section_copy']) ?></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="cta_text">CTA Button Text</label>
                  <input class="form-control" type="text" id="cta_text" name="cta_text"
                         value="<?= e($menuSection['cta_text']) ?>" maxlength="120">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="cta_link">CTA Link</label>
                  <input class="form-control" type="text" id="cta_link" name="cta_link"
                         value="<?= e($menuSection['cta_link']) ?>" maxlength="255">
                </div>
                <div class="col-12">
                  <label style="display:inline-flex;align-items:center;gap:9px;font-size:.82rem;color:#6e5d50;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($menuSection['is_active']) ? 'checked' : '' ?>>
                    Active (show on homepage)
                  </label>
                </div>
                <div class="col-12">
                  <button class="btn-coffee" type="submit"><i class="bi bi-check2"></i> Save Menu Header</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Items list -->
        <div class="panel">
          <div class="panel-head">
            <h2>Menu Items (<?= count($menuItems) ?>)</h2>
          </div>
          <div class="panel-body">
            <?php if ($menuItems): ?>
              <div class="table-responsive">
                <table class="table">
                  <thead><tr><th>#</th><th>Category</th><th>Item</th><th>Price</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
                  <tbody>
                    <?php foreach ($menuItems as $mi): ?>
                      <tr>
                        <td><?= (int)$mi['id'] ?></td>
                        <td><?= e($mi['category_label']) ?></td>
                        <td>
                          <strong><?= e($mi['name']) ?></strong>
                          <?php if (!empty($mi['description'])): ?>
                            <div style="color:var(--muted);font-size:.7rem;"><?= e(mb_substr($mi['description'], 0, 80)) ?></div>
                          <?php endif; ?>
                        </td>
                        <td>$<?= number_format((float)$mi['price'], 2) ?></td>
                        <td>
                          <span class="toggle-badge <?= !empty($mi['is_active']) ? 'on' : 'off' ?>">
                            <?= !empty($mi['is_active']) ? 'Active' : 'Inactive' ?>
                          </span>
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                          <a href="?page=menu&edit_item=<?= (int)$mi['id'] ?>" class="btn-sm-coffee">
                            <i class="bi bi-pencil"></i> Edit
                          </a>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this menu item?');">
                            <input type="hidden" name="action" value="menu_item_delete">
                            <input type="hidden" name="id" value="<?= (int)$mi['id'] ?>">
                            <button type="submit" class="btn-sm-coffee btn-sm-danger">
                              <i class="bi bi-trash"></i>
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="empty-state"><i class="bi bi-cup-hot"></i><p>No menu items yet.</p></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Add/Edit form -->
        <?php if (isset($_GET['edit_item'])): ?>
          <?php
          $isNewItem = $_GET['edit_item'] === 'new';
          $mi = $editingMenuItem ?: [
              'id' => 0, 'category_id' => 0, 'name' => '', 'description' => '',
              'price' => 0, 'image_path' => '', 'is_active' => 1, 'sort_order' => 0,
          ];
          ?>
          <div class="panel">
            <div class="panel-head">
              <h2><?= $isNewItem ? 'Add Menu Item' : 'Edit Menu Item' ?></h2>
              <a href="?page=menu" class="btn-sm-coffee"><i class="bi bi-arrow-left"></i> Back to list</a>
            </div>
            <div class="panel-body pad">
              <form method="post" action="?page=menu" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $isNewItem ? 'menu_item_insert' : 'menu_item_update' ?>">
                <?php if (!$isNewItem): ?><input type="hidden" name="id" value="<?= (int)$mi['id'] ?>"><?php endif; ?>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label" for="category_id">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                      <option value="">— Select category —</option>
                      <?php foreach ($menuCategories as $mc): ?>
                        <option value="<?= (int)$mc['id'] ?>" <?= (int)$mc['id'] === (int)$mi['category_id'] ? 'selected' : '' ?>>
                          <?= e($mc['label']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label" for="price">Price ($)</label>
                    <input class="form-control" type="number" step="0.01" id="price" name="price"
                           value="<?= e((string)$mi['price']) ?>" min="0" required>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input class="form-control" type="number" id="sort_order" name="sort_order"
                           value="<?= (int)$mi['sort_order'] ?>" min="0">
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="name">Item Name</label>
                    <input class="form-control" type="text" id="name" name="name"
                           value="<?= e($mi['name']) ?>" maxlength="160" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"
                              maxlength="500"><?= e($mi['description']) ?></textarea>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Item Image (optional)</label>
                    <div class="hero-preview <?= !empty($mi['image_path']) ? 'has-img' : '' ?>"
                         style="height:160px;<?= !empty($mi['image_path']) ? "background-image:url('" . e($mi['image_path']) . "');" : '' ?>">
                      <?= empty($mi['image_path']) ? 'No image uploaded' : '' ?>
                    </div>
                    <label class="file-drop" for="menu_image" style="display:block;">
                      <i class="bi bi-cloud-arrow-up"></i>
                      <strong>Upload image</strong>
                      <small>JPG, PNG, WebP, GIF — max 5 MB</small>
                      <input type="file" id="menu_image" name="menu_image"
                             accept="image/jpeg,image/png,image/webp,image/gif">
                    </label>
                    <?php if (!empty($mi['image_path'])): ?>
                      <label style="display:inline-flex;align-items:center;gap:8px;font-size:.8rem;color:#6e5d50;cursor:pointer;">
                        <input type="checkbox" name="remove_image" value="1"> Remove current image
                      </label>
                    <?php endif; ?>
                  </div>

                  <div class="col-md-6 d-flex align-items-end">
                    <label style="display:inline-flex;align-items:center;gap:9px;font-size:.82rem;color:#6e5d50;cursor:pointer;">
                      <input type="checkbox" name="is_active" value="1" <?= !empty($mi['is_active']) ? 'checked' : '' ?>>
                      Active (show on menu)
                    </label>
                  </div>

                  <div class="col-12" style="display:flex;gap:10px;margin-top:8px;">
                    <button class="btn-coffee" type="submit">
                      <i class="bi bi-check2"></i> <?= $isNewItem ? 'Create Item' : 'Update Item' ?>
                    </button>
                    <a href="?page=menu" class="btn-sm-coffee" style="padding:12px 22px;">Cancel</a>
                  </div>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>

      <?php endif; ?>

    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  /* Live-ish file name hints */
  document.querySelectorAll('input[type="file"]').forEach(function(inp){
    inp.addEventListener('change', function(){
      var wrap = inp.closest('label')?.nextElementSibling;
      if (wrap && wrap.id && wrap.id.endsWith('FileName')) {
        wrap.textContent = inp.files.length ? 'Selected: ' + inp.files[0].name : '';
      }
    });
  });

  /* Live preview bindings (values + quote + feature) */
  (function(){
    var vIcon = document.getElementById('icon_class');
    var vTitle = document.getElementById('title');
    var vDesc = document.getElementById('description');
    if (vIcon && document.getElementById('vpIcon')) {
      vIcon.addEventListener('input', function(){
        document.getElementById('vpIcon').className = 'bi ' + vIcon.value;
      });
    }
    if (vTitle && document.getElementById('vpTitle')) {
      vTitle.addEventListener('input', function(){
        document.getElementById('vpTitle').textContent = vTitle.value || 'Card title';
      });
    }
    if (vDesc && document.getElementById('vpDesc')) {
      vDesc.addEventListener('input', function(){
        document.getElementById('vpDesc').textContent = vDesc.value || 'Card description';
      });
    }

    var qText = document.getElementById('quote_text');
    var qAuthor = document.getElementById('author');
    if (qText && document.getElementById('qText')) {
      qText.addEventListener('input', function(){ document.getElementById('qText').textContent = qText.value; });
    }
    if (qAuthor && document.getElementById('qAuthor')) {
      qAuthor.addEventListener('input', function(){ document.getElementById('qAuthor').textContent = qAuthor.value; });
    }

    var fpMap = {
      eyebrow: 'fpEyebrow',
      title_line1: 'fpTitle1',
      title_line2: 'fpTitle2',
      description: 'fpCopy',
      cta_text: 'fpBtn'
    };
    Object.keys(fpMap).forEach(function(id){
      var el = document.getElementById(id);
      var target = document.getElementById(fpMap[id]);
      if (el && target) {
        el.addEventListener('input', function(){
          if (id === 'cta_text') {
            target.innerHTML = (el.value || '') + ' <i class="bi bi-arrow-up-right"></i>';
          } else {
            target.textContent = el.value;
          }
        });
      }
    });
    var colorEl = document.getElementById('title_line2_color');
    var fpTitle2 = document.getElementById('fpTitle2');
    if (colorEl && fpTitle2) {
      colorEl.addEventListener('input', function(){ fpTitle2.style.color = colorEl.value; });
    }
  })();
</script>
</body>
</html>