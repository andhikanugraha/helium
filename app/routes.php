<?php

// Routes file
//
// Routes are in the form of
// $route($path[, $params, $formats]);

$route('/%controller%/%action%/%id%');
$route('/%controller%/%action%');
$route('/%controller%');
$route('/', array('controller' => 'home'));