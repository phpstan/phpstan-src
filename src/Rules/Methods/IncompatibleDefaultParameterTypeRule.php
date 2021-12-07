<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\VerbosityLevel;
use function is_string;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
class IncompatibleDefaultParameterTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $scope->getFunction();
		if (!$method instanceof PhpMethodFromParserNodeReflection) {
			return [];
		}

		$parameters = ParametersAcceptorSelector::selectSingle($method->getVariants());

		$errors = [];
		foreach ($node->getOriginalNode()->getParams() as $paramI => $param) {
			if ($param->default === null) {
				continue;
			}
			if (
				$param->var instanceof Node\Expr\Error
				|| !is_string($param->var->name)
			) {
				throw new ShouldNotHappenException();
			}

			$defaultValueType = $scope->getType($param->default);
			$parameterType = $parameters->getParameters()[$paramI]->getType();
			$parameterType = TemplateTypeHelper::resolveToBounds($parameterType);

			if ($parameterType->accepts($defaultValueType, true)->yes()) {
				continue;
			}

			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($parameterType, $defaultValueType);

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Default value of the parameter #%d $%s (%s) of method %s::%s() is incompatible with type %s.',
				$paramI + 1,
				$param->var->name,
				$defaultValueType->describe($verbosityLevel),
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$parameterType->describe($verbosityLevel)
			))->line($param->getLine())->build();
		}

		return $errors;
	}

}
