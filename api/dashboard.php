<?php
session_start();
include('../config/db.php');

// Security Check - Session validation
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not set, or keep your development dummy logic
    // $_SESSION['user_id'] = 1; 
}

$user_id = $_SESSION['user_id'];

// --- STEP 2 FIX: Robust PHP Data Fetching with Error Checking ---
// 1. Fetch Current Active Target and Readiness Score
$readiness_query = "SELECT lat.career_node_id, n.node_name, crs.readiness_score 
                    FROM learner_active_target lat
                    JOIN kg_nodes n ON lat.career_node_id = n.node_id
                    LEFT JOIN career_readiness_scores crs 
                        ON lat.learner_id = crs.learner_id 
                        AND lat.career_node_id = crs.target_node 
                    WHERE lat.learner_id = '$user_id' 
                    ORDER BY crs.calculated_on DESC LIMIT 1";

$readiness_result = mysqli_query($conn, $readiness_query);

if (!$readiness_result) {
    die("Database Error: " . mysqli_error($conn));
}

// Initialize default values to avoid errors if the query fails or returns nothing
$active_role_name = "No Target Set";
$readiness = 0.0;
$target_id = 0;

if ($readiness_result && mysqli_num_rows($readiness_result) > 0) {
    $row_data = mysqli_fetch_assoc($readiness_result);
    $active_role_name = $row_data['node_name'];
    $readiness = isset($row_data['readiness_score']) ? $row_data['readiness_score'] : 0.0; 
    $target_id = $row_data['career_node_id'];
} else {
    // Optional: Log the error to see why it failed if needed during development
    // echo mysqli_error($conn); 
}

// 2. Fetch Skill Gaps for the SPECIFIC active target
$gap_query = "SELECT n.node_name, sgr.gap_value, sgr.required_level, sgr.current_level 
              FROM skill_gap_results sgr 
              JOIN kg_nodes n ON sgr.skill_node = n.node_id 
              WHERE sgr.learner_id = '$user_id' AND sgr.target_node = '$target_id'";
$gaps = mysqli_query($conn, $gap_query);

// 3. Aggregate Stats for Summary
$total_skills_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM learner_skills WHERE learner_id = '$user_id'");
$total_skills = mysqli_fetch_assoc($total_skills_q)['count'];

// Fetch Skill Status (Validated or Gap) for the active target
$status_query = "SELECT 
                    n.node_name, 
                    csr.required_level, 
                    COALESCE(lsv.validation_score, 0) as current_level,
                    (csr.required_level - COALESCE(lsv.validation_score, 0)) as gap_value
                 FROM career_skill_requirements csr
                 JOIN kg_nodes n ON csr.skill_node_id = n.node_id
                 LEFT JOIN learner_skill_validation lsv 
                    ON lsv.skill_node_id = n.node_id AND lsv.learner_id = '$user_id'
                 WHERE csr.career_node_id = '$target_id'";
                 
                
// Query to fetch gap analysis for the dashboard table
$gap_query = "
    SELECT 
        kn.node_name as skill_name,
        sgr.required_level,
        sgr.current_level,
        sgr.gap_value
    FROM skill_gap_results sgr
    JOIN kg_nodes kn ON sgr.skill_node = kn.node_id
    WHERE sgr.learner_id = ? AND sgr.target_node = ?
    ORDER BY sgr.gap_value DESC";

    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astraal AKIB | Intelligence Dashboard</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --secondary: #64748b;
            --dark-bg: #0f172a;
            --sidebar-width: 280px;
            --glass: rgba(255, 255, 255, 0.95);
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            overflow-x: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--dark-bg);
            color: white;
            position: fixed;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 10px 0 30px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 2.5rem 1.5rem;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(to right, #60a5fa, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-container {
            flex-grow: 1;
            padding: 0 1rem;
        }

        .nav-link {
            color: #94a3b8;
            padding: 12px 20px;
            margin-bottom: 8px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
        }

        .nav-link i {
            margin-right: 15px;
            font-size: 1.1rem;
            width: 25px;
            text-align: center;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--primary);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 2.5rem;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--glass);
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 1.8rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            height: 100%;
            transition: transform 0.3s;
        }

        .stat-card:hover { transform: translateY(-5px); }

        .gauge-wrapper {
            position: relative;
            width: 160px;
            margin: 0 auto;
        }

        .gauge-svg { transform: rotate(-90deg); }

        .gauge-bg {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 12;
        }

        .gauge-fill {
            fill: none;
            stroke: var(--primary);
            stroke-width: 12;
            stroke-linecap: round;
            stroke-dasharray: 440;
            stroke-dashoffset: 440;
            transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .gauge-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark-bg);
        }

        .custom-table {
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .custom-table tr {
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .custom-table td, .custom-table th {
            padding: 1.2rem;
            vertical-align: middle;
        }

        .custom-table tr td:first-child { border-radius: 15px 0 0 15px; }
        .custom-table tr td:last-child { border-radius: 0 15px 15px 0; }

        .badge-status {
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .bg-gap-high { background: #fee2e2; color: #ef4444; }
        .bg-gap-low { background: #fef9c3; color: #854d0e; }
        .bg-success-soft { background: #dcfce7; color: #166534; }

        .live-dot {
            height: 10px;
            width: 10px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            box-shadow: 0 0 8px #10b981;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.3; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">ASTRAAL AKIB</div>
    <div class="nav-container">
        <a href="dashboard.php" class="nav-link active">
            <i class="fas fa-columns"></i> Dashboard
        </a>

        <a href="skills.php" class="nav-link">
            <i class="fas fa-brain"></i> Competencies
        </a>

        <a href="career.php" class="nav-link">
            <i class="fas fa-user-tie"></i> Career Path
        </a>

        <a href="learning-path.php" class="nav-link">
            <i class="fas fa-map-signs"></i> Learning Path
        </a>

        <a href="certificates.php" class="nav-link">
            <i class="fas fa-medal"></i> Recommended Certifications
        </a>
        <a href="network.php" class="nav-link">
            <i class="fas fa-project-diagram"></i> Knowledge Network
        </a>

        
    </div>

    <div class="p-4">
        <a href="logout.php" class="nav-link text-danger">
            <i class="fas fa-power-off"></i> Logout
        </a>
    </div>
</aside>

<main class="main-content">
    <div class="top-bar d-flex justify-content-between align-items-center mb-5">
        <div>
    <h1 class="mb-1" style="
    font-family: 'Montserrat', sans-serif;
    letter-spacing: -3px;
    color: #1e293b; /* Dark Slate Blue */
    font-size: 3.2rem;
    font-weight: 900;
    text-transform: capitalize;
    text-shadow: 4px 4px 0px rgba(30, 58, 138, 0.1);
    ">
    Hello, <?php echo explode('@', $_SESSION['email'])[0]; ?>
    </h1>
            <div class="d-flex align-items-center gap-3">
                <p class="text-muted small fw-bold mb-0 text-uppercase tracking-widest">
                    <span class="live-dot"></span> Astraal Intelligence Backbone Active
                </p>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" style="font-size: 0.7rem;" onclick="location.reload();">
                    <i class="fas fa-sync-alt me-1"></i> REFRESH DATA
                </button>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <div class="text-end me-3">
                <div class="fw-bold"><?php echo $_SESSION['email']; ?></div>
                <small class="text-muted">Premium Learner</small>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?php echo $_SESSION['email']; ?>&background=2563eb&color=fff" class="rounded-circle" width="45">
        </div>
    </div>

    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card border-0 shadow-sm p-4 h-100" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: white; border-radius: 24px;">
                <small class="text-white-50 fw-bold tracking-widest text-uppercase">Active Objective</small>
                <h2 class="fw-900 m-0 mt-2 text-truncate"><?php echo $active_role_name; ?></h2>
                
                <div class="mt-4">
                    <span class="badge bg-primary-subtle text-primary p-2 px-3 fw-bold">
                        <i class="fas fa-id-badge me-1"></i> Role: <?php echo $active_role_name; ?>
                    </span>
                </div>
                
                <div class="mt-3 opacity-50 extra-small fw-bold">NODE ID: #<?php echo $target_id; ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card border-0 shadow-sm bg-white p-4 h-100" style="border-radius: 24px;">
                <small class="text-muted fw-bold tracking-widest text-uppercase">Total Skills Count</small>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h2 class="fw-900 m-0 text-primary">25</h2>
                    <span class="text-muted small fw-bold">Nodes</span>
                </div>
                <p class="extra-small text-success mb-0 mt-4 fw-bold">
                    <i class="fas fa-link me-1"></i> Edge Mapping: Synchronized
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card border-0 shadow-sm bg-white p-4 h-100" style="border-radius: 24px;">
                <small class="text-muted fw-bold tracking-widest text-uppercase">Total Career Roles</small>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h2 class="fw-900 m-0 text-dark">23</h2>
                    <span class="text-muted small fw-bold">Available</span>
                </div>
                <p class="extra-small text-muted mb-0 mt-4 fw-bold">
                    <i class="fas fa-project-diagram me-1"></i> Global Path Architecture
                </p>
            </div>
        </div>
    </div>


    <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e1e8ed;">
    
    <div style="border-bottom: 2px solid #3182ce; margin-bottom: 25px; padding-bottom: 15px;">
        <h2 style="color: #2d3748; margin: 0; font-size: 1.6rem; letter-spacing: -0.5px;">Top Demanding Skills</h2>
        <p style="color: #718096; font-size: 13px; margin: 5px 0 0 0; text-transform: uppercase; font-weight: 600;">Structural Knowledge Graph Nodes</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;">
        
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div style="background: #fdfdfd; padding: 20px; border-radius: 8px; border-left: 5px solid #3182ce; box-shadow: 2px 2px 10px rgba(0,0,0,0.02);">
                <div style="font-weight: 700; color: #1a202c; font-size: 15px;">Cloud-Native Architecture</div>
                <div style="font-size: 12px; color: #4a5568; margin-top: 6px; font-family: monospace;">Node ID: #89</div>
            </div>
            <div style="background: #fdfdfd; padding: 20px; border-radius: 8px; border-left: 5px solid #3182ce; box-shadow: 2px 2px 10px rgba(0,0,0,0.02);">
                <div style="font-weight: 700; color: #1a202c; font-size: 15px;">Ethical Hacking</div>
                <div style="font-size: 12px; color: #4a5568; margin-top: 6px; font-family: monospace;">Node ID: #74</div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div style="background: #fdfdfd; padding: 20px; border-radius: 8px; border-left: 5px solid #3182ce; box-shadow: 2px 2px 10px rgba(0,0,0,0.02);">
                <div style="font-weight: 700; color: #1a202c; font-size: 15px;">Deep Learning Architecture</div>
                <div style="font-size: 12px; color: #4a5568; margin-top: 6px; font-family: monospace;">Node ID: #69</div>
            </div>
            <div style="background: #fdfdfd; padding: 20px; border-radius: 8px; border-left: 5px solid #3182ce; box-shadow: 2px 2px 10px rgba(0,0,0,0.02);">
                <div style="font-weight: 700; color: #1a202c; font-size: 15px;">Exploratory Data Analysis</div>
                <div style="font-size: 12px; color: #4a5568; margin-top: 6px; font-family: monospace;">Node ID: #66</div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div style="background: #fdfdfd; padding: 20px; border-radius: 8px; border-left: 5px solid #3182ce; box-shadow: 2px 2px 10px rgba(0,0,0,0.02);">
                <div style="font-weight: 700; color: #1a202c; font-size: 15px;">Edge Computing Deployment</div>
                <div style="font-size: 12px; color: #4a5568; margin-top: 6px; font-family: monospace;">Node ID: #84</div>
            </div>
            <div style="background: #fdfdfd; padding: 20px; border-radius: 8px; border-left: 5px solid #3182ce; box-shadow: 2px 2px 10px rgba(0,0,0,0.02);">
                <div style="font-weight: 700; color: #1a202c; font-size: 15px;">Microservices Orchestration</div>
                <div style="font-size: 12px; color: #4a5568; margin-top: 6px; font-family: monospace;">Node ID: #71</div>
            </div>
        </div>

    </div>
</div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const readinessValue = <?php echo $readiness; ?>; 
        const circle = document.getElementById('readinessProgress');
        const textLabel = document.getElementById('readinessPct');
        
        const circumference = 2 * Math.PI * 70;
        const offset = circumference - (readinessValue * circumference);
        
        setTimeout(() => {
            circle.style.strokeDashoffset = offset;
            let count = 0;
            let targetPct = Math.floor(readinessValue * 100);
            if (targetPct > 0) {
                let interval = setInterval(() => {
                    if(count >= targetPct) clearInterval(interval);
                    textLabel.innerText = count + '%';
                    count += 1;
                }, 20);
            } else {
                textLabel.innerText = '0%';
            }
        }, 500);
    });

    const currentPath = window.location.pathname.split("/").pop();
    document.querySelectorAll('.nav-link').forEach(link => {
        if(link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
    
    
    
</script>

</body>
</html>