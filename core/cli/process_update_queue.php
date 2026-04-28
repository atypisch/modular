<?php
/**
 * Process update_queue jobs (modules, future types) from CLI or HTTP.
 *
 * Usage (CLI):
 *   php core/cli/process_update_queue.php
 */

// Bootstrap config / database
if (!defined('MODULAR_PATH')) {
    define('MODULAR_PATH', dirname(__DIR__));
}

require_once MODULAR_PATH . '/admin/config/config.php';

if (!isset($GLOBALS['db']) || !$GLOBALS['db'] instanceof PDO) {
    fwrite(STDERR, "Database unavailable. Aborting update queue processing.\n");
    exit(1);
}

require_once CORE_PATH . '/classes/Update/QueueService.php';
require_once CORE_PATH . '/classes/Update/ModuleUpdateService.php';

$db = $GLOBALS['db'];
$queue = new \Modular\Update\QueueService($db);

if (!$queue->acquireLock()) {
    echo "Update queue is locked by another process.\n";
    exit(0);
}

$processed = 0;

try {
    $service = new \Modular\Update\ModuleUpdateService($db);
    while ($job = $queue->getNextPending()) {
        $id = (int) $job['id'];
        $queue->markRunning($id);

        if ($job['type'] === 'module' && $job['action'] === 'update') {
            $result = $service->run($job['target']);
            if (!empty($result['success'])) {
                $queue->markCompleted($id, json_encode($result));
                echo "Job #{$id}: module '{$job['target']}' updated.\n";
            } else {
                $error = (string) ($result['error'] ?? 'Unknown error');
                $queue->markFailed($id, $error);
                echo "Job #{$id}: module '{$job['target']}' failed – {$error}\n";
            }
        } else {
            $queue->markFailed($id, 'Unsupported job type/action.');
            echo "Job #{$id}: unsupported type/action ({$job['type']}/{$job['action']}).\n";
        }

        $processed++;
    }
} finally {
    $queue->releaseLock();
}

if ($processed === 0) {
    echo "No pending jobs in update_queue.\n";
} else {
    echo "Processed {$processed} job(s) from update_queue.\n";
}

exit(0);

