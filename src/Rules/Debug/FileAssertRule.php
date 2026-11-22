<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\VerbosityLevel;
use function count;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[AutowiredService]
final class FileAssertRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private TypeStringResolver $typeStringResolver,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		if (count($node->getArgs()) !== 2) {
			return [];
		}

		$name = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
		$args = $node->getArgs();
		if ($name === 'PHPStan\\Testing\\assertType') {
			$typeArg = ArgumentsNormalizer::getArgByPositionOrName($args, 0, 'type');
			$valueArg = ArgumentsNormalizer::getArgByPositionOrName($args, 1, 'value');
			if ($typeArg === null || $valueArg === null) {
				return [];
			}
			return $this->processAssertType([$typeArg, $valueArg], $scope);
		}

		if ($name === 'PHPStan\\Testing\\assertNativeType') {
			$typeArg = ArgumentsNormalizer::getArgByPositionOrName($args, 0, 'type');
			$valueArg = ArgumentsNormalizer::getArgByPositionOrName($args, 1, 'value');
			if ($typeArg === null || $valueArg === null) {
				return [];
			}
			return $this->processAssertNativeType([$typeArg, $valueArg], $scope);
		}

		if ($name === 'PHPStan\\Testing\\assertSuperType') {
			$typeArg = ArgumentsNormalizer::getArgByPositionOrName($args, 0, 'superType');
			$valueArg = ArgumentsNormalizer::getArgByPositionOrName($args, 1, 'value');
			if ($typeArg === null || $valueArg === null) {
				return [];
			}
			return $this->processAssertSuperType([$typeArg, $valueArg], $scope);
		}

		if ($name === 'PHPStan\\Testing\\assertVariableCertainty') {
			$certaintyArg = ArgumentsNormalizer::getArgByPositionOrName($args, 0, 'certainty');
			$variableArg = ArgumentsNormalizer::getArgByPositionOrName($args, 1, 'variable');
			if ($certaintyArg === null || $variableArg === null) {
				return [];
			}
			return $this->processAssertVariableCertainty([$certaintyArg, $variableArg], $scope);
		}

		return [];
	}

	/**
	 * @param array{Node\Arg, Node\Arg} $args
	 * @return list<IdentifierRuleError>
	 */
	private function processAssertType(array $args, Scope $scope): array
	{
		$expectedTypeStrings = $scope->getType($args[0]->value)->getConstantStrings();
		if (count($expectedTypeStrings) !== 1) {
			return [
				RuleErrorBuilder::message('Expected type must be a literal string.')
					->nonIgnorable()
					->identifier('phpstan.unknownExpectation')
					->build(),
			];
		}

		$expressionType = $scope->getType($args[1]->value)->describe(VerbosityLevel::precise());
		if ($expectedTypeStrings[0]->getValue() === $expressionType) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('Expected type %s, actual: %s', $expectedTypeStrings[0]->getValue(), $expressionType))
				->nonIgnorable()
				->identifier('phpstan.type')
				->build(),
		];
	}

	/**
	 * @param array{Node\Arg, Node\Arg} $args
	 * @return list<IdentifierRuleError>
	 */
	private function processAssertNativeType(array $args, Scope $scope): array
	{
		$expectedTypeStrings = $scope->getNativeType($args[0]->value)->getConstantStrings();
		if (count($expectedTypeStrings) !== 1) {
			return [
				RuleErrorBuilder::message('Expected native type must be a literal string.')
					->nonIgnorable()
					->identifier('phpstan.unknownExpectation')
					->build(),
			];
		}

		$expressionType = $scope->getNativeType($args[1]->value)->describe(VerbosityLevel::precise());
		if ($expectedTypeStrings[0]->getValue() === $expressionType) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('Expected native type %s, actual: %s', $expectedTypeStrings[0]->getValue(), $expressionType))
				->nonIgnorable()
				->identifier('phpstan.nativeType')
				->build(),
		];
	}

	/**
	 * @param array{Node\Arg, Node\Arg} $args
	 * @return list<IdentifierRuleError>
	 */
	private function processAssertSuperType(array $args, Scope $scope): array
	{
		$expectedTypeStrings = $scope->getType($args[0]->value)->getConstantStrings();
		if (count($expectedTypeStrings) !== 1) {
			return [
				RuleErrorBuilder::message('Expected super type must be a literal string.')
					->nonIgnorable()
					->identifier('phpstan.unknownExpectation')
					->build(),
			];
		}

		$expressionType = $scope->getType($args[1]->value);
		$expectedType = $this->typeStringResolver->resolve($expectedTypeStrings[0]->getValue());
		if ($expectedType->isSuperTypeOf($expressionType)->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('Expected subtype of %s, actual: %s', $expectedTypeStrings[0]->getValue(), $expressionType->describe(VerbosityLevel::precise())))
				->nonIgnorable()
				->identifier('phpstan.superType')
				->build(),
		];
	}

	/**
	 * @param array{Node\Arg, Node\Arg} $args
	 * @return list<IdentifierRuleError>
	 */
	private function processAssertVariableCertainty(array $args, Scope $scope): array
	{
		$certainty = $args[0]->value;
		if (!$certainty instanceof StaticCall) {
			return [
				RuleErrorBuilder::message('First argument of %s() must be TrinaryLogic call')
					->nonIgnorable()
					->identifier('phpstan.unknownExpectation')
					->build(),
			];
		}
		if (!$certainty->class instanceof Node\Name) {
			return [
				RuleErrorBuilder::message('Invalid TrinaryLogic call.')
					->nonIgnorable()
					->identifier('phpstan.unknownExpectation')
					->build(),
			];
		}

		if ($certainty->class->toString() !== 'PHPStan\\TrinaryLogic') {
			return [
				RuleErrorBuilder::message('Invalid TrinaryLogic call.')
					->nonIgnorable()
					->identifier('phpstan.unknownExpectation')
					->build(),
			];
		}

		if (!$certainty->name instanceof Node\Identifier) {
			return [
				RuleErrorBuilder::message('Invalid TrinaryLogic call.')
					->nonIgnorable()
					->identifier('phpstan.unknownExpectation')
					->build(),
			];
		}

		// @phpstan-ignore staticMethod.dynamicName
		$expectedCertaintyValue = TrinaryLogic::{$certainty->name->toString()}();
		$variable = $args[1]->value;
		if ($variable instanceof Node\Expr\Variable && is_string($variable->name)) {
			$actualCertaintyValue = $scope->hasVariableType($variable->name);
			$variableDescription = sprintf('variable $%s', $variable->name);
		} elseif ($variable instanceof Node\Expr\ArrayDimFetch && $variable->dim !== null) {
			$offset = $scope->getType($variable->dim);
			$actualCertaintyValue = $scope->getType($variable->var)->hasOffsetValueType($offset);
			$variableDescription = sprintf('offset %s', $offset->describe(VerbosityLevel::precise()));
		} else {
			return [
				RuleErrorBuilder::message('Invalid assertVariableCertainty call.')
					->nonIgnorable()
					->identifier('phpstan.unknownExpectation')
					->build(),
			];
		}

		if ($expectedCertaintyValue->equals($actualCertaintyValue)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('Expected %s certainty %s, actual: %s', $variableDescription, $expectedCertaintyValue->describe(), $actualCertaintyValue->describe()))
				->nonIgnorable()
				->identifier('phpstan.variable')
				->build(),
		];
	}

}
