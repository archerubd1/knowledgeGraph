<?php
include("../config/db.php");

$stmt = $conn->prepare("
INSERT INTO kg_edges
(from_node,to_node,relationship_type,weight,required_level,confidence)
VALUES (?,?,?,?,?,?)
");

$stmt->bind_param(
"iisddd",
$_POST['from_node'],
$_POST['to_node'],
$_POST['relationship_type'],
$_POST['weight'],
$_POST['required_level'],
$_POST['confidence']
);

$stmt->execute();

echo "Edge Added";
?>
