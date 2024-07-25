<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
final class ImpossibleCheckTypeStaticMethodCallRule implements Rule
{

	public function __construct(
		private ImpossibleCheckTypeHelper $impossibleCheckTypeHelper,
		private bool $checkAlwaysTrueCheckTypeFunctionCall,
		private bool $treatPhpDocTypesAsCertain,
		private bool $reportAlwaysTrueInLastCondition,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		$isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $node);
		if ($isAlways === null) {
			return [];
		}

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
			if (!$this->treatPhpDocTypesAsCertain) {
				return $ruleErrorBuilder;
			}

			$isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $node);
			if ($isAlways !== null) {
				return $ruleErrorBuilder;
			}

			return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
		};

		if (!$isAlways) {
			$method = $this->getMethod($node->class, $node->name->name, $scope);

			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Call to static method %s::%s()%s will always evaluate to false.',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
				)))->identifier('staticMethod.impossibleType')->build(),
			];
		} elseif ($this->checkAlwaysTrueCheckTypeFunctionCall) {
			$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
			if ($isLast === true && !$this->reportAlwaysTrueInLastCondition) {
				return [];
			}

			$method = $this->getMethod($node->class, $node->name->name, $scope);
			$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
				'Call to static method %s::%s()%s will always evaluate to true.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
			)));
			if ($isLast === false && !$this->reportAlwaysTrueInLastCondition) {
				$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
			}

			$errorBuilder->identifier('staticMethod.alreadyNarrowedType');

			return [$errorBuilder->build()];
		}

		return [];
	}

	/**
	 * @param Node\Name|Expr $class
	 * @throws ShouldNotHappenException
	 */
	private function getMethod(
		$class,
		string $methodName,
		Scope $scope,
	): MethodReflection
	{
		if ($class instanceof Node\Name) {
			$calledOnType = $scope->resolveTypeByName($class);
		} else {
			$calledOnType = $scope->getType($class);
		}

		$method = $scope->getMethodReflection($calledOnType, $methodName);
		if ($method === null) {
			throw new ShouldNotHappenException();
		}

		return $method;
	}

}
