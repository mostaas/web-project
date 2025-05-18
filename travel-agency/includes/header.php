<?php
// File: includes/header.php

// --- Session Management (from your client header) ---
// Ensure session is started. If you have a central db.php or config.php that always starts it,
// this might be redundant, but it's safe to keep for a standalone header.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Page Type Detection & Base Path (from my admin header) ---
$isAdminPage = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
$basePath = ''; // Initialize basePath

// Determine base path dynamically based on current script's location relative to project root
// This assumes 'includes' is one level down from the project root.
// And 'admin' and 'public' are also one level down.
$scriptDir = dirname($_SERVER['SCRIPT_FILENAME']); // Directory of the calling script
$projectRoot = dirname($scriptDir); // Assuming calling script is in /public/ or /admin/

if ($isAdminPage) {
    // If in admin folder (e.g., /travel-agency/admin/dashboard.php)
    // and includes is /travel-agency/includes/
    // then basePath to get to /travel-agency/ from /travel-agency/admin/ is '../'
    $basePath = '../';
} else {
    // If in public folder (e.g., /travel-agency/public/index.php)
    // and includes is /travel-agency/includes/
    // then basePath to get to /travel-agency/ from /travel-agency/public/ is '../'
    // If public files are at root, and includes is /includes, basePath might be './' or empty.
    // Let's assume public files like index.php are inside a /public/ directory for consistency
    // with the admin structure. If client's index.php is at the very root, this needs adjustment.
    // Given your client header's CSS path `../public/css/styles.css`, it implies the calling PHP
    // file is likely in a subdirectory (e.g., /account/login.php).

    // A more robust way if 'includes' is always at the root level of accessible files from web.
    // This current $basePath logic is tailored to how my previous admin examples were structured.
    // The client header paths like `../public/css/styles.css` and `/js/scripts.js` are a bit mixed.
    // Let's standardize. If 'includes' is at root level of PHP includes:
    // /project_root/includes/header.php
    // /project_root/admin/dashboard.php
    // /project_root/public/index.php
    // /project_root/account/login.php

    if (strpos($_SERVER['PHP_SELF'], '/public/') !== false || strpos($_SERVER['PHP_SELF'], '/account/') !== false || strpos($_SERVER['PHP_SELF'], '/packages/') !== false || strpos($_SERVER['PHP_SELF'], '/feedback/') !== false) {
        $basePath = '../'; // If files are in /public, /account etc., and includes is one level up
    } else {
        $basePath = './'; // If files are at the same level as 'includes' or web root
                          // This case might not occur with your current client structure.
    }
     // Override for /public/index.php if it's directly in public and includes is one level above it.
    if (basename(dirname($_SERVER['PHP_SELF'])) === 'public' && basename($_SERVER['PHP_SELF']) === 'index.php') {
         // This is tricky. The client header has `../public/css/styles.css`
         // which means if header.php is in /includes/
         // then a file like /account/login.php would correctly find `../public/css/styles.css`
         // For /public/index.php, it would need `css/styles.css` or `./css/styles.css`
         //
         // Let's assume the goal is that $basePath should always point to the web root or project root
         // where public/ and admin/ folders reside.

        // A simpler $basePath strategy if this header.php is always in /includes/
        // and /includes/ is at the project root.
        $pathParts = explode('/', dirname($_SERVER['PHP_SELF']));
        $depth = count($pathParts) - 1; // Assuming project root is one level above 'includes'
                                       // or if 'includes' is directly under webroot, depth for files in subdirs.
        // This is getting too complex without knowing the exact true root.
        // The original $basePath logic for admin was simpler: `../` when in `admin/`.
        // For client pages, if header.php is in `includes/`, and pages are in `public/` or `account/`, `../` also works to get to the project root.
        // The client path `/js/scripts.js` suggests a web root reference.
        // `../public/css/styles.css` suggests relative path from a subdirectory.

        // Let's stick to the logic that $basePath helps get to the *project root*
        // where public/ and admin/ directories are.
        // If header.php is in /includes/, and pages calling it are in /admin/ or /public/ or /account/,
        // then '../' is generally correct to reach the level containing /public/ and /admin/.
        if (strpos(dirname($_SERVER['PHP_SELF']), '/admin') === 0 ||
            strpos(dirname($_SERVER['PHP_SELF']), '/public') === 0 ||
            strpos(dirname($_SERVER['PHP_SELF']), '/account') === 0 ||
            strpos(dirname($_SERVER['PHP_SELF']), '/packages') === 0 ||
            strpos(dirname($_SERVER['PHP_SELF']), '/feedback') === 0
            ) {
            // If the script is in a direct subfolder of the webroot
            $basePath = '../';
        } else {
            // If the script is at the webroot itself (e.g. /index.php calling /includes/header.php)
            // OR if it's deeper (e.g. /foo/bar/script.php calling /includes/header.php)
            // This needs to be more robust or standardized.
            // For now, assume pages are in first-level subdirectories like /public, /admin, /account.
            // The initial $isAdminPage check already handles this for admin.
            if (!$isAdminPage) $basePath = '../'; // Default for client pages in subdirs
            if (basename(dirname($_SERVER['PHP_SELF'])) == basename(dirname(dirname(__FILE__)))) {
                // If the calling script is in a directory at the same level as 'includes' parent (e.g. /public/)
                 $basePath = '../';
            }
             // If the calling script is directly in the project root and includes is a subfolder
            if (dirname($_SERVER['SCRIPT_NAME']) === '/') { // Script is at web root
                $basePath = './'; // or just empty
            }


        }
        // Simpler: if header.php is IN `includes/`
        // and `includes/` is at the project root.
        // Then for any file in `admin/` or `public/` or `account/`, `../` gets to project root.
        // The original check `($isAdminPage ? '../' : '')` assumed `public/index.php` was at project root.
        // Let's use a common strategy:
        if ($isAdminPage) {
            $basePath = '../'; // From /admin/file.php to project_root/
        } else {
            // For client pages: /public/file.php, /account/file.php, etc.
            // Their paths to CSS were `../public/css/...` meaning from /account/ to /public/
            // This implies `../` correctly gets to the project root from these locations.
            // Exception: if a public file is at the very root (e.g. /index.php) and calls `includes/header.php`,
            // then $basePath should be `./` or `''`.
            $script_path = $_SERVER['SCRIPT_NAME'];
            if (substr_count(trim($script_path, '/'), '/') == 0 && basename($script_path) == 'index.php') { // e.g. /index.php
                $basePath = './';
            } else { // e.g. /public/index.php or /account/login.php
                $basePath = '../';
            }
        }


}
}

// --- Page Title (from my admin header) ---
$page_title = isset($page_title) ? htmlspecialchars($page_title) : ($isAdminPage ? 'Admin Dashboard' : 'Travel Agency');

// --- Admin Specific Variables (from my admin header) ---
$current_admin_page = ($isAdminPage && isset($current_admin_page)) ? $current_admin_page : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Travel Agency System</title>

    <!-- CSRF token (from your client header) - Good for all pages -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

    <!-- Google Fonts - Poppins (common for both, from my admin header) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Linking -->
    <?php if ($isAdminPage): ?>
        <!-- Admin pages link to admin_styles.css. You might also want a base public style. -->
        <link rel="stylesheet" href="<?php echo $basePath; ?>public/css/styles.css"> <!-- Optional: common base styles -->
        <link rel="stylesheet" href="<?php echo $basePath; ?>admin/css/admin_styles.css">
    <?php else: // Public page styles (using client header's path logic adjusted with $basePath) ?>
        <link rel="stylesheet" href="<?php echo $basePath; ?>public/css/styles.css">
    <?php endif; ?>

    <!-- Icon Library Placeholder -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->

    <!-- JavaScript Linking (Client header used defer, good practice) -->
    <?php if (!$isAdminPage): // Only include public scripts.js for public pages by default ?>
        <script src="<?php echo $basePath; ?>public/js/scripts.js" defer></script>
        <?php // Note: Your client header had /js/scripts.js which assumes js is at webroot.
              // Using $basePath makes it relative to the project structure.
              // If public/js/ is correct, use $basePath . 'public/js/scripts.js'
        ?>
    <?php endif; ?>
</head>
<body class="<?php echo $isAdminPage ? 'admin-body-bg' : 'public-body-bg'; // Body class from my admin header ?>">

    <?php if ($isAdminPage): ?>
        <!-- Admin Interface Structure (from my admin header) -->
        <div class="admin-layout-container">
            <?php // Admin sidebar will be included by individual admin pages (e.g., dashboard.php) ?>
            <!-- Main admin content wrapper starts here, will be closed in footer.php -->
            <div class="admin-main-wrapper">
                <?php // The admin page (e.g. dashboard.php) will then include admin_sidebar.php and its own <main> content here ?>
    <?php else: ?>
        <!-- Public Interface Structure (from your client header) -->
        <header class="navbar"> <!-- Class from your client header -->
            <div class="logo"><a href="<?php echo $basePath; ?>public/index.php">Travel Agency</a></div> <!-- Path adjusted -->
            <nav>
                <ul class="nav-links">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <li><a href="<?php echo $basePath; ?>public/index.php">Home</a></li>
                        <li><a href="<?php echo $basePath; ?>packages/list.php">Packages</a></li>
                        <li><a href="<?php echo $basePath; ?>account/reservations.php">My Reservations</a></li>
                        <li><a href="<?php echo $basePath; ?>public/index.php#contact">Contact</a></li>
                        <li><a href="<?php echo $basePath; ?>account/logout.php">Logout</a></li>
                    <?php else: // User not logged in ?>
                        <li><a href="<?php echo $basePath; ?>public/index.php">Home</a></li>
                        <li><a href="<?php echo $basePath; ?>account/login.php">Login</a></li>
                        <li><a href="<?php echo $basePath; ?>account/register.php">Register</a></li>
                        <li><a href="<?php echo $basePath; ?>public/index.php#contact">Contact</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <!-- Optional: Add a hamburger menu toggle for mobile if your public CSS supports it -->
        </header>
        <main> <!-- Public pages will then add their content directly inside this main -->
            <!-- Note: Your client header had <div class="container"> inside <main> in the admin's branch.
                 For public pages, if a container is always needed, it should be here or in each public page.
                 I've removed it from here to match your client header's structure where <main> is direct.
            -->
    <?php endif; ?>
