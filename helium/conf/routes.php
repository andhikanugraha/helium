<?php

$routes = array(
	'/apa/[action]/[what]' => 'homes',
	'/aaa' => 'as',
	'/post/[year|nnnn]/[month]/[day]' => 'post::view',
	'/post/[year|nnnn]/[month]' => 'post::view',
	'/post/[year|nnnn]' => 'post::view',
	'/post' => 'home::index',
	'/[controller]/[action]' => '',
	'/[controller]' => ''
);