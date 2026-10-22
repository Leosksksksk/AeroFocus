<?php
/**
 * transactions.php – Activity / Audit Log
 */
define('AEROFOCUS', true);
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db       = $database->connect();
$userObj  = new User($db);

$transactions = $userObj->getTransactions($currentUser['user_id'], 50);

require_once 'includes/header.php';
?>

<div class="panel">
    <h2 style="display:flex; align-items:center; gap:10px;">
        <svg style="width:22px;height:22px;color:var(--orange);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>
        Activity Log
    </h2>
    <p style="color:var(--grey-text); font-size:13px; margin-bottom:24px;">
        Showing the last 50 actions for <strong style="color:var(--sky);"><?php echo htmlspecialchars($currentUser['full_name']); ?></strong>
    </p>

    <?php if (empty($transactions)): ?>
        <div class="empty-state">No activity recorded yet.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Action</th>
                <th>Type</th>
                <th>Description</th>
                <th>Date &amp; Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $i => $tx): ?>
            <tr>
                <td style="color:var(--muted);font-size:12px;"><?php echo (int)$tx['transaction_id']; ?></td>
                <td>
                    <?php
                    $actionColors = [
                        'CREATE'   => ['color' => '#39ff8a', 'bg' => 'rgba(57,255,138,.1)',  'border' => '#39ff8a'],
                        'UPDATE'   => ['color' => '#ff8c1a', 'bg' => 'rgba(255,140,26,.1)',  'border' => '#ff8c1a'],
                        'DELETE'   => ['color' => '#ff3b4e', 'bg' => 'rgba(255,59,78,.1)',   'border' => '#ff3b4e'],
                        'LOGIN'    => ['color' => '#38bdf8', 'bg' => 'rgba(56,189,248,.1)',  'border' => '#38bdf8'],
                        'LOGOUT'   => ['color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.1)', 'border' => '#94a3b8'],
                        'REGISTER' => ['color' => '#a78bfa', 'bg' => 'rgba(167,139,250,.1)', 'border' => '#a78bfa'],
                    ];
                    $ac = $actionColors[$tx['action']] ?? ['color'=>'#ccc','bg'=>'transparent','border'=>'#ccc'];
                    ?>
                    <span style="
                        display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;
                        color:<?php echo $ac['color']; ?>;
                        background:<?php echo $ac['bg']; ?>;
                        border:1px solid <?php echo $ac['border']; ?>;
                        letter-spacing:.5px;
                    ">
                        <?php echo htmlspecialchars($tx['action']); ?>
                    </span>
                </td>
                <td style="color:var(--grey-text); font-size:13px; text-transform:capitalize;">
                    <?php echo htmlspecialchars($tx['entity_type']); ?>
                </td>
                <td style="font-size:13px; color:#e2e8f0;">
                    <?php echo htmlspecialchars($tx['description']); ?>
                </td>
                <td style="font-size:12px; color:var(--grey-text); white-space:nowrap;">
                    <?php echo htmlspecialchars($tx['performed_at']); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
