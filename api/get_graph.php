<?php
include("../config/db.php");

$result = $conn->query("
SELECT e.from_node,e.to_node,e.relationship_type,
n1.node_name as from_name,
n2.node_name as to_name
FROM kg_edges e
JOIN kg_nodes n1 ON e.from_node=n1.node_id
JOIN kg_nodes n2 ON e.to_node=n2.node_id
");

$data = [];
while($row=$result->fetch_assoc()){
    $data[]=$row;
}

echo json_encode($data);
?>
