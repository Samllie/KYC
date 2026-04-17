<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$loginPath = ($basePath === '' ? '' : $basePath) . '/app/auth/login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>">
    <title>KYC System - Login</title>
</head>
<body>
    <p>If you are not redirected, <a href="<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>">click here</a>.</p>
    <script>
        window.location.href = <?php echo json_encode($loginPath, JSON_UNESCAPED_SLASHES); ?>;
    </script>
</body>
</html>