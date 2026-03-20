<?php
// Project Root Configuration (Portable between XAMPP and Linux)
if (!isset($entryx_root)) {
    $self = str_replace('\\', '/', $_SERVER['PHP_SELF']);
    $pos = strpos($self, '/EntryX/');
    if ($pos !== false) {
        $entryx_root = substr($self, 0, $pos + 8); 
    } else {
        $entryx_root = '/';
    }
}

// Global Path Mapping Utility
function get_entryx_path($path = '') {
    global $entryx_root;
    return $entryx_root . ltrim($path, '/');
}

// 🔐 SESSION BOOTSTRAP: Standardized session for all pages
if (session_status() === PHP_SESSION_NONE) {
    // Modern Session Scoping (PHP 7.3+)
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $entryx_root,
        'samesite' => 'Lax'
    ]);
    session_start();
}
?>
