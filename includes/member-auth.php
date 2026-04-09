<?php
declare(strict_types=1);

/**
 * Member Auth Guard
 *
 * Include this at the top of any member-only page.
 * It starts the session, checks for a valid arc_member_id,
 * loads the member record, and redirects to login if not authenticated.
 *
 * After including, these variables are available:
 *   $arc_member — full member row (assoc array)
 *   $pdo        — PDO database connection
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';

$pdo = get_db();

// Check session
if (empty($_SESSION['arc_member_id'])) {
    header('Location: /arc-reader-club/login');
    exit;
}

// Load member
$stmt = $pdo->prepare('SELECT * FROM members WHERE id = ? AND status = "active"');
$stmt->execute([$_SESSION['arc_member_id']]);
$arc_member = $stmt->fetch();

if (!$arc_member) {
    // Member not found or no longer active — clear session
    unset($_SESSION['arc_member_id']);
    header('Location: /arc-reader-club/login');
    exit;
}
