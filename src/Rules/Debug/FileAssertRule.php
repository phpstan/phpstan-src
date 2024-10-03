<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
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
final class FileAssertRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
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

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$function = $this->reflectionProvider->getFunction($node->name, $scope);
		if ($function->getName() === 'PHPStan\\Testing\\assertType') {
			return $this->processAssertType($node->getArgs(), $scope);
		}

		if ($function->getName() === 'PHPStan\\Testing\\assertNativeType') {
			return $this->processAssertNativeType($node->getArgs(), $scope);
		}

		if ($function->getName() === 'PHPStan\\Testing\\assertVariableCertainty') {
			return $this->processAssertVariableCertainty($node->getArgs(), $scope);
		}

		return [];
	}

	/**
	 * @param Node\Arg[] $args
	 * @return list<IdentifierRuleError>
	 */
	private function processAssertType(array $args, Scope $scope): array
	{
		if (count($args) !== 2) {
			return [];
		}

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
	 * @param Node\Arg[] $args
	 * @return list<IdentifierRuleError>
	 */
	private function processAssertNativeType(array $args, Scope $scope): array
	{
		if (count($args) !== 2) {
			return [];
		}

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
	 * @param Node\Arg[] $args
	 * @return list<IdentifierRuleError>
	 */
	private function processAssertVariableCertainty(array $args, Scope $scope): array
	{
		if (count($args) !== 2) {
			return [];
		}

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
