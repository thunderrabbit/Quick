<?php
// Report everything, but to DreamHost's PHP error log, not the page: errors
// shown on screen leak paths, SQL and config to whoever triggers them.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

const SEMVER = "1.0.0";

// The session cookie carries the CSRF token and the editor's draft, so it
// gets the same flags as the remember-me cookie. These only take effect if
// set before session_start(). Strict mode refuses a session id the server
// never issued, so an attacker cannot plant one (session fixation).
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/classes/Mlaphp/Autoloader.php';
// create autoloader instance and register the method with SPL
$autoloader = new \Mlaphp\Autoloader();
spl_autoload_register(array($autoloader, 'load'));

$mla_request = new \Mlaphp\Request();
$csrfProtect = new \Security\CSRFProtectaroo($mla_request);

/**
 * The hidden CSRF input for any POST form. Templates write <?= csrf_field() ?>
 * inside the <form>; no page-level set() is needed.
 */
function csrf_field(): string
{
    global $csrfProtect;
    return $csrfProtect->field();
}

/**
 * The bare token, for fetch() callers that send it as the X-CSRF-Token header
 * (see the pull button in templates/poster/index.tpl.php). Always
 * json_encode() it into JS.
 */
function csrf_token(): string
{
    global $csrfProtect;
    return $csrfProtect->getToken();
}

// Every state-changing request must carry the session's token. Checked here,
// before any page code or DB work, so a handler cannot forget it. A cross-site
// POST fails because the attacking page cannot read our session's token.
// That matters more than SameSite here: badmin and quick are both on
// robnugen.com, so to the browser they are the same site.
if (!$csrfProtect->validateRequest()) {
    http_response_code(403);
    echo "<h1>Request rejected</h1>"
        . "<p>The form's security token was missing or has expired. "
        . "Go back, reload the page, and try again.</p>";
    exit;
}

function print_rob($object, $exit = true)
{
    echo ("<pre>");
    if (is_object($object) && method_exists($object, "toArray")) {
        echo "ResultSet => " . print_r($object->toArray(), true);
    } else {
        print_r($object);
    }
    echo ("</pre>");
    if ($exit) {
        exit;
    }
}

try {
    $config = new \Config();
} catch (\Exception $e) {
    echo "Be sure to rebase Required branch onto master";
    echo "<br>ssh bfr";
    echo "<br>cd ~/quick.robnugen.com";
    echo "<br>git stash";
    echo "<br>git checkout Required";
    echo "<br>git rebase master";
    echo "<br>git stash pop";
    exit;
}

$mla_database = \Base::getDB($config);

$is_logged_in = new \Auth\IsLoggedIn($mla_database);
// Read-only: who does the remember-me cookie say this is? Credentials are
// checked by /login/index.php alone, never here.
$is_logged_in->resumeFromCookie($mla_request);

// /login/ defines LOGIN_PAGE: it is where the form below posts to.
if (!$is_logged_in->isLoggedIn() && !defined('LOGIN_PAGE')) {
    $page = new \Template(config: $config);
    $page->setTemplate("login/index.tpl.php");
    $page->echoToScreen();
    exit;
}
