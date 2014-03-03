<?php

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Use APC for autoloading to improve performance.
// Change 'sf2' to a unique prefix in order to prevent cache key conflicts
// with other applications also using APC.
/*
$apcLoader = new ApcClassLoader('sf2', $loader);
$loader->unregister();
$apcLoader->register(true);
*/

require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';


// default to the dev environment
if (!getenv('ENVIRONMENT')) {
    putenv('ENVIRONMENT=dev');
}

// default DEBUG to true
if (!getenv('DEBUG')) {
    putenv('DEBUG=true');
}

// instantiate the kernel with the correct environmental variables
$kernel = new AppKernel(strtolower(getenv('ENVIRONMENT')), (bool) getenv('DEBUG'));

if (getenv('DEBUG')) {
    umask(0000); // eases local development efforts
}

$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
