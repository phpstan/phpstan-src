<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\Php\ArrayColumnHelper;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class ArrayColumnRule implements Rule
{

	public function __construct(
		private readonly ReflectionProvider $reflectionProvider,
		private readonly bool $treatPhpDocTypesAsCertain,
		private readonly bool $treatPhpDocTypesAsCertainTip,
		private readonly ArrayColumnHelper $arrayColumnHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) < 2) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if ($functionReflection->getName() !== 'array_column') {
			return [];
		}

		$indexKeyType = null;
		if ($this->treatPhpDocTypesAsCertain) {
			$arrayType = $scope->getType($args[0]->value);
			$columnKeyType = $scope->getType($args[1]->value);
			if (count($args) >= 3) {
				$indexKeyType = $scope->getType($args[2]->value);
			}
		} else {
			$arrayType = $scope->getNativeType($args[0]->value);
			$columnKeyType = $scope->getNativeType($args[1]->value);
			if (count($args) >= 3) {
				$indexKeyType = $scope->getNativeType($args[2]->value);
			}
		}

		$errors = [];
		if ($columnKeyType->isNull()->no()) {
			[$returnValueType] = $this->arrayColumnHelper->getReturnValueType($arrayType, $columnKeyType, $scope);
			if ($returnValueType instanceof NeverType) {
				$errorBuilder = RuleErrorBuilder::message(sprintf(
					'Cannot access column %s on %s.',
					$columnKeyType->describe(VerbosityLevel::value()),
					$arrayType->getIterableValueType()->describe(VerbosityLevel::value()),
				))->identifier('arrayColumn.column');

				if ($this->treatPhpDocTypesAsCertainTip) {
					$errorBuilder->treatPhpDocTypesAsCertainTip();
				}

				$errors[] = $errorBuilder->build();
			}
		}

		if ($indexKeyType !== null && $indexKeyType->isNull()->no()) {
			$returnIndexType = $this->arrayColumnHelper->getReturnIndexType($arrayType, $indexKeyType, $scope);
			if ($returnIndexType instanceof NeverType) {
				$errorBuilder = RuleErrorBuilder::message(sprintf(
					'Cannot access column %s on %s.',
					$indexKeyType->describe(VerbosityLevel::value()),
					$arrayType->getIterableValueType()->describe(VerbosityLevel::value()),
				))->identifier('arrayColumn.index');

				if ($this->treatPhpDocTypesAsCertainTip) {
					$errorBuilder->treatPhpDocTypesAsCertainTip();
				}

				$errors[] = $errorBuilder->build();
			}
		}

		return $errors;
	}

}
