<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class PrintfArrayParametersRule implements Rule
{

	public function __construct(
		private PrintfHelper $printfHelper,
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
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		$name = $functionReflection->getName();
		if (!in_array($name, ['vprintf', 'vsprintf'], true)) {
			return [];
		}

		$args = $node->getArgs();
		$argsCount = count($args);
		if ($argsCount < 1) {
			return []; // caught by CallToFunctionParametersRule
		}

		$formatArgType = $scope->getType($args[0]->value);
		$maxPlaceHoldersCount = null;
		foreach ($formatArgType->getConstantStrings() as $formatString) {
			$format = $formatString->getValue();
			$tempPlaceHoldersCount = $this->printfHelper->getPrintfPlaceholdersCount($format);
			if ($maxPlaceHoldersCount === null) {
				$maxPlaceHoldersCount = $tempPlaceHoldersCount;
			} elseif ($tempPlaceHoldersCount > $maxPlaceHoldersCount) {
				$maxPlaceHoldersCount = $tempPlaceHoldersCount;
			}
		}

		if ($maxPlaceHoldersCount === null) {
			return [];
		}

		$formatArgsCount = 0;
		if (isset($args[1])) {
			$formatArgsType = $scope->getType($args[1]->value);

			$size = null;
			$constantArrays = $formatArgsType->getConstantArrays();
			foreach ($constantArrays as $constantArray) {
				$size = $constantArray->getArraySize();

				if ($size instanceof IntegerRangeType) {
					break;
				}
				if (!$size instanceof ConstantIntegerType) {
					return [];
				}
				$formatArgsCount = $size->getValue();
			}

			if ($constantArrays === []) {
				$size = $formatArgsType->getArraySize();
			}

			if ($size instanceof IntegerRangeType) {
				if ($size->getMin() !== null && $size->getMax() !== null) {
					$values = $size->getMin() . '-' . $size->getMax();
				} elseif ($size->getMin() !== null) {
					$values = $size->getMin() . ' or more';
				} elseif ($size->getMax() !== null) {
					$values = $size->getMax() . ' or less';
				} else {
					throw new ShouldNotHappenException();
				}

				return [
					RuleErrorBuilder::message(sprintf(
						sprintf(
							'%s, %s.',
							$maxPlaceHoldersCount === 1 ? 'Call to %s contains %d placeholder' : 'Call to %s contains %d placeholders',
							'%s values given',
						),
						$name,
						$maxPlaceHoldersCount,
						$values,
					))->identifier(sprintf('argument.%s', $name))->build(),
				];
			}
		}

		if ($formatArgsCount !== $maxPlaceHoldersCount) {
			return [
				RuleErrorBuilder::message(sprintf(
					sprintf(
						'%s, %s.',
						$maxPlaceHoldersCount === 1 ? 'Call to %s contains %d placeholder' : 'Call to %s contains %d placeholders',
						$formatArgsCount === 1 ? '%d value given' : '%d values given',
					),
					$name,
					$maxPlaceHoldersCount,
					$formatArgsCount,
				))->identifier(sprintf('argument.%s', $name))->build(),
			];
		}

		return [];
	}

}
