<?php

// prepend.php shows the login form to anyone not logged in and stops there.
// This page handles that form's POST, so it asks prepend.php to let it through.
const LOGIN_PAGE = true;

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

if ($is_logged_in->isLoggedIn()) {
    // Already in (remember-me cookie, or the POST below on a previous request).
    header("Location: /");
    exit;
}

// This page is the ONLY place credentials are checked. prepend.php already
// rejected any POST without a CSRF token before we got here.
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $mla_request->post['email'] ?? '';
    $password = $mla_request->post['pass'] ?? '';

    $logged_in = false;
    if (is_string($email) && trim($email) !== '' && is_string($password) && $password !== '') {
        $logged_in = $is_logged_in->attemptPasswordLogin(trim($email), $password);
    }

    if ($logged_in) {
        header("Location: /");
        exit;
    }
    // One message for every bad credential: saying which half was wrong
    // tells a guesser which logins exist.
    $login_error = "Username or password incorrect.";
}

$page = new \Template(config: $config);
$page->setTemplate("login/index.tpl.php");
$page->set(name: "login_error", value: $login_error);
$page->echoToScreen();
