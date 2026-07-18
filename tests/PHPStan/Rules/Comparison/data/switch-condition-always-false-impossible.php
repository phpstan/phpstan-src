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
	 * A `never` operand leaves the loose comparison undecided (never has no
	 * value to compare), so the trailing case is intentionally not reported as
	 * always-false: it sits on already-unreachable code and flagging it would
	 * just pile onto that. No error is expected here.
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
