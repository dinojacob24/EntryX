<?php
require_once 'config/db_connect.php';
require_once 'classes/Event.php';

$eventObj = new Event($pdo);
$data = [
    'name' => 'Test Event ' . time(),
    'description' => 'Test Description',
    'event_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'venue' => 'Test Venue',
    'capacity' => 100,
    'type' => 'both',
    'is_paid' => 1,
    'base_price' => 100.00,
    'is_gst_enabled' => 1,
    'gst_rate' => 18.00,
    'payment_upi' => 'test@upi',
    'gst_target' => 'both',
    'created_by' => 1 // Assuming 1 is a valid user ID
];

$result = $eventObj->createEvent($data);
echo json_encode($result);
?>