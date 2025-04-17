<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swim Data Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #e0f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            width: 300px;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .login-form input[type="submit"] {
            padding: 10px 15px;
            margin: 5px 3px;
            border: none;
            border-radius: 5px;
            background-color: #00796b;
            color: white;
            cursor: pointer;
        }
        .login-form input[type="submit"]:hover {
            background-color: #004d40;
        }
        .login-form a {
            display: block;
            margin-top: 15px;
            color: #00796b;
            text-decoration: none;
            font-size: 0.9em;
        }
        .login-form a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Swim Data Login</h2>
        <form method="post" action="login.php">
            <input type="text" name="userID" placeholder="User ID" required>
            <input type="password" name="plainPassword" placeholder="Password" required>
            <input type="submit" name="swimmerLog" value="Swimmer">
            <input type="submit" name="coachLog" value="Coach">
            <input type="submit" name="adminLog" value="Administrator">
        </form>
        <a href="login.php?action=forgot">Forgot Password?</a>
        <a href="login.php?action=create">Create Account</a>
    </div>
</body>
</html>
