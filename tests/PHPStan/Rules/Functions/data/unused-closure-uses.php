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
