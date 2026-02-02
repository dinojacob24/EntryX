<?php
/**
 * Update UPI ID for Active External Program
 * Run this script once to add UPI payment ID
 */

require_once '../config/db_connect.php';

try {
    // Update the active external program with UPI ID
    $upiId = 'dinojacob24@okaxis';

    $stmt = $pdo->prepare("UPDATE external_programs SET payment_upi = ? WHERE is_active = 1");
    $result = $stmt->execute([$upiId]);

    if ($result) {
        echo "✅ Successfully updated UPI ID: $upiId\n\n";

        // Verify the update
        $check = $pdo->query("SELECT id, program_name, is_paid, payment_upi, total_amount_with_gst FROM external_programs WHERE is_active = 1");
        $program = $check->fetch(PDO::FETCH_ASSOC);

        if ($program) {
            echo "Current Active Program:\n";
            echo "- ID: " . $program['id'] . "\n";
            echo "- Name: " . $program['program_name'] . "\n";
            echo "- Is Paid: " . ($program['is_paid'] ? 'Yes' : 'No') . "\n";
            echo "- UPI ID: " . $program['payment_upi'] . "\n";
            echo "- Total Amount: ₹" . $program['total_amount_with_gst'] . "\n";
        }
    } else {
        echo "❌ Failed to update UPI ID\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>