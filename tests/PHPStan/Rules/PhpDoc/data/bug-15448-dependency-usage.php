<?php

namespace Bug15448Dependency;

function create(): ErrorIterator
{
	return new ErrorIterator([]);
}

function errors(ErrorIterator $errors): void
{
	/** @var Error $error */
	foreach ($errors as $error) {
	}
}
