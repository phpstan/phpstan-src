<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use function ucfirst;

/**
 * @implements Rule<PropertyHookReturnStatementsNode>
 */
final class ThrowsVoidPropertyHookWithExplicitThrowPointRule implements Rule
{

	public function __construct(
		private ExceptionTypeResolver $exceptionTypeResolver,
		private bool $missingCheckedExceptionInThrows,
	)
	{
	}

	public function getNodeType(): string
	{
		return PropertyHookReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$statementResult = $node->getStatementResult();
		$hookReflection = $node->getHookReflection();

		if ($hookReflection->getThrowType() === null || !$hookReflection->getThrowType()->isVoid()->yes()) {
			return [];
		}

		if ($hookReflection->getPropertyHookName() === null) {
			throw new ShouldNotHappenException();
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
					'%s hook for property %s::$%s throws exception %s but the PHPDoc contains @throws void.',
					ucfirst($hookReflection->getPropertyHookName()),
					$hookReflection->getDeclaringClass()->getDisplayName(),
					$hookReflection->getHookedPropertyName(),
					$throwPointType->describe(VerbosityLevel::typeOnly()),
				))
					->line($throwPoint->getNode()->getStartLine())
					->identifier('throws.void')
					->build();
			}
		}

		return $errors;
	}

}
