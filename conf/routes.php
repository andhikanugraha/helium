<?php

// Helium framework
// route definitions

$routes = array(

	// Example 1: permalinks
	// '/cherrybananapie/[size]' => array('controller' => 'food', 'action' => 'view', 'item' => 'cherrybananapie'),
	// will load controller 'food', action 'view', and have the parameter 'item' = 'cherrybananapie'.

	// Example 2: archives link
	//            the 'n' denotes a single digit -- everything after the | acts as a filter
	// '/[year|nnnn]//[month|nn]/[day|nn]/[slug]' => array('controller' => 'posts', 'action' => 'archives')

	// Default route
	// the two slashes denote optional parameters
	'/[controller]//[action]/[id]'

);