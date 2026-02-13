<?php
include("../config/db.php");

$stmt = $conn->prepare("
INSERT INTO kg_nodes
(node_type,node_name,taxonomy_level,parent_node,description)
VALUES (?,?,?,?,?)
");

$stmt->bind_param(
"siiis",
$_POST['node_type'],
$_POST['node_name'],
$_POST['taxonomy_level'],
$_POST['parent_node'],
$_POST['description']
);

$stmt->execute();

echo "Node Added";
?>
