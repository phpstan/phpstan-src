<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function substr;

final class RegexExpressionHelper
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	/**
	 * Ignores preg_quote() calls in the concatenation as these are not relevant for array-shape matching.
	 *
	 * This assumption only works for the ArrayShapeMatcher therefore it is not implemented for the common case in Scope.
	 *
	 * see https://github.com/phpstan/phpstan-src/pull/3233#discussion_r1676938085
	 */
	public function resolvePatternConcat(Concat $concat, Scope $scope): Type
	{
		$resolver = new class($scope) {

			public function __construct(private Scope $scope)
			{
			}

			public function resolve(Expr $expr): Type
			{
				if (
					$expr instanceof Expr\FuncCall
					&& $expr->name instanceof Name
					&& $expr->name->toLowerString() === 'preg_quote'
				) {
					return new ConstantStringType('');
				}

				if ($expr instanceof Concat) {
					$left = $this->resolve($expr->left);
					$right = $this->resolve($expr->right);

					$strings = [];
					foreach ($left->toString()->getConstantStrings() as $leftString) {
						foreach ($right->toString()->getConstantStrings() as $rightString) {
							$strings[] = new ConstantStringType($leftString->getValue() . $rightString->getValue());
						}
					}

					return TypeCombinator::union(...$strings);
				}

				return $this->scope->getType($expr);
			}

		};

		return $this->initializerExprTypeResolver->getConcatType($concat->left, $concat->right, static fn (Expr $expr): Type => $resolver->resolve($expr));
	}

	/**
	 * Get delimiters from non-constant patterns, if possible.
	 *
	 * @return string[]
	 */
	public function getPatternDelimiters(Concat $concat, Scope $scope): array
	{
		if ($concat->left instanceof Concat) {
			return $this->getPatternDelimiters($concat->left, $scope);
		}

		$left = $scope->getType($concat->left);

		$delimiters = [];
		foreach ($left->getConstantStrings() as $leftString) {
			$delimiter = $this->getDelimiterFromString($leftString);
			if ($delimiter === null) {
				continue;
			}

			$delimiters[] = $delimiter;
		}
		return $delimiters;
	}

	private function getDelimiterFromString(ConstantStringType $string): ?string
	{
		if ($string->getValue() === '') {
			return null;
		}

		return substr($string->getValue(), 0, 1);
	}

}
