<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Optimized Query for Performance and Accuracy
$competency_query = "
    SELECT n.node_id, n.node_name, n.description, MAX(v.validation_score) as validation_score 
    FROM kg_nodes n
    LEFT JOIN learner_skill_validation v ON n.node_id = v.skill_node_id AND v.learner_id = '$user_id'
    WHERE n.node_type IN ('competency', 'skill')
    GROUP BY n.node_id
    ORDER BY n.node_name ASC";

$results = mysqli_query($conn, $competency_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competency Backbone | Astraal AKIB</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #2563eb; 
            --expert: #10b981; 
            --advanced: #3b82f6; 
            --intermediate: #f59e0b; 
            --beginner: #ef4444;
            --bg-slate: #f8fafc;
        }

        body { 
            background-color: var(--bg-slate); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #0f172a; 
            letter-spacing: -0.01em;
        }

        /* Professional Header Styling */
        .hero-section {
            padding: 4rem 0 3rem;
            background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.05), transparent);
        }

        .backbone-text {
            background: linear-gradient(90deg, #1e293b, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }

        /* Glassmorphism Cards */
        .comp-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .comp-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }

        /* Dynamic Progress Bar logic */
        .progress-container {
            background: #f1f5f9;
            height: 10px;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            margin: 1.5rem 0 1rem;
        }

        .progress-fill {
            height: 100%;
            border-radius: 20px;
            transition: width 1.5s ease-in-out;
        }

        /* Level Badge Styling */
        .status-pill {
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .node-id-tag {
            background: #f1f5f9;
            color: #64748b;
            font-size: 0.7rem;
            padding: 4px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-action {
            border-radius: 14px;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .line-clamp {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;  
            overflow: hidden;
            min-height: 40px;
        }
    </style>
</head>
<body>

<div class="hero-section">
    <div class="container text-center">
        <h6 class="text-primary fw-bold text-uppercase small mb-3" style="letter-spacing: 0.2em;">Astraal Engine v2.0</h6>
        <h1 class="display-4 backbone-text mb-3">Your Competency Profile</h1>
        <div class="d-flex justify-content-center align-items-center gap-3">
            <span class="text-muted fs-5">Structural nodes mapped to the <strong class="text-dark">Astraal Backbone</strong></span>
           
            
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <?php while($row = mysqli_fetch_assoc($results)): 
            $raw_score = isset($row['validation_score']) ? $row['validation_score'] : 0;
            $percentage = $raw_score * 100;
            $marks = $raw_score * 10;
            $is_validated = $raw_score > 0;

            // Deep Logic for Professional Coloring
            if ($marks >= 9) { 
                $level = "Expert"; 
                $color = "var(--expert)"; 
                $bg_subtle = "#ecfdf5";
            } elseif ($marks >= 7) { 
                $level = "Advanced"; 
                $color = "var(--advanced)"; 
                $bg_subtle = "#eff6ff";
            } elseif ($marks >= 5) { 
                $level = "Intermediate"; 
                $color = "var(--intermediate)"; 
                $bg_subtle = "#fffbeb";
            } else { 
                $level = $is_validated ? "Beginner" : "NOT STARTED"; 
                $color = $is_validated ? "var(--beginner)" : "#94a3b8"; 
                $bg_subtle = $is_validated ? "#fef2f2" : "#f8fafc";
            }
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="comp-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="node-id-tag">NODE #<?php echo $row['node_id']; ?></span>
                    <span class="status-pill" style="background: <?php echo $bg_subtle; ?>; color: <?php echo $color; ?>;">
                        <i class="fas <?php echo $is_validated ? 'fa-verified' : 'fa-circle-notch fa-spin'; ?> me-1"></i>
                        <?php echo $is_validated ? 'Validated' : 'Analysis Pending'; ?>
                    </span>
                </div>
                
                <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($row['node_name']); ?></h4>
                <p class="text-muted small line-clamp mb-4"><?php echo htmlspecialchars($row['description']); ?></p>

                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <div>
                            <span class="d-block small text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Current Proficiency</span>
                            <span class="fw-bold" style="color: <?php echo $color; ?>; font-size: 1.1rem;">
                                <?php echo $level; ?> 
                                <?php if($is_validated) echo '<span class="text-muted fw-normal" style="font-size: 0.8rem;">('.round($marks).'/10)</span>'; ?>
                            </span>
                        </div>
                        <span class="h5 fw-bold mb-0"><?php echo round($percentage); ?>%</span>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $color; ?>;"></div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-3">
                        <a href="take_assessment.php?node_id=<?php echo $row['node_id']; ?>" 
                           class="btn flex-grow-1 btn-action <?php echo $is_validated ? 'btn-outline-dark' : 'btn-primary shadow-sm'; ?>">
                            <?php echo $is_validated ? '<i class="fas fa-sync-alt me-2"></i>Re-Validate' : 'Launch Gap Analysis'; ?>
                        </a>
                        <button class="btn btn-light border btn-action" style="width: 50px;">
                            <i class="fas fa-ellipsis-v text-muted"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>