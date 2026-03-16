<?php
session_start();
include('../config/db.php');

// 1. Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$node_id = isset($_GET['node_id']) ? mysqli_real_escape_string($conn, $_GET['node_id']) : null;

if (!$node_id) {
    header("Location: skills.php");
    exit();
}

// 2. Fetch Skill Name from Knowledge Graph
$node_info_query = "SELECT node_name FROM kg_nodes WHERE node_id = '$node_id'";
$node_res = mysqli_query($conn, $node_info_query);
if (!$node_res || mysqli_num_rows($node_res) == 0) {
    die("Error: Skill Node not found in AKIB.");
}
$skill = mysqli_fetch_assoc($node_res);

// 3. Fetch 10 RANDOM Questions for the assessment
$questions_query = "SELECT * FROM assessment_questions 
                    WHERE node_id = '$node_id' 
                    ORDER BY RAND() 
                    LIMIT 10";
$questions = mysqli_query($conn, $questions_query);

// 4. Handle Submission - OVERWRITE LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['answers'])) {
    $correct_count = 0;
    $total_q = count($_POST['answers']);

    foreach ($_POST['answers'] as $q_id => $user_ans_letter) {
        $q_id = mysqli_real_escape_string($conn, $q_id);
        $check = mysqli_query($conn, "SELECT correct_option FROM assessment_questions WHERE id = '$q_id'");
        if ($row = mysqli_fetch_assoc($check)) {
            // Compare the submitted letter with the stored correct letter
            if ($user_ans_letter == $row['correct_option']) { 
                $correct_count++; 
            }
        }
    }

    $score = ($total_q > 0) ? ($correct_count / $total_q) : 0;

    $update_sql = "INSERT INTO learner_skill_validation (learner_id, skill_node_id, validation_score, validated_on) 
                   VALUES ('$user_id', '$node_id', '$score', NOW()) 
                   ON DUPLICATE KEY UPDATE 
                   validation_score = '$score', 
                   validated_on = NOW()";
    
    if(mysqli_query($conn, $update_sql)) {
        header("Location: skills.php?validated=$node_id&marks=$correct_count");
        exit();
    } else {
        die("Critical Database Error: " . mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment | <?php echo htmlspecialchars($skill['node_name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --astraal-blue: #4361ee;
            --astraal-gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --correct-bg: #dcfce7;
            --correct-border: #22c55e;
            --wrong-bg: #fee2e2;
            --wrong-border: #ef4444;
        }

        body {
            background-color: #f0f2f5;
            background-image: radial-gradient(#4361ee10 1px, transparent 1px);
            background-size: 20px 20px;
            font-family: 'Poppins', sans-serif;
            color: #2b2d42;
        }

        .top-nav {
            padding: 20px 0;
            display: flex;
            justify-content: flex-end;
            margin-bottom: -40px;
        }

        .btn-exit {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 10px 25px;
            border-radius: 12px;
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-exit:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
        }

        .main-container {
            max-width: 900px;
            margin-top: 50px;
        }

        .skill-title {
            font-weight: 700;
            background: var(--astraal-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .question-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .question-number {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--astraal-blue);
            font-weight: 600;
            display: block;
            margin-bottom: 10px;
        }

        .option-container {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            margin-top: 15px;
            border: 2px solid #edf2f7;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fff;
            font-weight: 500;
        }

        .option-container:hover {
            border-color: var(--astraal-blue);
            transform: translateX(8px);
            background: #f8faff;
        }

        .option-letter {
            width: 35px;
            height: 35px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: 700;
            color: var(--astraal-blue);
        }

        .correct-selected {
            background-color: var(--correct-bg) !important;
            border-color: var(--correct-border) !important;
            color: #166534 !important;
        }
        .correct-selected .option-letter { background: #bbf7d0; }

        .wrong-selected {
            background-color: var(--wrong-bg) !important;
            border-color: var(--wrong-border) !important;
            color: #991b1b !important;
        }
        .wrong-selected .option-letter { background: #fecaca; }

        .disabled-card { pointer-events: none; }

        .btn-submit {
            background: var(--astraal-gradient);
            border: none;
            padding: 18px 60px;
            border-radius: 18px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
            transition: all 0.3s ease;
            margin-bottom: 80px;
        }

        .btn-submit:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 25px rgba(67, 97, 238, 0.4);
            color: white;
        }
    </style>
</head>
<body>

<div class="container main-container">
    <div class="top-nav">
        <a href="skills.php" class="btn-exit" onclick="return confirm('Are you sure you want to exit? Your current progress will not be saved.');">
            ✕ Exit Quiz
        </a>
    </div>

    <div class="text-center mb-5">
        <h1 class="skill-title"><?php echo htmlspecialchars($skill['node_name']); ?></h1>
        <p class="text-muted fw-medium">Knowledge Intelligence Validation</p>
    </div>

    <?php if (mysqli_num_rows($questions) > 0): ?>
        <form method="POST" id="quizForm">
            <?php $i = 1; while($q = mysqli_fetch_assoc($questions)): 
                // SOLUTION 1: Create array of options and shuffle them
                $options_pool = [
                    ['orig_letter' => 'A', 'text' => $q['option_a']],
                    ['orig_letter' => 'B', 'text' => $q['option_b']],
                    ['orig_letter' => 'C', 'text' => $q['option_c']],
                    ['orig_letter' => 'D', 'text' => $q['option_d']]
                ];

                // Identify the correct text string based on the correct_option letter
                $correct_letter = $q['correct_option'];
                $correct_text = $q['option_' . strtolower($correct_letter)];

                shuffle($options_pool);
            ?>
                <div class="question-card" data-answered="false">
                    <span class="question-number">Question <?php echo $i++; ?></span>
                    <h4 class="fw-bold mb-4"><?php echo htmlspecialchars($q['question_text']); ?></h4>
                    
                    <div class="options-list">
                        <?php 
                        $display_labels = ['A', 'B', 'C', 'D']; 
                        foreach($options_pool as $idx => $opt): 
                            $current_label = $display_labels[$idx];
                            // Check if this shuffled option is the correct one
                            $is_this_correct = ($opt['text'] === $correct_text) ? "true" : "false";
                        ?>
                            <div class="option-container" 
                                 onclick="handleChoice(this, '<?php echo $current_label; ?>', '<?php echo $is_this_correct; ?>')">
                                
                                <input class="form-check-input d-none" type="radio" 
                                       name="answers[<?php echo $q['id']; ?>]" 
                                       value="<?php echo $opt['orig_letter']; ?>" required>
                                
                                <div class="option-letter"><?php echo $current_label; ?></div>
                                <div class="option-text"><?php echo htmlspecialchars($opt['text']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <div class="text-center">
                <button type="submit" class="btn btn-submit">
                    Submit Final Assessment
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="question-card text-center">
            <h4 class="text-muted">No questions found in the graph.</h4>
            <a href="skills.php" class="btn btn-primary mt-3">Back to Skills</a>
        </div>
    <?php endif; ?>
</div>

<script>
// SOLUTION 2: Updated JS to handle the 'true/false' truth check
function handleChoice(element, selectedLabel, isCorrect) {
    const card = element.closest('.question-card');
    if (card.getAttribute('data-answered') === "true") return;

    card.setAttribute('data-answered', "true");
    card.classList.add('disabled-card');

    const options = card.querySelectorAll('.option-container');
    
    options.forEach(opt => {
        // We look inside the onclick attribute to see which one we marked as 'true' in PHP
        const checkTruth = opt.getAttribute('onclick');
        
        if (checkTruth.includes("'true'")) {
            opt.classList.add('correct-selected');
        } else if (opt === element && isCorrect === 'false') {
            opt.classList.add('wrong-selected');
        }
    });

    element.querySelector('input').checked = true;
}
</script>

</body>
</html>