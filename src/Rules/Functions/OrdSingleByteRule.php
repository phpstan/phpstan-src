<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use function count;
use function min;
use function ord;
use function strlen;

/**
 * @implements Rule<FuncCall>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.phpLanguageDeprecations%',
)]
final class OrdSingleByteRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getPhpVersion()->deprecatesOrdWithoutSingleByte()->no()) {
			return [];
		}

		if (!$node->name instanceof Node\Name || $node->isFirstClassCallable()) {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) !== 1 || $args[0]->unpack) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		if ($this->reflectionProvider->getFunction($node->name, $scope)->getName() !== 'ord') {
			return [];
		}

		$argType = $scope->getType($args[0]->value);
		if (!$argType->isString()->yes() && $scope->isDeclareStrictTypes()) {
			// a TypeError reported by the argument type check
			return [];
		}

		$stringType = $argType->toString();
		if ($stringType instanceof ErrorType) {
			return [];
		}

		if ($stringType->isNonEmptyString()->no()) {
			return [
				RuleErrorBuilder::message('Providing an empty string to ord() is deprecated in PHP 8.5.')
					->identifier('argument.ordEmptyString')
					->build(),
			];
		}

		$constantStrings = $stringType->getConstantStrings();
		if (count($constantStrings) === 0) {
			return [];
		}

		$minLength = null;
		foreach ($constantStrings as $constantString) {
			$length = strlen($constantString->getValue());
			if ($length === 1) {
				return [];
			}

			$minLength = $minLength === null ? $length : min($minLength, $length);
		}

		$errorBuilder = RuleErrorBuilder::message('Providing a string that is not one byte long to ord() is deprecated in PHP 8.5.')
			->tip('Use ord($str[0]) instead.')
			->identifier('argument.ordMultiByte');

		// ord() reads the first byte, which the fix passes explicitly
		if ($minLength > 1 && $argType->isString()->yes()) {
			$errorBuilder->fixNode($args[0]->value, static function (Node\Expr $expr): Node\Expr {
				if ($expr instanceof Node\Scalar\String_ && ord($expr->value[0]) >= 0x20 && ord($expr->value[0]) <= 0x7E) {
					return new Node\Scalar\String_($expr->value[0]);
				}

				return new Node\Expr\ArrayDimFetch($expr, new Node\Scalar\Int_(0));
			});
		}

		return [$errorBuilder->build()];
	}

}
