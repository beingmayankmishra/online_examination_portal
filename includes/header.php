<?php
// This file contains common header code that can be included in multiple pages
function getHeader($title = 'Mind Power University', $isAdmin = false) {
    $basePath = $isAdmin ? '../' : '';
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <link rel="stylesheet" href="<?php echo $basePath; ?>css/style.css">
    </head>
    <body>
        <div class="<?php echo $isAdmin ? 'admin-container' : 'container'; ?>">
            <header class="<?php echo $isAdmin ? 'admin-header' : ''; ?>">
                <h1>Mind Power University</h1>
                <h2><?php echo $isAdmin ? 'Admin Portal' : 'Online Examination Portal'; ?></h2>
                <?php if ($isAdmin && isset($_SESSION['admin_logged_in'])): ?>
                <div class="admin-welcome">
                    Welcome, Admin | <a href="logout.php" style="color: white;">Logout</a>
                </div>
                <?php endif; ?>
            </header>
    <?php
    return ob_get_clean();
}

function getFooter($isAdmin = false) {
    ob_start();
    ?>
            <footer>
                <p>&copy; 2025 Mind Power University. All rights reserved.</p>
            </footer>
        </div>
        
        <script src="<?php echo $isAdmin ? '../' : ''; ?>js/script.js"></script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>