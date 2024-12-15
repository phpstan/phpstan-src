<?php declare(strict_types = 1);

namespace PHPStan\Rules\Missing;

use Generator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function sprintf;
use function ucfirst;

/**
 * @implements Rule<ExecutionEndNode>
 */
final class MissingReturnRule implements Rule
{

	public function __construct(
		private bool $checkExplicitMixedMissingReturn,
		private bool $checkPhpDocMissingReturn,
	)
	{
	}

	public function getNodeType(): string
	{
		return ExecutionEndNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$statementResult = $node->getStatementResult();
		if ($statementResult->isAlwaysTerminating()) {
			return [];
		}

		$anonymousFunctionReturnType = $scope->getAnonymousFunctionReturnType();
		$scopeFunction = $scope->getFunction();
		if ($anonymousFunctionReturnType !== null) {
			$returnType = $anonymousFunctionReturnType;
			$description = 'Anonymous function';
			if (!$node->hasNativeReturnTypehint()) {
				return [];
			}
		} elseif ($scopeFunction !== null) {
			$returnType = $scopeFunction->getReturnType();
			if ($scopeFunction instanceof PhpMethodFromParserNodeReflection) {
				if (!$scopeFunction->isPropertyHook()) {
					$description = sprintf('Method %s::%s()', $scopeFunction->getDeclaringClass()->getDisplayName(), $scopeFunction->getName());
				} else {
					$description = sprintf('%s hook for property %s::$%s', ucfirst($scopeFunction->getPropertyHookName()), $scopeFunction->getDeclaringClass()->getDisplayName(), $scopeFunction->getHookedPropertyName());
				}
			} else {
				$description = sprintf('Function %s()', $scopeFunction->getName());
			}
		} else {
			throw new ShouldNotHappenException();
		}

		$returnType = TypeUtils::resolveLateResolvableTypes($returnType);

		$isVoidSuperType = $returnType->isSuperTypeOf(new VoidType());
		if ($isVoidSuperType->yes() && !$returnType instanceof MixedType) {
			return [];
		}

		if ($statementResult->hasYield()) {
			if ($this->checkPhpDocMissingReturn) {
				$generatorReturnType = $returnType->getTemplateType(Generator::class, 'TReturn');
				if (!$generatorReturnType instanceof ErrorType) {
					$returnType = $generatorReturnType;
					if ($returnType->isVoid()->yes()) {
						return [];
					}
					if (!$returnType instanceof MixedType) {
						return [
							RuleErrorBuilder::message(
								sprintf('%s should return %s but return statement is missing.', $description, $returnType->describe(VerbosityLevel::typeOnly())),
							)
								->line($node->getNode()->getStartLine())
								->identifier('return.missing')
								->build(),
						];
					}
				}
			}
			return [];
		}

		if (
			!$node->hasNativeReturnTypehint()
			&& !$this->checkPhpDocMissingReturn
			&& TypeCombinator::containsNull($returnType)
		) {
			return [];
		}

		if ($returnType instanceof NeverType && $returnType->isExplicit()) {
			$errorBuilder = RuleErrorBuilder::message(sprintf('%s should always throw an exception or terminate script execution but doesn\'t do that.', $description))->line($node->getNode()->getStartLine());

			if ($node->hasNativeReturnTypehint()) {
				$errorBuilder->nonIgnorable();
			}

			$errorBuilder->identifier('return.never');

			return [
				$errorBuilder->build(),
			];
		}

		if (
			$returnType instanceof MixedType
			&& !$returnType instanceof TemplateMixedType
			&& !$node->hasNativeReturnTypehint()
			&& (
				!$returnType->isExplicitMixed()
				|| !$this->checkExplicitMixedMissingReturn
			)
		) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message(
			sprintf('%s should return %s but return statement is missing.', $description, $returnType->describe(VerbosityLevel::typeOnly())),
		)->line($node->getNode()->getStartLine());

		if ($node->hasNativeReturnTypehint()) {
			$errorBuilder->nonIgnorable();
		}

		$errorBuilder->identifier('return.missing');

		return [
			$errorBuilder->build(),
		];
	}

}
