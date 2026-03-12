<?php
$filePath = 'api/external_programs.php';
$content = file_get_contents($filePath);

$target = "        if (empty(\$data['payment_upi']) || !str_contains(\$data['payment_upi'], '@')) {
            echo json_encode(['success' => false, 'error' => 'A valid UPI ID (e.g. name@okaxis) is required for paid programs.']);
            return;
        }";

// Need to handle potential whitespace variations
$newContent = str_replace($target, '', $content);

if ($newContent !== $content) {
    file_put_contents($filePath, $newContent);
    echo "✅ Successfully removed UPI validation from $filePath.\n";
} else {
    echo "❌ Failed to find target content in $filePath.\n";
    // Try a more flexible regex if literal match fails
    $regex = "/if\s*\(empty\(\\\$data\['payment_upi'\]\)\s*\|\|\s*!str_contains\(\\\$data\['payment_upi'\]\s*,\s*'@'\)\)\s*\{[\s\S]*?return;\s*\}/";
    $newContentRegex = preg_replace($regex, '', $content);
    if ($newContentRegex !== $content) {
        file_put_contents($filePath, $newContentRegex);
        echo "✅ Successfully removed UPI validation using regex.\n";
    } else {
        echo "❌ Regex also failed to find target content.\n";
    }
}
?>