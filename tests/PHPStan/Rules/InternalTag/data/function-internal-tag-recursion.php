<?php

namespace FunctionInternalTagRecursion {

	/** @internal */
	function doInternal(int $i): int
	{
		if ($i > 0) {
			return doInternal($i - 1);
		}

		return $i;
	}

}

namespace {

	/** @internal */
	function doInternalRecursionWithoutNamespace(int $i): int
	{
		if ($i > 0) {
			return doInternalRecursionWithoutNamespace($i - 1);
		}

		$closure = function (): int {
			return doInternalRecursionWithoutNamespace(0);
		};

		return $closure();
	}

	function doNotInternalRecursionWithoutNamespace(int $i): int
	{
		if ($i > 0) {
			return doInternalRecursionWithoutNamespace($i - 1);
		}

		return $i;
	}

}
