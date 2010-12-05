<?php

// Map file
//
// Add mappings in the format:
// self::map($path[, $controller, $action, $params]);
//
// Note: $path must start with a slash, and must NOT have a trailing slash.
//
// examples
// -> self::map('/%controller%/%action%/%id%')
//    then /alpha/beta/1
//    will resolve to controller 'alpha', action 'beta', and $params[id] will be 1.
//    (this is actually the default map)

$this->map('/%controller%/%action%/%id%');
$this->map('/%controller%/%action%');
$this->map('/%controller%');
$this->map('/', array('controller' => 'home'));