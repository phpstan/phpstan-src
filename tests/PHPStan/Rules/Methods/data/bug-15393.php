<?php

namespace Bug15393;

function doFoo(\Redis $redis): void
{
	$redis->set('key', 'value');
	$redis->set('key', 'value', 3600);
	$redis->set('key', 'value', ['nx', 'ex' => 3600]);
	$redis->set('key', 'value', null);
}
