<?php declare(strict_types = 1);

namespace Bug7675;

use Closure;
use Throwable;

class Handler
{
}

class SpladeCore
{
	const HEADER_SPLADE = 'x-splade';
	public static function exceptionHandler(Handler $exceptionHandler): Closure
	{
		return Closure::bind(function (Throwable $e, $request) {
			if (!$request->header(SpladeCore::HEADER_SPLADE)) {
				return null;
			}

			return true;
		}, $exceptionHandler, get_class($exceptionHandler));
	}
}
