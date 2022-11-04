<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class ThrowsVoidMethodWithExplicitThrowPointRule implements Rule
{

	public function __construct(
		private ExceptionTypeResolver $exceptionTypeResolver,
		private bool $missingCheckedExceptionInThrows,
	)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$statementResult = $node->getStatementResult();
		$methodReflection = $scope->getFunction();
		if (!$methodReflection instanceof MethodReflection) {
			throw new ShouldNotHappenException();
		}

		if (!$methodReflection->getThrowType() instanceof VoidType) {
			return [];
		}

		$errors = [];
		foreach ($statementResult->getThrowPoints() as $throwPoint) {
			if (!$throwPoint->isExplicit()) {
				continue;
			}

			foreach (TypeUtils::flattenTypes($throwPoint->getType()) as $throwPointType) {
				if (
					$throwPointType instanceof TypeWithClassName
					&& $this->exceptionTypeResolver->isCheckedException(
						$throwPointType->getClassName(),
						$throwPoint->getScope(),
						$throwPoint->getNode()
					)
					&& $this->missingCheckedExceptionInThrows
				) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'Method %s::%s() throws exception %s but the PHPDoc contains @throws void.',
					$methodReflection->getDeclaringClass()->getDisplayName(),
					$methodReflection->getName(),
					$throwPointType->describe(VerbosityLevel::typeOnly()),
				))->line($throwPoint->getNode()->getLine())->build();
			}
		}

		return $errors;
	}

}
