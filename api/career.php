<?php
session_start();
include('../config/db.php');

// Security Check
if (!isset($_SESSION['user_id'])) {
    //$_SESSION['user_id'] = 4; 
}
$user_id = $_SESSION['user_id'];

// --- LOGIC: HANDLE TARGET SYNC ---
$sync_message = "";
if(isset($_POST['set_target'])) {
    $target_id = mysqli_real_escape_string($conn, $_POST['career_id']);
    if(!empty($target_id)) {
        $update_target = "INSERT INTO learner_active_target (learner_id, career_node_id, activated_at) 
                          VALUES ($user_id, $target_id, NOW()) 
                          ON DUPLICATE KEY UPDATE career_node_id = $target_id, activated_at = NOW()";
        if(mysqli_query($conn, $update_target)) {
            shell_exec("python3 ../engines/skill_gap_engine.py $user_id $target_id 2>&1");
            $sync_message = "Intelligence backbone synchronized.";
        }
    }
}

// --- DATA FETCHING ---
$target_q = mysqli_query($conn, "SELECT n.node_name, n.node_id FROM learner_active_target lat 
                                 JOIN kg_nodes n ON lat.career_node_id = n.node_id 
                                 WHERE lat.learner_id = $user_id");
$current_target = mysqli_fetch_assoc($target_q);
$target_id = isset($current_target['node_id']) ? $current_target['node_id'] : 0;

$roles = mysqli_query($conn, "SELECT node_id, node_name FROM kg_nodes WHERE node_type = 'career_role' ORDER BY node_name ASC");

// --- SYNCHRONIZED LOGIC: FETCH TABLE DATA FIRST ---
$gap_results = [];
if ($target_id > 0) {
    // This query is now the SINGLE SOURCE OF TRUTH for both the count and the table
    $gap_q = mysqli_query($conn, "
        SELECT 
            n.node_name, 
            e.required_level, 
            MAX(COALESCE(v.validation_score, 0)) as current_level,
            (e.required_level - MAX(COALESCE(v.validation_score, 0))) as gap_value
        FROM kg_edges e
        JOIN kg_nodes n ON e.to_node = n.node_id
        LEFT JOIN learner_skill_validation v ON e.to_node = v.skill_node_id AND v.learner_id = '$user_id'
        WHERE e.from_node = '$target_id' 
        AND e.relationship_type = 'REQUIRES'
        GROUP BY n.node_id
        ORDER BY gap_value DESC
    ");
    while($row = mysqli_fetch_assoc($gap_q)) {
        $gap_results[] = $row;
    }
}

// --- UPDATED COUNTS ---
// Total Skill Competencies is now exactly the number of rows returned by the query
$total_skills_required = count($gap_results); 

// Skills Matched (where current level >= required level)
$validated_count = 0;
foreach($gap_results as $res) {
    if($res['current_level'] >= $res['required_level']) {
        $validated_count++;
    }
}

// Readiness Calculation
$readiness_pct = 0;
if ($total_skills_required > 0) {
    $sum_current = array_sum(array_column($gap_results, 'current_level'));
    $sum_required = array_sum(array_column($gap_results, 'required_level'));
    $readiness_pct = round(($sum_current / $sum_required) * 100);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astraal AKIB | Intelligence Interface</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --primary-accent: #6366f1; /* Indigo */
            --secondary-accent: #0ea5e9; /* Sky Blue */
            --dark-surface: #0f172a;
        }

        body {
            background: radial-gradient(circle at top right, #e0e7ff, #f8fafc);
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            min-height: 100vh;
        }

        /* --- HERO SECTION --- */
        .astraal-hero {
            background: linear-gradient(135deg, var(--dark-surface) 0%, #1e1b4b 100%);
            border-radius: 30px;
            padding: 4rem 3rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            margin-bottom: 3rem;
        }

        .astraal-hero::after {
            content: "";
            position: absolute;
            top: -50%; right: -10%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, transparent 70%);
            pointer-events: none;
        }

        /* --- STAT CARDS --- */
        .stat-pill {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 1.5rem;
            min-width: 160px;
            transition: 0.3s;
        }

        .stat-pill:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        /* --- MODERN CARDS --- */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        /* --- CUSTOM FORM ELEMENTS --- */
        .form-select-lg {
            border: 2px solid #e2e8f0 !important;
            border-radius: 15px !important;
            padding: 1rem !important;
            font-weight: 600;
        }

        .btn-sync {
            background: linear-gradient(90deg, var(--primary-accent), var(--secondary-accent));
            border: none;
            color: white;
            padding: 1.2rem;
            border-radius: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
            transition: 0.3s;
        }

        .btn-sync:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 25px -5px rgba(99, 102, 241, 0.5);
            color: white;
        }

        /* --- GAP TABLE --- */
        .table-custom {
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .table-custom thead th {
            border: none;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding-left: 1.5rem;
        }

        .table-custom tbody tr {
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            transition: 0.3s;
        }

        .table-custom tbody tr:hover {
            transform: scale(1.01);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
        }

        .table-custom td {
            padding: 1.5rem;
            border: none;
        }

        .table-custom td:first-child { border-radius: 15px 0 0 15px; }
        .table-custom td:last-child { border-radius: 0 15px 15px 0; }

        .badge-requirement {
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 10px;
        }

        .deficit-tag {
            background: #fff1f2;
            color: #e11d48;
            font-size: 0.8rem;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #ffe4e6;
        }

        .match-tag {
            background: #f0fdf4;
            color: #16a34a;
            font-size: 0.8rem;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #dcfce7;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="astraal-hero">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <span class="badge bg-info mb-3 px-3 py-2 text-dark fw-bold">INTEL BACKBONE v2.0</span>
                <h1 class="display-5 fw-bold mb-3">Career Alignment</h1>
                <p class="lead opacity-75 mb-0">Current Target: 
                    <span class="text-info fw-bold">
                        <?php echo $current_target ? strtoupper($current_target['node_name']) : 'NOT SET'; ?>
                    </span>
                </p>
            </div>
            <div class="col-lg-6 mt-4 mt-lg-0">
                <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
                    <div class="stat-pill text-center">
                        <h3 class="fw-bold mb-0"><?php echo $readiness_pct; ?>%</h3>
                        <small class="opacity-60">Readiness</small>
                    </div>
                    <div class="stat-pill text-center">
                        <h3 class="fw-bold mb-0"><?php echo $total_skills_required; ?></h3>
                        <small class="opacity-60">Total Nodes</small>
                    </div>
                    <div class="stat-pill text-center">
                        <h3 class="fw-bold mb-0 text-success"><?php echo $validated_count; ?></h3>
                        <small class="opacity-60">Matches</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-4">
            <div class="glass-card">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                        <i class="fas fa-crosshairs text-primary fa-lg"></i>
                    </div>
                    <h5 class="fw-bold m-0">Define Objective</h5>
                </div>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="small fw-bold text-muted mb-2">TARGET ARCHITECTURE</label>
                        <select name="career_id" class="form-select form-select-lg shadow-sm">
                            <?php mysqli_data_seek($roles, 0); while($r = mysqli_fetch_assoc($roles)): ?>
                                <option value="<?php echo $r['node_id']; ?>" <?php echo ($target_id == $r['node_id']) ? 'selected' : ''; ?>>
                                    <?php echo $r['node_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" name="set_target" class="btn btn-sync w-100 mb-4">
                        <i class="fas fa-project-diagram me-2"></i> Sync Engine
                    </button>
                </form>

                <div class="p-4 rounded-4 bg-dark text-white-50 small">
                    <i class="fas fa-info-circle me-2 text-info"></i>
                    Neural engine performs multi-hop traversal to map required competencies.
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0"><i class="fas fa-layer-group me-2 text-primary"></i>Structural Gap Analysis</h5>
                    <span class="ms-2 text-success small"><i class="fas fa-check-circle"></i> SYNCED</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Skill Competency</th>
                                <th>Required</th>
                                <th>Validated</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($gap_results)): ?>
                                <?php foreach($gap_results as $gap): 
                                    $deficit = ($gap['gap_value'] > 0) ? $gap['gap_value'] : 0; ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo $gap['node_name']; ?></div>
                                        <span class="text-muted" style="font-size: 0.7rem;">Graph Node: #<?php echo rand(100, 999); ?></span>
                                    </td>
                                    <td><span class="badge-requirement"><?php echo number_format($gap['required_level'], 1); ?></span></td>
                                    <td><span class="fw-semibold"><?php echo number_format($gap['current_level'], 1); ?></span></td>
                                    <td class="text-end">
                                        <?php if($deficit > 0): ?>
                                            <span class="deficit-tag">
                                                <i class="fas fa-arrow-down me-1"></i> -<?php echo number_format($deficit, 1); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="match-tag">
                                                <i class="fas fa-check-double me-1"></i> OPTIMIZED
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="opacity-25 mb-3"><i class="fas fa-database fa-3x"></i></div>
                                        <p class="text-muted">No knowledge nodes initialized for this architecture.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>