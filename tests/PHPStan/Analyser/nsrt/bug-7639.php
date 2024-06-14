<?php declare(strict_types = 1);

namespace Bug7639;

use function PHPStan\Testing\assertType;

abstract class TestCase
{
	/**
	 * @return static|null
	 */
	public static function getTestFromBacktrace()
	{
		foreach (debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT) as $frame) {
			if (($frame['object'] ?? null) instanceof static) {
				assertType('static(Bug7639\\TestCase)', $frame['object']);
				return $frame['object'];
			}
		}

		return null;
	}

	/**
	 * @return static|null
	 */
	public static function getTestFromBacktraceWithoutNullCoalesce()
	{
		foreach (debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT) as $frame) {
			if (isset($frame['object']) && $frame['object'] instanceof static) {
				assertType('static(Bug7639\\TestCase)', $frame['object']);
				return $frame['object'];
			}
		}

		return null;
	}
}
