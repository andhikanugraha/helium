<?php

$routes = array(
	'/post/[year|nnnn]//[month|nn]/[day|nn]/[slug]' => 'post::view',
	// Default route
	// the two slashes denote optional parameters
	'/[controller]//[action]',
	'/' => 'post::view'
);