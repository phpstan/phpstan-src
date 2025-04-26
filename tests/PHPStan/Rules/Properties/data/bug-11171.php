<?php

namespace Bug11171;

class TypeExpression
{
	public string $value;

	/**
	 * @var list<array{start_index: int, expression: self}>
	 */
	public array $innerTypeExpressions = [];

	/**
	 * @param \Closure(self): void $callback
	 */
	public function walkTypes(\Closure $callback): void
	{
		$startIndexOffset = 0;

		foreach ($this->innerTypeExpressions as $k => ['start_index' => $startIndexOrig,
				 'expression' => $inner,]) {
			$this->innerTypeExpressions[$k]['start_index'] += $startIndexOffset;

			$innerLengthOrig = \strlen($inner->value);

			$inner->walkTypes($callback);

			$this->value = substr_replace(
				$this->value,
				$inner->value,
				$startIndexOrig + $startIndexOffset,
				$innerLengthOrig
			);

			$startIndexOffset += \strlen($inner->value) - $innerLengthOrig;
		}

		$callback($this);
	}
}
