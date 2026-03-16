<?php
session_start();
include('../config/db.php');

/** * 1. SESSION & CONTEXT
 */
if (!isset($_SESSION['user_id'])) { 
    die("<div class='container mt-5'><div class='alert alert-danger'>Access Denied: Please log in to view your learning path.</div></div>"); 
}

$user_id = $_SESSION['user_id'];

/**
 * 2. DYNAMIC CAREER TARGET RETRIEVAL
 */
$target_q = mysqli_query($conn, "SELECT career_node_id FROM learner_active_target WHERE learner_id = '$user_id' LIMIT 1");
$target_data = mysqli_fetch_assoc($target_q);
$target_id = $target_data ? $target_data['career_node_id'] : 0;

/**
 * 3. KNOWLEDGE GRAPH TRAVERSAL & GAP CALCULATION
 */
$path_query = "
    SELECT 
        n.node_id, 
        n.node_name, 
        n.description,
        e.required_level,
        COALESCE(MAX(v.validation_score), 0) as current_level,
        (e.required_level - COALESCE(MAX(v.validation_score), 0)) as gap_value
    FROM kg_edges e
    JOIN kg_nodes n ON e.to_node = n.node_id
    LEFT JOIN learner_skill_validation v ON n.node_id = v.skill_node_id AND v.learner_id = '$user_id'
    WHERE e.from_node = '$target_id' 
    AND e.relationship_type = 'REQUIRES'
    GROUP BY n.node_id
    HAVING gap_value > 0
    ORDER BY gap_value DESC";

$path_results = mysqli_query($conn, $path_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intelligence Roadmap | Astraal AKIB</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --bg-canvas: #f8fafc;
            --text-main: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(226, 232, 240, 0.8);
        }

        body { 
            background-color: var(--bg-canvas);
            /* Subtle depth pattern */
            background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.03) 0, transparent 50%), 
                              radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.03) 0, transparent 50%);
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--text-main);
            min-height: 100vh;
        }

        .path-container { 
            max-width: 850px; 
            margin: 80px auto; 
            position: relative; 
        }
        
        /* The High-Level Roadmap Connector */
        .path-container::before {
            content: ''; 
            position: absolute; 
            left: 33px; 
            top: 20px; 
            bottom: 0;
            width: 4px; 
            background: linear-gradient(to bottom, #6366f1, #e2e8f0); 
            border-radius: 100px;
            opacity: 0.3;
        }

        /* --- Header & Career Badge --- */
        .header-section {
            padding-left: 80px;
            margin-bottom: 60px;
        }

        .career-badge-container {
            display: inline-flex;
            align-items: center;
            background: white;
            padding: 10px 20px;
            border-radius: 100px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }

        .career-badge-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            margin-right: 14px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .career-id-pill {
            background: #f1f5f9;
            color: #6366f1;
            padding: 4px 12px;
            border-radius: 8px;
            font-family: 'Monaco', monospace;
            font-size: 0.85rem;
            margin-left: 8px;
            font-weight: 700;
        }

        /* --- Glassmorphic Step Cards --- */
        .step-card {
            backdrop-filter: blur(12px);
            background: var(--glass-bg);
            border: 1px solid var(--card-border);
            border-radius: 30px; 
            padding: 35px;
            margin-bottom: 40px; 
            margin-left: 80px;
            position: relative; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
        }

        .step-card:hover { 
            transform: translateX(15px) scale(1.02); 
            border-color: #6366f1;
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.1);
        }

        .step-icon {
            position: absolute; 
            left: -63px; 
            top: 35px;
            width: 38px; 
            height: 38px; 
            background: var(--primary-gradient);
            border-radius: 50%; 
            z-index: 2;
            box-shadow: 0 0 0 10px rgba(99, 102, 241, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .gap-indicator {
            font-size: 0.75rem; 
            font-weight: 800;
            padding: 8px 16px; 
            border-radius: 50px;
            background: #fff1f2; 
            color: #e11d48;
            border: 1px solid #fecdd3;
            text-transform: uppercase;
        }

        /* --- Action Area --- */
        .course-bridge {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px 25px;
            border: 1px dashed #cbd5e1;
        }

        .btn-learn {
            background: var(--primary-gradient);
            color: white;
            font-weight: 700;
            border: none;
            padding: 12px 28px;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .btn-learn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
            color: white;
        }


        /* Import a stylish, modern font */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;800&display=swap');

.roadmap-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 800; /* Extra bold for a high-scope feel */
    font-size: 3.5rem; /* Larger display size */
    letter-spacing: -1.5px; /* Tighter tracking for a professional look */
    margin-bottom: 0.2rem;
    
    /* Elegant Dark Purple Gradient */
    background: linear-gradient(135deg, #2d1b69 0%, #4c1d95 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    
    /* Subtle text shadow to give it a "lift" from the white background */
    text-shadow: 2px 4px 10px rgba(45, 27, 105, 0.1);
}

/* Enhancing the subtitle to match */
.header-section p {
    font-family: 'Outfit', sans-serif;
    font-weight: 500;
    color: #6d28d9; /* Lighter purple for the subtitle */
    opacity: 0.8;
}
    </style>
</head>
<body>

<div class="container path-container">
    <div class="header-section">
        <h6 class="text-primary fw-bold text-uppercase small ls-wide mb-2">
            <i class="fas fa-microchip me-2"></i>Astraal AKIB Intelligence
        </h6>
       <h1 class="roadmap-title">Your Learning Roadmap</h1>
        
        <div class="career-badge-container">
            <div class="career-badge-icon">
                <i class="fas fa-bullseye"></i>
            </div>
            <div class="career-badge-text">
                <span class="text-muted fw-normal">Currently bridging gaps for</span> 
                <strong>Target Career</strong>
                <span class="career-id-pill">#<?php echo $target_id; ?></span>
            </div>
        </div>
    </div>

    <?php if(mysqli_num_rows($path_results) > 0): ?>
        <?php $i = 1; while($step = mysqli_fetch_assoc($path_results)): ?>
            <div class="step-card">
                <div class="step-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="fw-bold text-dark mb-1">
                            <span class="text-primary opacity-50 me-1"><?php echo sprintf("%02d", $i++); ?>.</span> 
                            <?php echo htmlspecialchars($step['node_name']); ?>
                        </h3>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small me-2">
                                <i class="fas fa-chart-line text-primary me-2"></i>Required: <?php echo $step['required_level']; ?>
                            </span>
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                                <i class="fas fa-user-check text-success me-2"></i>Current: <?php echo number_format($step['current_level'], 1); ?>
                            </span>
                        </div>
                    </div>
                    <span class="gap-indicator">
                        <i class="fas fa-minus-circle me-1"></i> Gap: <?php echo number_format($step['gap_value'], 1); ?>
                    </span>
                </div>

                <p class="text-muted mb-4 leading-relaxed">
                    <?php echo htmlspecialchars($step['description']); ?>
                </p>
                
                <div class="course-bridge d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <small class="text-uppercase fw-800 text-muted d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">Recommended Validation</small>
                        <span class="fw-bold text-dark">Knowledge Graph Skill Assessment</span>
                    </div>
                    <a href="take_assessment.php?node_id=<?php echo $step['node_id']; ?>" class="btn btn-learn rounded-pill px-4">
                        Start Assessment <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5 ps-5">
            <div class="display-1 text-primary opacity-25 mb-4"><i class="fas fa-award"></i></div>
            <h2 class="fw-bold">Roadmap Optimized</h2>
            <p class="text-muted px-5">All skill nodes for Career Target #<?php echo $target_id; ?> have been validated. You are ready for the next level.</p>
            <a href="career.php" class="btn btn-outline-primary rounded-pill px-5 mt-3">Select New Career Node</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>