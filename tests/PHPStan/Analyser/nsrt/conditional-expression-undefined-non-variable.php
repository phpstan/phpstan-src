<?php // lint >= 8.1

declare(strict_types = 1);

namespace ConditionalExpressionUndefinedNonVariable;

use function PHPStan\Testing\assertType;

class Rule { public function __construct(public readonly int $level) {} }
class Dice
{
	public bool $lastResult = false;
	public function useDice(int $level): self { $this->lastResult = rand(0, 10) < $level; return $this; }
}

class Game
{
	private ?Rule $rule = null;

	public function __construct(private readonly bool $haveRule) {}

	public function play(?Dice $dice): void
	{
		$this->setupRule();

		if ($this->rule && $dice) {
			$if = $dice->useDice($this->rule->level);
		}

		if ($this->rule && !$dice) {
			assertType(Rule::class, $this->rule);
			$if = $this->rule->level;
		}
	}

	private function setupRule(): void
	{
		if ($this->haveRule) {
			$this->rule = new Rule(5);
		}
	}
}

class Variants
{

	private ?Rule $rule = null;

	private ?Variants $inner = null;

	private static ?Rule $staticRule = null;

	public function strictComparison(?Dice $dice): void
	{
		if ($this->rule !== null && $dice !== null) {
		}

		if ($this->rule !== null && $dice === null) {
			assertType(Rule::class, $this->rule);
		}
	}

	public function narrowedEarlier(?Dice $dice): void
	{
		if ($this->rule !== null && $dice !== null) {
		}

		if ($this->rule === null) {
			return;
		}

		if ($dice === null) {
			assertType(Rule::class, $this->rule);
		}
	}

	public function nestedProperty(?Dice $dice): void
	{
		if ($this->inner !== null && $this->inner->rule !== null && $dice !== null) {
		}

		if ($this->inner !== null && $this->inner->rule !== null && $dice === null) {
			assertType(Rule::class, $this->inner->rule);
		}
	}

	public function staticProperty(?Dice $dice): void
	{
		if (self::$staticRule !== null && $dice !== null) {
		}

		if (self::$staticRule !== null && $dice === null) {
			assertType(Rule::class, self::$staticRule);
		}
	}

	/**
	 * @param array{k: Rule|null} $a
	 */
	public function arrayOffset(array $a, ?Dice $dice): void
	{
		if ($a['k'] !== null && $dice !== null) {
		}

		if ($a['k'] !== null && $dice === null) {
			assertType(Rule::class, $a['k']);
		}
	}

}
