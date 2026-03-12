<?php
require_once "c:/xampp/htdocs/Project/EntryX/config/db_connect.php";
$stmt = $pdo->query("SHOW COLUMNS FROM registrations");
file_put_contents('tmp_output.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)));
?>
