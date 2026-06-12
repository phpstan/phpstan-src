<?php

namespace SwitchConditionAlwaysFalseNative;

class Foo
{

	public function typeMismatch(int $i): void
	{
		switch ($i) {
			case 'foo':
				break;
		}
	}

	/**
	 * @param int<5, max> $i
	 */
	public function phpDocOnly(int $i): void
	{
		switch ($i) {
			case 1:
				break;
		}
	}

}
