<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class UnserializeRule implements Rule
{

	public function __construct(
		private readonly PhpVersion $phpVersion,
		private readonly ReflectionProvider $reflectionProvider,
		private readonly bool $checkInsecureUnserialize,
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
		if ($functionReflection->getName() !== 'unserialize') {
			return [];
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
		if ($normalizedFuncCall === null) {
			return [];
		}

		$args = $normalizedFuncCall->getArgs();
		if (count($args) !== 2) {
			if ($this->checkInsecureUnserialize) {
				return [
					RuleErrorBuilder::message(
						'Calling unserialize() without parameter $2 options and "allowed_classes" set to false or a list of allowed class names is insecure.',
					)->identifier('unserialize.options.missing')->build(),
				];
			}
			return [];
		}

		$type = $scope->getType($args[1]->value);
		$constantArrays = $type->getConstantArrays();
		if ($constantArrays === []) {
			return [];
		}

		$allowedClassesChecked = false;
		$errors = [];
		foreach ($constantArrays[0]->getValueTypes() as $i => $valueType) {
			$key = $constantArrays[0]->getKeyTypes()[$i]->getValue();
			switch ($key) {
				case 'allowed_classes':
					$allowedClassesChecked = true;
					if ($valueType->isBoolean()->yes()) {
						if ($this->checkInsecureUnserialize && $valueType->isTrue()->yes()) {
							$errors[] = RuleErrorBuilder::message(
								'Parameter #2 $options to function unserialize must either be false or a list of allowed class names.',
							)->identifier('unserialize.allowedClasses.insecure')->build();
						}
						continue 2;
					}
					$optionConstantArrays = $valueType->getConstantArrays();
					if ($valueType->isBoolean()->no() && $optionConstantArrays !== []) {
						foreach ($optionConstantArrays[0]->getValueTypes() as $j => $itemType) {
							$constantStrings = $itemType->getConstantStrings();
							if ($constantStrings !== []) {
								continue;
							}
							$errors[] = RuleErrorBuilder::message(sprintf(
								'Parameter #2 $options to function unserialize contains an invalid value for "allowed_classes" item #%d.',
								$j + 1,
							))->identifier('unserialize.allowedClasses.invalidType')->build();
						}
					} else {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Parameter #2 $options to function unserialize contains an invalid value %s for "allowed_classes".',
							$valueType->describe(VerbosityLevel::value()),
						))->identifier('unserialize.allowedClasses.invalidType')->build();
					}
					break;
				case 'max_depth':
					if (!$this->phpVersion->supportsUnserializeMaxDepthOption()) {
						$errors[] = RuleErrorBuilder::message(
							'Parameter #2 $options to function unserialize contains an option "max_depth" which is not supported by this PHP version.',
						)->identifier('unserialize.maxDepth.unsupported')->build();
					} elseif ($valueType->isInteger()->no()) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Parameter #2 $options to function unserialize contains an invalid value %s for "max_depth".',
							$valueType->describe(VerbosityLevel::value()),
						))->identifier('unserialize.maxDepth.invalidType')->build();
					}
					break;
				default:
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Parameter #2 $options to function unserialize contains unsupported option "%s".',
						$key,
					))->identifier('unserialize.unsupported')->build();
			}
		}
		if ($this->checkInsecureUnserialize && !$allowedClassesChecked) {
			$errors[] = RuleErrorBuilder::message(
				'Parameter #2 $options to function unserialize must be present with "allowed_classes" set to false or a list of allowed class names.',
			)->identifier('unserialize.allowedClasses.missing')->build();
		}

		return $errors;
	}

}
