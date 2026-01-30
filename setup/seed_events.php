<?php
require_once '../config/db_connect.php';

echo "<h1>Seeding Events...</h1>";

$events = [
    [
        'name' => 'Tech Fest 2025',
        'description' => 'Annual technical symposium of MCA Department.',
        'event_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
        'venue' => 'Main Auditorium',
        'capacity' => 500,
        'type' => 'both',
        'is_paid' => 1,
        'base_price' => 200.00,
        'is_gst_enabled' => 1,
        'gst_rate' => 18.00,
        'created_by' => 1 // Super Admin
    ],
    [
        'name' => 'Guest Lecture: AI Trends',
        'description' => 'A session on Agentic AI by Google DeepMind experts.',
        'event_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'venue' => 'Seminar Hall 1',
        'capacity' => 100,
        'type' => 'internal',
        'is_paid' => 0,
        'base_price' => 0.00,
        'is_gst_enabled' => 0,
        'gst_rate' => 0.00,
        'created_by' => 1
    ]
];

try {
    $stmt = $pdo->prepare("INSERT INTO events (name, description, event_date, venue, capacity, type, is_paid, base_price, is_gst_enabled, gst_rate, created_by) 
                           VALUES (:name, :description, :event_date, :venue, :capacity, :type, :is_paid, :base_price, :is_gst_enabled, :gst_rate, :created_by)");

    foreach ($events as $event) {
        $stmt->execute($event);
        echo "<p style='color: green;'>✅ Created Event: <b>{$event['name']}</b></p>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>