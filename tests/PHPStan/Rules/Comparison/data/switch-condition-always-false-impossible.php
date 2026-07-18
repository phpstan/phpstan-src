<?php

namespace SwitchConditionAlwaysFalseImpossible;

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
	 * @param 1|2|3 $i
	 */
	public function literalIntUnion(int $i): void
	{
		switch ($i) {
			case 4:
				break;
			case 1:
				break;
		}
	}

	/**
	 * @param 'a'|'b' $s
	 */
	public function exhaustedStringUnion(string $s): void
	{
		switch ($s) {
			case 'a':
				break;
			case 'b':
				break;
			case 'c':
				break;
		}
	}

	/**
	 * @param int<5, max> $i
	 */
	public function integerRange(int $i): void
	{
		switch ($i) {
			case 1:
				break;
			case 10:
				break;
		}
	}

	/**
	 * Once the earlier cases have exhausted the subject, it narrows to `never`.
	 * A `never` operand makes the loose comparison always-false (consistently
	 * with strict comparison), so the trailing case is reported as always-false
	 * just like the equivalent `match` arm / `if`-`elseif` chain on an exhausted
	 * subject.
	 *
	 * @param 'a'|'b' $s
	 */
	public function nonConstantCaseOnExhaustedSubject(string $s, string $other): void
	{
		switch ($s) {
			case 'a':
			case 'b':
				break;
			case $other:
				break;
		}
	}

}
