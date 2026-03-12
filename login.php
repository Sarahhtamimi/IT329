
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Palm Glow — Log in</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --matcha: #809671;
            --almond: #E5E0D8;
            --chai: #D2AB80;
            --carob: #725C3A;
            --ink: #2C2C2C;
            --border: rgba(114, 92, 58, .14);
            --shadow: 0 16px 40px rgba(114, 92, 58, .12);
            --shadow-soft: 0 10px 24px rgba(114, 92, 58, .10);
            --r-lg: 22px;
            --r-md: 16px;
            --r-pill: 999px;
            --container: 900px;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: Inter, system-ui, Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 550px at 15% 10%, rgba(128, 150, 113, .18), transparent 60%),
                radial-gradient(800px 550px at 85% 0%, rgba(210, 171, 128, .18), transparent 60%),
                linear-gradient(180deg, var(--almond), rgba(229, 224, 216, .75) 55%, var(--almond));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            width: min(var(--container), calc(100% - 32px));
            margin: 0 auto;
        }




.error-msg{
  margin: 10px 0 16px;
  padding: 10px 14px;
  border-radius: 14px;
  background: rgba(179, 38, 30, 0.08);
  border: 1px solid rgba(179, 38, 30, 0.18);
  color: #b3261e;
  font-size: 13px;
  font-weight: 600;
}


        main {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 26px 0;
        }

        .card {
            width: min(560px, 100%);
            border-radius: 26px;
            border: 1px solid rgba(114, 92, 58, .12);
            background: rgba(255, 255, 255, .70);
            box-shadow: var(--shadow);
            padding: 22px;
        }

        h1 {
            margin: 0 0 8px;
            font-family: "Cormorant Garamond", serif;
            font-size: 36px;
            color: var(--carob);
        }

        .sub {
            margin: 0 0 18px;
            font-size: 13.5px;
            opacity: .8;
            line-height: 1.7;
        }

        label {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--carob);
            display: block;
            margin: 12px 0 6px;
        }

        .field {
            width: 100%;
            padding: 12px 12px;
            border-radius: 16px;
            border: 1px solid rgba(114, 92, 58, .14);
            background: rgba(255, 255, 255, .70);
            outline: none;
            font-size: 14px;
        }

        .field:focus {
            border-color: rgba(128, 150, 113, .55);
            box-shadow: 0 0 0 4px rgba(128, 150, 113, .14);
        }

        .btn-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .btn {
            flex: 1 1 220px;
            padding: 12px 16px;
            border-radius: var(--r-pill);
            border: 1px solid rgba(114, 92, 58, .14);
            background: rgba(255, 255, 255, .60);
            cursor: pointer;
            font-weight: 800;
            transition: .2s;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        .btn-user {
            background: var(--matcha);
            border-color: rgba(128, 150, 113, .55);
            color: #fff;
        }

        .btn-admin {
            background: rgba(210, 171, 128, .55);
            border-color: rgba(210, 171, 128, .75);
            color: #2C2C2C;
        }

        .note {
            margin-top: 12px;
            font-size: 12.5px;
            opacity: .8;
            min-height: 16px;
        }
    </style>
</head>
<body>

    <main>
        <section class="card" aria-label="Log in form">
            <h1>Welcome back</h1>
            <p class="sub">Enter your email and password.</p>

            <?php if (isset($_GET['error'])) { ?>
                <p class="error-msg">
                    <?php
                        if ($_GET['error'] == 'missing_fields') echo "Please enter your email and password.";
                        elseif ($_GET['error'] == 'blocked_user') echo "This account is blocked.";
                        elseif ($_GET['error'] == 'wrong_email') echo "Email not found.";
                        elseif ($_GET['error'] == 'wrong_password') echo "Incorrect password.";
                        elseif ($_GET['error'] == 'please_login') echo "Please log in first.";
                        elseif ($_GET['error'] == 'unauthorized') echo "You are not authorized to access this page.";
                        else echo "Something went wrong.";
                    ?>
                </p>
            <?php } ?>

            <form id="loginForm" action="process_login.php" method="POST">
    <label for="email">Email address</label>
    <input class="field" id="email" name="email" type="email" placeholder="nouraAziz@gmail.com" required>

    <label for="password">Password</label>
    <input class="field" id="password" name="password" type="password" placeholder="••••••••" required>

    <div class="btn-row">
        <button class="btn btn-user" type="submit">Log in</button>
    </div>

    <div class="note" id="note" aria-live="polite"></div>
</form>
        </section>
    </main>

</body>
</html>

</html>