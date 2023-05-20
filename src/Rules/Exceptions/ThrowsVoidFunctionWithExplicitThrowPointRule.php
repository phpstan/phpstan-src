<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
class ThrowsVoidFunctionWithExplicitThrowPointRule implements Rule
{

	public function __construct(
		private ExceptionTypeResolver $exceptionTypeResolver,
		private bool $missingCheckedExceptionInThrows,
	)
	{
	}

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$statementResult = $node->getStatementResult();
		$functionReflection = $scope->getFunction();
		if (!$functionReflection instanceof FunctionReflection) {
			throw new ShouldNotHappenException();
		}

		if ($functionReflection->getThrowType() === null || !$functionReflection->getThrowType()->isVoid()->yes()) {
			return [];
		}

		$errors = [];
		foreach ($statementResult->getThrowPoints() as $throwPoint) {
			if (!$throwPoint->isExplicit()) {
				continue;
			}

			foreach (TypeUtils::flattenTypes($throwPoint->getType()) as $throwPointType) {
				$isCheckedException = TrinaryLogic::createFromBoolean($this->missingCheckedExceptionInThrows)->lazyAnd(
					$throwPointType->getObjectClassNames(),
					fn (string $objectClassName) => TrinaryLogic::createFromBoolean($this->exceptionTypeResolver->isCheckedException($objectClassName, $throwPoint->getScope())),
				);
				if ($isCheckedException->yes()) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'Function %s() throws exception %s but the PHPDoc contains @throws void.',
					$functionReflection->getName(),
					$throwPointType->describe(VerbosityLevel::typeOnly()),
				))
					->line($throwPoint->getNode()->getLine())
					->identifier('throws.void')
					->build();
			}
		}

		return $errors;
	}

}
