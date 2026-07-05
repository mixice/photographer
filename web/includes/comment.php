<?php

function handleCommentSubmission($conn, $target_type, $target_id, $comment_enabled, $redirect_url, $error_redirect) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$comment_enabled) {
        return false;
    }

    // CSRF token validation
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        header("Location: " . $error_redirect . (strpos($error_redirect, '?') !== false ? '&' : '?') . "msg=csrf_fail");
        exit();
    }

    // Rate limiting: 1 comment per 60 seconds per session
    if (isset($_SESSION['last_comment_time']) && (time() - $_SESSION['last_comment_time']) < 60) {
        header("Location: " . $error_redirect . (strpos($error_redirect, '?') !== false ? '&' : '?') . "msg=rate_limited");
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $name = mb_substr(strip_tags($name), 0, 50);
    $content = mb_substr(strip_tags($content), 0, 1000);

    if (!empty($_POST['email-repeat'])) {
        return false;
    }
    if (!empty($name) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO comment (target_type, target_id, name, email, content, status) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sisss", $target_type, $target_id, $name, $email, $content);
        if ($stmt->execute()) {
            $_SESSION['last_comment_time'] = time();
            header("Location: $redirect_url");
            exit();
        }
    }
    return false;
}

function getCommentPagination($conn, $target_type, $target_id, $page_size = 10, $comment_page = null) {
    $comment_page = $comment_page ?? max(1, intval($_GET['cpage'] ?? 1));
    $target_id = (int) $target_id;

    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM comment WHERE target_type=? AND target_id=? AND status=1");
    $count_stmt->bind_param('si', $target_type, $target_id);
    $count_stmt->execute();
    $total_comments = (int) $count_stmt->get_result()->fetch_row()[0];

    $comment_pages = max(1, ceil($total_comments / $page_size));
    $comment_offset = ($comment_page - 1) * $page_size;

    $list_stmt = $conn->prepare("SELECT id, name, email, content, created_at FROM comment WHERE target_type=? AND target_id=? AND status=1 ORDER BY created_at DESC, id DESC LIMIT ?, ?");
    $list_stmt->bind_param('siii', $target_type, $target_id, $comment_offset, $page_size);
    $list_stmt->execute();
    $comment_list = $list_stmt->get_result();

    return [
        'comment_page' => $comment_page,
        'comment_pages' => $comment_pages,
        'comment_list' => $comment_list,
    ];
}
