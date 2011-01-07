<?php

// Routes file
//
// Routes are in the form of
// $route($path[, $controller, $action, $params]);

$route('/%controller%/%action%/%id%');
$route('/%controller%/%action%');
$route('/%controller%');
$route('/', array('controller' => 'home'));