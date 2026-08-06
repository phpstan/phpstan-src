<?php declare(strict_types = 1);

namespace Bug15046;

class Example {

	protected int $answer;

	public function echo_answer(?int $my_answer = null): void {
		// If the following conditional property initialisation is removed, the issue disappears.
		if ($my_answer !== null)
			$this->answer = $my_answer;
		// At this point, $this->answer may still be uninitialised, so that `??` is not unnecessary
		echo "The answer is ", self::format_answer($this->answer ?? null), ".\n";
	}

	private static function format_answer(?int $the_answer): string {
		return (string) ($the_answer ?? 'unknown');
	}

}

class StaticProperty
{

	protected static int $answer;

	public static function echoAnswer(?int $myAnswer = null): void
	{
		if ($myAnswer !== null) {
			self::$answer = $myAnswer;
		}

		echo (self::$answer ?? null);
	}

}

class BooleanCondition
{

	protected int $answer;

	public function echoAnswer(bool $cond): void
	{
		if ($cond) {
			$this->answer = 1;
		}

		echo ($this->answer ?? null);
	}

}

class CoalesceAssign
{

	protected int $answer;

	public function echoAnswer(?int $myAnswer = null): void
	{
		if ($myAnswer !== null) {
			$this->answer = $myAnswer;
		}

		$this->answer ??= null;
	}

}

class Ternary
{

	protected int $answer;

	public function echoAnswer(?int $myAnswer = null): void
	{
		$myAnswer !== null ? ($this->answer = $myAnswer) : null;

		echo ($this->answer ?? null);
	}

}

class SwitchStatement
{

	protected int $answer;

	public function echoAnswer(int $myAnswer): void
	{
		switch ($myAnswer) {
			case 1:
				$this->answer = 1;
				break;
		}

		echo ($this->answer ?? null);
	}

}

class Inner
{

	public int $deep;

}

class NestedChain
{

	protected Inner $inner;

	public function echoAnswer(?int $myAnswer = null): void
	{
		$this->inner = new Inner();
		if ($myAnswer !== null) {
			$this->inner->deep = $myAnswer;
		}

		echo ($this->inner->deep ?? null);
	}

}

class BothBranches
{

	protected int $answer;

	public function echoAnswer(bool $cond): void
	{
		if ($cond) {
			$this->answer = 1;
		} else {
			$this->answer = 2;
		}

		echo ($this->answer ?? null);
	}

}

class ConditionMetAgain
{

	protected int $answer;

	public function echoAnswer(?int $myAnswer = null): void
	{
		if ($myAnswer !== null) {
			$this->answer = $myAnswer;
		}

		if ($myAnswer !== null) {
			echo ($this->answer ?? null);
		}
	}

}
