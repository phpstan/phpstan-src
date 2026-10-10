<?php // lint >= 8.0

namespace Bug15448;

class Error
{
}

/**
 * @template T of Error|ErrorIterator
 * @implements \Iterator<int, T>
 */
abstract class ErrorIterator implements \Iterator
{

	/** @return T */
	abstract public function current(): Error|self;

}

function errors(ErrorIterator $errors): void
{
	/** @var Error $error */
	foreach ($errors as $error) {
	}
}
