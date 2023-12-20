<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClosureNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\VerbosityLevel;
use function is_string;
use function sprintf;

/**
 * @implements Rule<InClosureNode>
 */
class IncompatibleClosureDefaultParameterTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return InClosureNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$parameters = $node->getClosureType()->getParameters();

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
			$parameterType = $parameters[$paramI]->getType();
			$parameterType = TemplateTypeHelper::resolveToBounds($parameterType);

			$accepts = $parameterType->acceptsWithReason($defaultValueType, true);
			if ($accepts->yes()) {
				continue;
			}

			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($parameterType, $defaultValueType);

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Default value of the parameter #%d $%s (%s) of anonymous function is incompatible with type %s.',
				$paramI + 1,
				$param->var->name,
				$defaultValueType->describe($verbosityLevel),
				$parameterType->describe($verbosityLevel),
			))
				->line($param->getStartLine())
				->identifier('parameter.defaultValue')
				->acceptsReasonsTip($accepts->reasons)
				->build();
		}

		return $errors;
	}

}
