<?php

namespace Bug5095;

final class UnaryOperator
{
	const PLUS = '+';
	const MINUS = '-';
	const DIVIDE = '/';
	const NOT = 'not';
}

class Parser
{
	/**
	 * Returns the unary operator corresponding to $character, or `null` if
	 * the character is not a unary operator.
	 *
	 * @phpstan-return UnaryOperator::*|null
	 */
	private function unaryOperatorFor(string $character): ?string
	{
		switch ($character) {
			case '+':
				return UnaryOperator::PLUS;

			case '-':
				return UnaryOperator::MINUS;

			case '/':
				return UnaryOperator::DIVIDE;

			default:
				return null;
		}
	}
}
