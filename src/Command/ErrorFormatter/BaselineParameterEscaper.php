<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use function str_replace;

final class BaselineParameterEscaper
{

	/**
	 * Baseline entries are loaded as nette/di parameters, which expand `%name%` references.
	 * A leading `@` needs no escaping - nette/di deprecated `@@` and parameters never unescape it.
	 */
	public static function escape(string $value): string
	{
		return str_replace('%', '%%', $value);
	}

}
