<?php
session_start();
include('../config/db.php');

// Security Check - Session validation
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not set, or keep your development dummy logic
    // $_SESSION['user_id'] = 1; 
}

$user_id = $_SESSION['user_id'];

// 1. Fetch the CURRENT ACTIVE TARGET
$target_sql = "SELECT n.node_name, n.node_id 
               FROM learner_active_target lat 
               JOIN kg_nodes n ON lat.career_node_id = n.node_id 
               WHERE lat.learner_id = $user_id 
               ORDER BY lat.activated_at DESC LIMIT 1";

$target_res = mysqli_query($conn, $target_sql);
$target_data = mysqli_fetch_assoc($target_res);

$target_id = isset($target_data['node_id']) ? $target_data['node_id'] : 0;
$target_name = isset($target_data['node_name']) ? $target_data['node_name'] : 'No Role Selected';

// 2. Fetch Certifications INCLUDING the link
$cert_list = [];
if ($target_id > 0) {
    // Added external_link to the SELECT
    $cert_sql = "SELECT c.node_name, c.external_link 
                 FROM kg_nodes c
                 JOIN kg_edges e ON c.node_id = e.to_node
                 WHERE e.from_node = $target_id 
                 AND e.relationship_type = 'RECOMMENDS'
                 LIMIT 5";
    
    $res = mysqli_query($conn, $cert_sql);
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            $cert_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Astraal AKIB | Target Credentials</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .target-header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 60px 0; border-radius: 0 0 40px 40px; }
        .cert-card { 
            background: white; border-radius: 20px; border: none; 
            transition: 0.3s; border-top: 5px solid #6366f1;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .cert-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .btn-prep { background: #6366f1; color: white; font-weight: 600; border-radius: 12px; padding: 10px; text-decoration: none; text-align: center; transition: 0.3s; }
        .btn-prep:hover { background: #4f46e5; color: white; }
    </style>
</head>
<body>

<div class="target-header text-center mb-5 shadow">
    <div class="container">
        <h6 class="text-info fw-bold mb-2">ASTRAAL CAREER ENGINE</h6>
        <h1 class="fw-bold"><?php echo strtoupper($target_name); ?></h1>
        <p class="opacity-75">Curated Industry Credentials to bridge your skill gaps.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4 justify-content-center">
        <?php if(!empty($cert_list)): ?>
            <?php foreach($cert_list as $index => $cert): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="cert-card p-4 h-100 shadow-sm">
                        <div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">Credential #<?php echo $index + 1; ?></span>
                                <i class="fas fa-external-link-alt text-muted"></i>
                            </div>
                            <h4 class="fw-bold mb-3"><?php echo $cert['node_name']; ?></h4>
                            <p class="text-muted small mb-4">Master this certification to unlock high-tier opportunities in <strong><?php echo $target_name; ?></strong>.</p>
                        </div>
                        
                        <?php if(!empty($cert['external_link'])): ?>
                            <a href="<?php echo $cert['external_link']; ?>" target="_blank" class="btn-prep">
                                <i class="fas fa-play-circle me-2"></i> Start Preparation
                            </a>
                       <?php else: ?>
                     <a href="https://www.google.com/search?q=<?php echo urlencode($cert['node_name'] . ' certification'); ?>" 
                     target="_blank" class="btn-prep" style="background: #94a3b8;">
                    <i class="fas fa-search me-2"></i> Find Details
                    </a>
                   <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="bg-white p-5 rounded-4 shadow-sm">
                    <i class="fas fa-layer-group fa-3x text-primary mb-3"></i>
                    <h3>Knowledge Graph Sync Required</h3>
                    <p>No certificates mapped for this role. Please sync your career target in the dashboard.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>