<?php

$routes = array(
	'/apa/[action]/[what]' => 'homes',
	'/aaa' => 'as',
	'/post//[year|nnnn]/[month|nn]/[day|nn]/[slug]' => 'post::view',
	// Default route
	// the two slashes denote optional parameters
	'/[controller]//[action]',
	'/' => 'post::view'
);