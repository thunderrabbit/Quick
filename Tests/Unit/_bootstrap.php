<?php

declare(strict_types=1);

// Unit test bootstrap - load project classes without prepend.php
// (prepend.php starts a session, connects to the DB and demands a login;
// unit tests must not)

$projectRoot = dirname(__DIR__, 2);

// Same lookup as Mlaphp\Autoloader, but silent on a miss: Mlaphp's throws,
// which would break class_exists() probes below.
spl_autoload_register(function ($class) use ($projectRoot) {
    $class = ltrim($class, '\\');
    $path = str_replace(['\\', '_'], DIRECTORY_SEPARATOR, $class);
    $file = $projectRoot . '/classes/' . $path . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// The real Config.php lives only on DreamHost (not in the repo); on Lemur 13
// and in the runner image only the sample exists. Prefer a real Config,
// else load the sample, which declares the same \Config class.
if (!class_exists(\Config::class)) {
    require_once $projectRoot . '/classes/ConfigSample.php';
}
