<?php
declare(strict_types=1);

/**
 * Email Queue Processor
 *
 * Run via cPanel cron: php /path/to/cron/send-emails.php
 * Or trigger manually from admin/email-queue.php
 *
 * Processes up to 20 pending emails per run.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/email-templates.php';

$pdo = get_db();
$batch_size = 20;

$stmt = $pdo->prepare('SELECT * FROM email_queue WHERE status = "pending" AND scheduled_at <= datetime("now") ORDER BY scheduled_at ASC LIMIT ?');
$stmt->execute([$batch_size]);
$queue = $stmt->fetchAll();

$sent = 0;
$failed = 0;

foreach ($queue as $item) {
    $html = get_email_body($item['template_key'], $item['language'], [
        'name'     => $item['recipient_name'],
    ]);
    $subject = get_email_subject($item['template_key'], $item['language'], [
        'name'     => $item['recipient_name'],
    ]);

    if ($html === '' || $subject === '') {
        $pdo->prepare('UPDATE email_queue SET status = "failed", error_message = "Template not found" WHERE id = ?')
            ->execute([$item['id']]);
        $failed++;
        continue;
    }

    $headers  = "From: Author Juan Jose <noreply@authorjuanjose.io>\r\n";
    $headers .= "Reply-To: noreply@authorjuanjose.io\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $ok = @mail($item['recipient_email'], $subject, $html, $headers);

    if ($ok) {
        $pdo->prepare('UPDATE email_queue SET status = "sent", sent_at = datetime("now") WHERE id = ?')
            ->execute([$item['id']]);
        $sent++;
    } else {
        $pdo->prepare('UPDATE email_queue SET status = "failed", error_message = "mail() returned false" WHERE id = ?')
            ->execute([$item['id']]);
        $failed++;
    }
}

// Output for cron log
echo date('Y-m-d H:i:s') . " — Processed: " . count($queue) . ", Sent: {$sent}, Failed: {$failed}" . PHP_EOL;
