<?php
session_start();
include('../config/db.php');

// 1. DYNAMIC ROLE DETECTION
if (isset($_GET['role_id'])) {
    $target_role_id = intval($_GET['role_id']);
} else {
    // If no ID is passed, find the first career node in the database
    $find_first = "SELECT node_id FROM kg_nodes WHERE node_type = 'career' LIMIT 1";
    $first_res = $conn->query($find_first);
    $first_row = $first_res->fetch_assoc();
    $target_role_id = $first_row ? $first_row['node_id'] : 0; 
}

// 2. Fetch the Career Node name
$role_query = "SELECT node_name FROM kg_nodes WHERE node_id = $target_role_id";
$role_res = $conn->query($role_query);
$role_data = $role_res->fetch_assoc();
$role_name = $role_data ? $role_data['node_name'] : "Architecture Not Found";

// 3. Fetch all Skills linked to this Career
$edges_query = "SELECT e.to_node, n.node_name, e.required_level 
                FROM kg_edges e 
                JOIN kg_nodes n ON e.to_node = n.node_id 
                WHERE e.from_node = $target_role_id AND e.relationship_type = 'REQUIRES'";
$edges_res = $conn->query($edges_query);

$elements = array();
// Add the Career Node (Center)
$elements[] = array('data' => array('id' => 'career', 'label' => $role_name, 'type' => 'career'));

while($row = $edges_res->fetch_assoc()) {
    $elements[] = array('data' => array('id' => 'skill_'.$row['to_node'], 'label' => $row['node_name'], 'type' => 'skill'));
    $elements[] = array('data' => array(
        'id' => 'edge_'.$row['to_node'], 
        'source' => 'career', 
        'target' => 'skill_'.$row['to_node']
    ));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Knowledge Network | <?php echo $role_name; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.23.0/cytoscape.min.js"></script>
    <style>
        #cy { width: 100%; height: 85vh; background: #0f172a; border-radius: 15px; margin-top: 20px;}
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1e293b; color: white; padding: 20px; overflow: hidden; }
        .back-btn { background: #3b82f6; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Exploring Architecture: <span style="color: #3b82f6;"><?php echo $role_name; ?></span></h2>
        <a href="javascript:history.back()" class="back-btn">← Back to Dashboard</a>
    </div>

    <div id="cy"></div>

    <script>
        var cy = cytoscape({
            container: document.getElementById('cy'),
            elements: <?php echo json_encode($elements); ?>,
            style: [
                {
                    selector: 'node[type="career"]',
                    style: { 'background-color': '#3b82f6', 'label': 'data(label)', 'color': '#fff', 'width': 90, 'height': 90, 'text-valign': 'center', 'font-weight': 'bold' }
                },
                {
                    selector: 'node[type="skill"]',
                    style: { 'background-color': '#64748b', 'label': 'data(label)', 'color': '#cbd5e1', 'width': 60, 'height': 60, 'text-valign': 'bottom', 'text-margin-y': 5 }
                },
                {
                    selector: 'edge',
                    style: { 'width': 3, 'line-color': '#475569', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#475569', 'curve-style': 'bezier' }
                }
            ],
            layout: { name: 'cose', animate: true, nodeRepulsion: 400000 }
        });
    </script>
</body>
</html>