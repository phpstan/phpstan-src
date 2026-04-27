<?php

namespace Bug14534;

function test(\SplFileObject $spl): bool
{
	if ($spl->key() === 1) {
		return $spl->key() === 1;
	}

	return false;
}

function test2(\SplTempFileObject $spl): bool
{
	if ($spl->key() === 1) {
		return $spl->key() === 1;
	}

	return false;
}
