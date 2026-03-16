<?php
session_start();
include('../config/db.php');

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; 

    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $message = "<div class='alert-custom error'>Conflict: Identity already provisioned.</div>";
    } else {
        $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
        if (mysqli_query($conn, $sql)) {
            header("Location: index.php?status=registered");
            exit();
        } else {
            $message = "<div class='alert-custom error'>System Error: Registration failed.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Astraal AKIB</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #4361ee;
            --brand-secondary: #3a0ca3;
            --bg-dark: #0f172a;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Moving Gradient Background */
        body {
            background: linear-gradient(-45deg, #0f172a, #1e293b, #1e1b4b, #020617);
            background-size: 400% 400%;
            animation: gradientMove 15s ease infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 45px;
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            color: #fff;
        }

        .logo-text {
            font-weight: 800;
            font-size: 2rem;
            letter-spacing: -1px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--brand-primary);
            font-weight: 700;
            margin-bottom: 30px;
            display: block;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 8px;
            margin-left: 2px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 14px 18px;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15);
            color: #fff;
        }

        .form-control::placeholder {
            color: #475569;
        }

        .btn-primary {
            background: var(--brand-primary);
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
            margin-top: 10px;
        }

        .btn-primary:hover {
            background: #3651d1;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.3);
        }

        .alert-custom {
            padding: 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .footer-link {
            margin-top: 25px;
            font-size: 0.85rem;
            color: #64748b;
        }

        .footer-link a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .footer-link a:hover {
            color: var(--brand-primary);
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="text-center">
            <h1 class="logo-text">ASTRAAL</h1>
            <span class="subtitle">Intelligence Backbone</span>
        </div>

        <?php echo $message; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Handle" required>
            </div>

            <div class="mb-3">
                <label class="form-label">System Email</label>
                <input type="email" name="email" class="form-control" placeholder="username@gmail.com" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Security Key</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Provision Account</button>
            </div>

            <div class="text-center footer-link">
                Already registered? <a href="index.php">Authenticate</a>
            </div>
        </form>
    </div>

</body>
</html>