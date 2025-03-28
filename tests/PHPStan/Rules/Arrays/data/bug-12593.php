<?php

namespace Bug12593;

class HelloWorld
{
	/**
	 * @param list<int> $indexes
	 */
	protected function removeArguments(array $indexes): void
	{
		if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
			foreach ($indexes as $index) {
				if (isset($_SERVER['argv'][$index])) {
					unset($_SERVER['argv'][$index]);
				}
			}
		}
	}
}

class HelloWorld2
{
	/**
	 * @param list<int> $indexes
	 */
	protected function removeArguments(array $indexes): void
	{
		foreach ($indexes as $index) {
			if (isset($_SERVER['argv']) && is_array($_SERVER['argv']) && isset($_SERVER['argv'][$index])) {
				unset($_SERVER['argv'][$index]);
			}
		}
	}
}
