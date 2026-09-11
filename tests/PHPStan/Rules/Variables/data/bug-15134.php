<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug15134;

use LogicException;

interface Node {}

class A implements Node {}

abstract class Parser {

	protected function parseExpressionChild(bool $value): ?Node {
		return $this->parseNumber($value)
			?? $this->parseSpace($value);
	}

	abstract protected function parseNumber(bool $value): ?A;

	protected function parseSpace(bool $value): null {
		if ($value === false) {
			throw new LogicException('The string is not a mathematical expression.');
		}

		return null;
	}

}
