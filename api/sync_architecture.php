<?php
// sync_architecture.php
session_start();
include 'db.php'; // Your DB config

if (isset($_POST['target_node'])) {
    $learner_id = $_SESSION['user_id']; // Ensure this is set during login
    $target_node = $_POST['target_node'];

    // Path to your python executable and script
    $python_path = "python"; // or "python3" depending on your server
    $script_path = "skill_gap_engine.py";

    // Execute the Python script with arguments
    $command = escapeshellcmd("$python_path $script_path $learner_id $target_node");
    $output = shell_exec($command);

    // Return response to AJAX
    echo json_encode(["status" => "success", "message" => "Intelligence Backbone Synced!", "debug" => $output]);
}
?>