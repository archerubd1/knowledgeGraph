<?php
include('../config/db.php');
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// 1. FETCH THE CURRENT ACTIVE TARGET
$target_q = mysqli_query($conn, "SELECT career_node_id FROM learner_active_target WHERE learner_id = '$user_id' ORDER BY activated_at DESC LIMIT 1");
$active_target = mysqli_fetch_assoc($target_q);
$target_id = $active_target ? $active_target['career_node_id'] : 0;

$nodes = [];
$links = [];

if ($target_id > 0) {
    // UPDATED QUERY: Includes relationship_description aliased as 'intel'
    $scoped_query = "
        SELECT n.node_id, n.node_name, n.node_type, 0 as current_score,
               COALESCE(e.relationship_description, 'Critical foundational component for this career architecture.') as intel
        FROM kg_nodes n 
        LEFT JOIN kg_edges e ON n.node_id = e.to_node AND e.from_node = '$target_id'
        WHERE n.node_id = '$target_id'
        
        UNION ALL
        
       SELECT n.node_id, n.node_name, n.node_type, MAX(COALESCE(v.validation_score, 0)) as current_score,
               CASE 
                    WHEN n.node_name = 'Agile Methodology' THEN 'Enables high-velocity project delivery through iterative cycles. Essential for maintaining flexibility in dynamic product environments.'
                    WHEN n.node_name = 'Cloud-Native Architecture & Resilience' THEN 'The blueprint for modern scalability. Ensures the intelligence backbone remains available and performs under extreme traffic loads.'
                    WHEN n.node_name = 'Continuous Integration (CI/CD)' THEN 'Automates structural integrity checks and deployment. Vital for maintaining a zero-downtime evolution of the software architecture.'
                    WHEN n.node_name = 'Cryptographic Implementation' THEN 'Hardens the data layer against exfiltration. Ensures that all learner progress and institutional data remain mathematically secure.'
                    WHEN n.node_name = 'Deep Learning Architecture' THEN 'The cognitive core of AI. Allows for the modeling of complex non-linear relationships within the knowledge graph.'
                    WHEN n.node_name = 'Edge Computing Deployment' THEN 'Reduces systemic latency by processing intelligence at the network periphery, critical for real-time career responsiveness.'
                    WHEN n.node_name = 'Ethical Hacking & Penetration Testing' THEN 'A proactive defense strategy. Simulates adversarial attacks to identify and patch vulnerabilities before they become liabilities.'
                    WHEN n.node_name = 'Exploratory Data Analysis' THEN 'The primary diagnostic tool for data science. Uncovers hidden patterns and anomalies that guide the direction of model training.'
                    WHEN n.node_name = 'Feature Engineering' THEN 'Refines raw data into high-signal inputs. Directly influences the accuracy and predictive power of the AI engines.'
                    WHEN n.node_name = 'Incident Response Management' THEN 'The organizational immune system. Provides a structured protocol for neutralizing security threats and restoring normal operations.'
                    WHEN n.node_name = 'IoT System Integration' THEN 'Links physical sensor data with digital intelligence, expanding the sensory perimeter of the Astraal ecosystem.'
                    WHEN n.node_name = 'Microservices Orchestration' THEN 'Manages the complexity of distributed components, ensuring that each microservice scales and recovers automatically.'
                    WHEN n.node_name = 'Model Deployment' THEN 'Bridges the gap between research and reality. Manages the lifecycle of machine learning models in a live production environment.'
                    WHEN n.node_name = 'Natural Language Processing' THEN 'Enables the backbone to interpret human context, enabling advanced semantic search and conversational intelligence.'
                    WHEN n.node_name = 'Neural Network Architecture' THEN 'Defines the structural depth of learning. Higher proficiency allows for more sophisticated pattern recognition.'
                    WHEN n.node_name = 'Quantum Programming' THEN 'Next-generation computation. Prepares the architecture for solving complex optimization problems using quantum gates.'
                    WHEN n.node_name = 'RESTful API Design' THEN 'The connective tissue of the modern web. Standardizes how the frontend, database, and tools exchange information.'
                    WHEN n.node_name = 'Serverless Computing' THEN 'Optimizes infrastructure costs by utilizing event-driven execution. Allows for massive scaling without managing physical servers.'
                    WHEN n.node_name = 'Smart Contract Development' THEN 'Automates trust through blockchain. Ensures that certifications and skill validations are immutable and globally verifiable.'
                    WHEN n.node_name = 'Stakeholder Management' THEN 'Navigates the human element of technology. Ensures that technical roadmaps align with institutional goals.'
                    WHEN n.node_name = 'Statistical Modeling' THEN 'Ensures data-driven rigor. Validates that career recommendations are based on proven mathematical distributions.'
                    WHEN n.node_name = 'Strategic Financial Modeling' THEN 'Aligns technology with fiscal reality. Predicts the economic viability and ROI of technical implementations.'
                    WHEN n.node_name = 'Supervised Learning' THEN 'The foundation of predictive AI. Uses labeled historical data to train the system to accurately forecast future trends.'
                    WHEN n.node_name = 'Threat Modeling' THEN 'Structural risk assessment. Identifies potential failure points in the architecture before they can be exploited.'
                    WHEN n.node_name = 'User Research & Persona Mapping' THEN 'Ensures the technology serves the person. Maps features to the specific behaviors and goals of the learner.'
                    ELSE 'Essential competency node within the current career trajectory.'
               END as intel
        FROM kg_nodes n
        INNER JOIN kg_edges e ON n.node_id = e.to_node
        LEFT JOIN learner_skill_validation v ON n.node_id = v.skill_node_id AND v.learner_id = '$user_id'
        WHERE e.from_node = '$target_id' AND n.node_type IN ('skill', 'competency')
        GROUP BY n.node_id
        UNION ALL
        
        SELECT node_id, node_name, node_type, current_score, intel FROM (
            SELECT n.node_id, n.node_name, n.node_type, 0 as current_score,
                   COALESCE(e.relationship_description, 'Industry-standard certification to verify domain expertise.') as intel
            FROM kg_nodes n
            INNER JOIN kg_edges e ON n.node_id = e.to_node
            WHERE e.from_node = '$target_id' AND n.node_type = 'certification'
            LIMIT 5
        ) AS cert_subquery";

    $nodes_res = mysqli_query($conn, $scoped_query);
    while($row = mysqli_fetch_assoc($nodes_res)) {
        $nodes[$row['node_id']] = [
            'id' => (int)$row['node_id'],
            'name' => strtoupper($row['node_name']),
            'type' => $row['node_type'],
            'score' => (float)$row['current_score'],
            'intel' => $row['intel'] 
        ];
    }

    $node_ids_string = implode(',', array_keys($nodes));
    if(!empty($node_ids_string)) {
        $edges_query = "SELECT from_node, to_node FROM kg_edges WHERE from_node IN ($node_ids_string) AND to_node IN ($node_ids_string)";
        $edges_res = mysqli_query($conn, $edges_query);
        while($row = mysqli_fetch_assoc($edges_res)) {
            $links[] = ['source' => (int)$row['from_node'], 'target' => (int)$row['to_node']];
        }
    }
}
$roles_list = mysqli_query($conn, "SELECT node_id, node_name FROM kg_nodes WHERE node_type = 'career_role' ORDER BY node_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AKIB | Intelligence Core</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg: #010409; 
            --career: #1a4fcf; 
            --skill: #00ff95; 
            --cert: #ffb300;
            --glass: rgba(13, 17, 23, 0.98);
        }
        body { 
            background: var(--bg); 
            background-image: 
                linear-gradient(rgba(255,255,255,0.03) 1.5px, transparent 1.5px), 
                linear-gradient(90deg, rgba(255,255,255,0.03) 1.5px, transparent 1.5px);
            background-size: 60px 60px;
            color: #f0f6fc; margin: 0; overflow: hidden; font-family: 'Plus Jakarta Sans', sans-serif; 
        }
        
        /* Intelligence Sidebar - HD Glassmorphism */
        #intel-sidebar {
            position: absolute; right: -450px; top: 0; bottom: 0; width: 420px;
            background: rgba(10, 12, 16, 0.95); backdrop-filter: blur(40px);
            border-left: 1px solid rgba(255,255,255,0.1);
            transition: 0.7s cubic-bezier(0.19, 1, 0.22, 1);
            padding: 50px 40px; z-index: 1000;
            box-shadow: -20px 0 50px rgba(0,0,0,0.8);
        }
        #intel-sidebar.active { right: 0; }

        .intel-tag { 
            background: rgba(26, 79, 207, 0.15); color: #4e84ff; 
            padding: 8px 15px; border-radius: 8px; font-size: 0.7rem; 
            font-weight: 800; letter-spacing: 1.5px; border: 1px solid rgba(78, 132, 255, 0.3);
        }

        .intel-description {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
            padding: 25px; border-radius: 20px; margin-top: 30px; line-height: 1.8;
            font-size: 0.95rem; color: rgba(240, 246, 252, 0.8);
        }

        .hud-overlay {
            position: absolute; top: 30px; left: 30px; z-index: 100;
            background: var(--glass); backdrop-filter: blur(30px);
            padding: 35px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);
            width: 400px; box-shadow: 0 30px 60px rgba(0,0,0,0.9);
        }

        .legend-overlay {
            position: absolute; bottom: 30px; left: 30px; z-index: 100;
            background: var(--glass); padding: 25px; border-radius: 20px; 
            border: 2px solid rgba(255,255,255,0.15); backdrop-filter: blur(20px);
        }

        #network-svg { width: 100vw; height: 100vh; }

        .link { 
            stroke: #ffffff; stroke-opacity: 0.2; stroke-width: 2.5px; 
            stroke-dasharray: 12, 6; animation: flow 25s linear infinite;
        }
        @keyframes flow { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }

        .node { cursor: pointer; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        
        .active-target-node {
            stroke: #ffffff !important; stroke-width: 6px !important;
            filter: drop-shadow(0 0 25px var(--career)) drop-shadow(0 0 10px white);
            animation: pulse-glow 3s infinite alternate;
        }

        @keyframes pulse-glow {
            from { filter: drop-shadow(0 0 15px var(--career)) drop-shadow(0 0 5px white); }
            to { filter: drop-shadow(0 0 35px var(--career)) drop-shadow(0 0 15px white); }
        }

        .label { 
            fill: #ffffff; font-size: 16px; font-weight: 800; pointer-events: none; 
            letter-spacing: 0.8px; paint-order: stroke; stroke: #000000; stroke-width: 6px;
            text-transform: uppercase;
        }

        .status-dot { width: 16px; height: 16px; border-radius: 50%; display: inline-block; margin-right: 15px; vertical-align: middle; }
    </style>
</head>
<body>

<div id="intel-sidebar">
    <button type="button" class="btn-close btn-close-white float-end" onclick="closeIntel()"></button>
    <div class="mt-5">
        <span id="intel-type" class="intel-tag">ANALYTICS NODE</span>
        <h2 id="intel-name" class="fw-800 mt-3 mb-2" style="font-size: 2.2rem; letter-spacing: -1px;">--</h2>
        
        <div class="intel-description">
            <label class="text-white small fw-800 mb-3 d-block opacity-50" style="letter-spacing: 1px;">STRATEGIC CONTEXT</label>
            <p id="intel-body">Select a node within the backbone to extract professional intelligence and relationship mapping.</p>
        </div>

        
    </div>
</div>

<div class="hud-overlay">
    <h2 class="fw-800 mb-1" style="letter-spacing: -2px;">AKIB <span style="color:var(--career)">CORE</span></h2>
    <p class="text-white fw-bold small mb-4" style="letter-spacing: 2px; opacity: 0.6;">NODE TRAVERSAL ENGINE V3.0</p>
    
    <select onchange="syncWithCareer(this.value)" class="form-select bg-black text-white border-0 py-3 mb-4 rounded-3 fw-bold shadow-lg">
        <?php mysqli_data_seek($roles_list, 0); while($r = mysqli_fetch_assoc($roles_list)): ?>
            <option value="<?= $r['node_id'] ?>" <?= ($target_id == $r['node_id']) ? 'selected' : '' ?>><?= strtoupper($r['node_name']) ?></option>
        <?php endwhile; ?>
    </select>

    <div class="row g-2">
        <div class="col-12"><button onclick="location.href='career.php'" class="btn btn-primary w-100 py-3 fw-800 rounded-3 shadow" style="background:var(--career); border:none;">MANAGE STRATEGY</button></div>
    </div>
</div>

<div class="legend-overlay">
    <div class="d-flex flex-column gap-3">
        <div class="small fw-800 text-white"><span class="status-dot" style="background: var(--career); box-shadow: 0 0 15px var(--career); border: 2px solid white;"></span> ACTIVE TARGET CAREER</div>
        <div class="small fw-800 text-white"><span class="status-dot" style="background: var(--skill); box-shadow: 0 0 15px var(--skill);"></span> REQUIRED SKILLS</div>
        <div class="small fw-800 text-white"><span class="status-dot" style="background: var(--cert); box-shadow: 0 0 15px var(--cert);"></span> REQUIRED CERTIFICATES</div>
    </div>
</div>

<svg id="network-svg"></svg>



<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
    const data = {
        nodes: <?= json_encode(array_values($nodes)) ?>,
        links: <?= json_encode($links) ?>,
        activeTarget: <?= (int)$target_id ?>
    };

    const svg = d3.select("#network-svg");
    const container = svg.append("g");
    const width = window.innerWidth, height = window.innerHeight;

    const zoom = d3.zoom().scaleExtent([0.2, 4]).on("zoom", (e) => container.attr("transform", e.transform));
    svg.call(zoom);

    const simulation = d3.forceSimulation(data.nodes)
        .force("link", d3.forceLink(data.links).id(d => d.id).distance(300))
        .force("charge", d3.forceManyBody().strength(-4500))
        .force("center", d3.forceCenter(width / 2, height / 2))
        .force("collide", d3.forceCollide().radius(150));

    const link = container.append("g").selectAll("line")
        .data(data.links).join("line").attr("class", "link");

    const node = container.append("g").selectAll("circle")
        .data(data.nodes).join("circle")
        .attr("class", d => d.id === data.activeTarget ? "node active-target-node" : "node")
        .attr("r", d => d.id === data.activeTarget ? 70 : (d.type === 'certification' ? 32 : 28))
        .attr("fill", d => {
            if (d.id === data.activeTarget) return "var(--career)";
            return (d.type === 'certification') ? "var(--cert)" : "var(--skill)";
        })
        .on("click", (e, d) => openIntel(d))
        .call(d3.drag().on("start", dragStart).on("drag", dragging).on("end", dragEnd));

    const labels = container.append("g").selectAll("text")
        .data(data.nodes).join("text")
        .attr("class", "label")
        .attr("dy", d => d.id === data.activeTarget ? 110 : 85)
        .attr("text-anchor", "middle")
        .text(d => d.name);

    simulation.on("tick", () => {
        link.attr("x1", d => d.source.x).attr("y1", d => d.source.y).attr("x2", d => d.target.x).attr("y2", d => d.target.y);
        node.attr("cx", d => d.x).attr("cy", d => d.y);
        labels.attr("x", d => d.x).attr("y", d => d.y);
    });

    function openIntel(d) {
        document.getElementById('intel-sidebar').classList.add('active');
        document.getElementById('intel-name').innerText = d.name;
        document.getElementById('intel-type').innerText = d.type.toUpperCase() + " NODE";
        document.getElementById('intel-body').innerText = d.intel;
    }

    function closeIntel() { document.getElementById('intel-sidebar').classList.remove('active'); }

    function dragStart(e, d) { if (!e.active) simulation.alphaTarget(0.3).restart(); d.fx = d.x; d.fy = d.y; }
    function dragging(e, d) { d.fx = e.x; d.fy = e.y; }
    function dragEnd(e, d) { if (!e.active) simulation.alphaTarget(0); d.fx = null; d.fy = null; }

    function syncWithCareer(val) {
        const form = document.createElement('form'); form.method = 'POST'; form.action = 'career.php';
        const input = document.createElement('input'); input.type = 'hidden'; input.name = 'career_id'; input.value = val;
        const btn = document.createElement('input'); btn.type = 'hidden'; btn.name = 'set_target';
        form.appendChild(input); form.appendChild(btn); document.body.appendChild(form); form.submit();
    }
</script>
</body>
</html>