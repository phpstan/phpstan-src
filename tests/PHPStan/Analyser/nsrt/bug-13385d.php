<?php declare(strict_types = 1);

namespace Bug13385d;

use function PHPStan\Testing\assertType;

interface Operator {
	public function priority(): int;
	public function calculate(int $a, int $b): int;
}

class HelloWorld
{
	/**
	 * @param list<Operator|int> $children
	 */
	public function calculate(array $children): int {
		$operands  = [];
		$operators = [];

		for ($i = 0; $i < count($children); $i++) {
			if ($children[$i] instanceof Operator) {
				while ($operators !== [] && end($operators)->priority() >= $children[$i]->priority()) {
					$op    = array_pop($operators);
					$left  = array_pop($operands) ?? 0;
					$right = array_pop($operands) ?? 0;

					assert(is_int($left));
					assert(is_int($right));

					$value = $op->calculate($left, $right);

					assertType(Operator::class, $op);
					assertType('int', $left);
					assertType('int', $right);
					assertType('int', $value);

					$operands[] = $value;

					assertType('non-empty-list<int>', $operands);
				}

				$operators[] = $children[$i];
			} else {
				$operands[] = $children[$i];
			}
		}

		return count($operands) === 1 ? reset($operands) : 0;
	}
}
