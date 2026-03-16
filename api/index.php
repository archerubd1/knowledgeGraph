<?php
session_start();
include('../config/db.php');

// If already logged in, redirect to dashboard automatically
if (isset($_SESSION['user_id'])) { 
    header("Location: dashboard.php"); 
    exit(); 
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Professional Query (Note: Consider password_verify() for production)
    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $email;
       $_SESSION['user_name'] = isset($user['full_name']) ? $user['full_name'] : 'User';
        
        header("Location: dashboard.php"); 
        exit();
    } else {
        $error = "Invalid credentials. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Astraal AKIB</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #4361ee;
            --deep-navy: #1e3c72;
            --accent-purple: #7209b7;
            --glass: rgba(255, 255, 255, 0.92);
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
        }

        /* Animated Background */
        body {
            background: linear-gradient(-45deg, #1e3c72, #2a5298, #4361ee, #3a0ca3);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--glass);
            backdrop-filter: blur(15px);
            padding: 45px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }

        .auth-card:hover {
            transform: translateY(-5px);
        }

        .logo-area {
            margin-bottom: 35px;
        }

        .logo-text {
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #1e3c72 0%, #4361ee 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.2rem;
            margin-bottom: 0;
        }

        .subtitle {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #64748b;
            font-weight: 600;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-left: 5px;
        }

        .form-control {
            border-radius: 14px;
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15);
        }

        .btn-login {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.25);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.4);
            filter: brightness(1.1);
        }

        .alert {
            border-radius: 12px;
            font-size: 0.85rem;
            border: none;
            font-weight: 500;
        }

        .footer-link {
            color: #64748b;
            font-size: 0.9rem;
        }

        .footer-link a {
            color: var(--primary-blue);
            font-weight: 700;
            text-decoration: none;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }

        /* Input Icon simulation */
        .input-group-text {
            background: none;
            border: none;
            padding-right: 0;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="text-center logo-area">
            <h1 class="logo-text">ASTRAAL</h1>
            <p class="subtitle">AKIB Intelligence</p>
        </div>
        
        <?php if($error): ?> 
            <div class="alert alert-danger text-center animate__animated animate__shakeX">
                <?php echo $error; ?>
            </div> 
        <?php endif; ?>

        <?php if(isset($_GET['status'])): ?> 
            <div class="alert alert-success text-center">
                Registration successful! Log in below.
            </div> 
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@gmail.com" required>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between">
                    <label class="form-label">Password</label>
                    
                </div>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-login">Sign In</button>
            </div>

            <div class="text-center footer-link">
                <span>Don't have an account?</span><br>
                <a href="register.php">Create Astraal Identity</a>
            </div>
        </form>
    </div>

</body>
</html>