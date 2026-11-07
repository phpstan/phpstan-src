<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<FuncCall>
 */
#[RegisteredRule(level: 5)]
final class ArrayPointerFunctionOnObjectRule implements Rule
{

	private const FUNCTIONS = ['current', 'pos', 'key', 'next', 'prev', 'reset', 'end'];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->deprecatesArrayPointerFunctionsOnObjects()) {
			return [];
		}

		if (!$node->name instanceof Node\Name) {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) !== 1 || $args[0]->unpack) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionName = $this->reflectionProvider->getFunction($node->name, $scope)->getName();
		if (!in_array($functionName, self::FUNCTIONS, true)) {
			return [];
		}

		$type = $scope->getType($args[0]->value);
		$arrayType = new ArrayType(new MixedType(), new MixedType());
		if (
			!TypeCombinator::union($arrayType, new ObjectWithoutClassType())->isSuperTypeOf($type)->yes()
			|| $this->ruleLevelHelper->accepts($arrayType, $type, $scope->isDeclareStrictTypes())->result
		) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Calling %s() on %s is deprecated in PHP 8.1.',
				$functionName,
				$type->describe(VerbosityLevel::typeOnly()),
			))
				->identifier('argument.deprecatedObject')
				->build(),
		];
	}

}
