<?php
// includes/functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is logged in and has the required role.
 * If not, redirects to the login page.
 *
 * @param array|null $required_roles An array of roles that are allowed to access the page. If null, any logged-in user is allowed.
 */
function check_session(array $required_roles = null) {
    // Determine the base path of the project.
    // This assumes the project root is the parent directory of 'includes'.
    $base_path = dirname(__DIR__);

    // Construct the path to the login page.
    // We use a relative path from the domain root.
    $login_page = '/index.php';
    // This logic might need adjustment depending on how the server is set up.
    // For a typical XAMPP setup, if the project is in htdocs/global_store,
    // the URL would be /global_store/index.php.
    // A simple way to handle this is to assume a root-relative path.
    $project_folder = basename($base_path);
    $login_url = "/" . $project_folder . "/index.php";


    if (!isset($_SESSION['user_id'])) {
        header("Location: " . $login_url);
        exit();
    }

    if ($required_roles !== null) {
        if (!in_array($_SESSION['role'], $required_roles)) {
            // If the user does not have the required role, redirect them.
            // For simplicity, we redirect to login. A 403 page would be better.
            header("Location: " . $login_url . "?error=unauthorized");
            exit();
        }
    }
}

/**
 * Formats a number as a price in FCFA.
 *
 * @param float $number The number to format.
 * @return string The formatted price.
 */
function format_price(float $number): string {
    return number_format($number, 0, ',', ' ') . ' FCFA';
}

?>
