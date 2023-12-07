<?php

namespace Discussion10252;

function sayIt(): void
{
	require 'who-to-love.php';

	/** @var string $name */
	echo 'I love you, ' . $name .'!';
}
