<?php
require_once 'config/db_connect.php';
$stmt = $pdo->query('SELECT id, name, is_paid, base_price, is_gst_enabled, gst_rate, gst_target FROM events WHERE is_paid=1 LIMIT 5');
$rows = $stmt->fetchAll();
foreach($rows as $r) {
    echo "ID:{$r['id']} | {$r['name']} | base:₹{$r['base_price']} | gst_enabled:{$r['is_gst_enabled']} | gst_rate:{$r['gst_rate']} | gst_target:{$r['gst_target']}\n";
    // Simulate internal user GST calc
    $price = floatval($r['base_price']);
    if ($r['is_gst_enabled'] && in_array($r['gst_target'], ['both', 'internals_only'])) {
        $price += $price * (floatval($r['gst_rate']) / 100);
        echo "  -> Internal price with GST: ₹$price\n";
    } else {
        echo "  -> Internal price (no GST): ₹$price\n";
    }
    $price = floatval($r['base_price']);
    if ($r['is_gst_enabled'] && in_array($r['gst_target'], ['both', 'externals_only'])) {
        $price += $price * (floatval($r['gst_rate']) / 100);
        echo "  -> External price with GST: ₹$price\n";
    } else {
        echo "  -> External price (no GST): ₹$price\n";
    }
}
