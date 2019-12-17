<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Function_>
 */
final class MissingFunctionParameterTypehintRule implements \PHPStan\Rules\Rule
{

	/** @var Broker */
	private $broker;

	/** @var \PHPStan\Rules\MissingTypehintCheck */
	private $missingTypehintCheck;

	public function __construct(
		Broker $broker,
		MissingTypehintCheck $missingTypehintCheck
	)
	{
		$this->broker = $broker;
		$this->missingTypehintCheck = $missingTypehintCheck;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Stmt\Function_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionName = $node->name->name;
		if (isset($node->namespacedName)) {
			$functionName = (string) $node->namespacedName;
		}
		$functionNameName = new Name($functionName);
		if (!$this->broker->hasFunction($functionNameName, null)) {
			return [];
		}
		$functionReflection = $this->broker->getFunction($functionNameName, null);

		$messages = [];

		foreach (ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getParameters() as $parameterReflection) {
			foreach ($this->checkFunctionParameter($functionReflection, $parameterReflection) as $parameterMessage) {
				$messages[] = $parameterMessage;
			}
		}

		return $messages;
	}

	/**
	 * @param \PHPStan\Reflection\FunctionReflection $functionReflection
	 * @param \PHPStan\Reflection\ParameterReflection $parameterReflection
	 * @return \PHPStan\Rules\RuleError[]
	 */
	private function checkFunctionParameter(FunctionReflection $functionReflection, ParameterReflection $parameterReflection): array
	{
		$parameterType = $parameterReflection->getType();

		if ($parameterType instanceof MixedType && !$parameterType->isExplicitMixed()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Function %s() has parameter $%s with no typehint specified.',
					$functionReflection->getName(),
					$parameterReflection->getName()
				))->build(),
			];
		}

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($parameterType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Function %s() has parameter $%s with no value type specified in iterable type %s.',
				$functionReflection->getName(),
				$parameterReflection->getName(),
				$iterableTypeDescription
			))->tip(sprintf(MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP, $iterableTypeDescription))->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($parameterType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Function %s() has parameter $%s with generic %s but does not specify its types: %s',
				$functionReflection->getName(),
				$parameterReflection->getName(),
				$name,
				implode(', ', $genericTypeNames)
			))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
		}

		return $messages;
	}

}
