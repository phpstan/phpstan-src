<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class PrintfParametersRule implements Rule
{

	private const FORMAT_ARGUMENT_POSITIONS = [
		'printf' => 0,
		'sprintf' => 0,
		'sscanf' => 1,
		'fscanf' => 1,
	];
	private const MINIMUM_NUMBER_OF_ARGUMENTS = [
		'printf' => 1,
		'sprintf' => 1,
		'sscanf' => 3,
		'fscanf' => 3,
	];

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
		if (!array_key_exists($name, self::FORMAT_ARGUMENT_POSITIONS)) {
			return [];
		}

		$formatArgumentPosition = self::FORMAT_ARGUMENT_POSITIONS[$name];

		$args = $node->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack) {
				return [];
			}
		}
		$argsCount = count($args);
		if ($argsCount < self::MINIMUM_NUMBER_OF_ARGUMENTS[$name]) {
			return []; // caught by CallToFunctionParametersRule
		}

		$formatArgType = $scope->getType($args[$formatArgumentPosition]->value);
		$maxPlaceHoldersCount = null;
		foreach ($formatArgType->getConstantStrings() as $formatString) {
			$format = $formatString->getValue();

			if (in_array($name, ['sprintf', 'printf'], true)) {
				$tempPlaceHoldersCount = $this->printfHelper->getPrintfPlaceholdersCount($format);
			} else {
				$tempPlaceHoldersCount = $this->printfHelper->getScanfPlaceholdersCount($format);
			}

			if ($maxPlaceHoldersCount === null) {
				$maxPlaceHoldersCount = $tempPlaceHoldersCount;
			} elseif ($tempPlaceHoldersCount > $maxPlaceHoldersCount) {
				$maxPlaceHoldersCount = $tempPlaceHoldersCount;
			}
		}

		if ($maxPlaceHoldersCount === null) {
			return [];
		}

		$argsCount -= $formatArgumentPosition;

		if ($argsCount !== $maxPlaceHoldersCount + 1) {
			return [
				RuleErrorBuilder::message(sprintf(
					sprintf(
						'%s, %s.',
						$maxPlaceHoldersCount === 1 ? 'Call to %s contains %d placeholder' : 'Call to %s contains %d placeholders',
						$argsCount - 1 === 1 ? '%d value given' : '%d values given',
					),
					$name,
					$maxPlaceHoldersCount,
					$argsCount - 1,
				))->identifier(sprintf('argument.%s', $name))->build(),
			];
		}

		return [];
	}

}
