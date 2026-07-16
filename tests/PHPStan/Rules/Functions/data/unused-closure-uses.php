<?php

function () use (
	$used,
	$usedInClosureUse,
	$unused,
	$anotherUnused
) {
	echo $used;
	function ($anotherUnused) use ($usedInClosureUse) {
		echo $anotherUnused; // different scope
	};
};

$container = new stdClass();

function () use ($container) {
	require_once __DIR__ . '/foo.php';
};

function () use ($container) {
	include 'foo.php';
};

function () use ($container) {
	eval('echo $container;');
};

$aap = 684;

$a = function () use ($aap) {
	require __DIR__ . '/echo_the_value_of_aap.php';
};

$name = 'container';

function () use ($container, $name) {
	echo $$name;
};
