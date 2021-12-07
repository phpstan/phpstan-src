<?php declare(strict_types = 1);

namespace PHPStan\Rules\Missing;

use Generator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\GenericTypeVariableResolver;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function sprintf;

/**
 * @implements Rule<ExecutionEndNode>
 */
class MissingReturnRule implements Rule
{

	private bool $checkExplicitMixedMissingReturn;

	private bool $checkPhpDocMissingReturn;

	public function __construct(
		bool $checkExplicitMixedMissingReturn,
		bool $checkPhpDocMissingReturn
	)
	{
		$this->checkExplicitMixedMissingReturn = $checkExplicitMixedMissingReturn;
		$this->checkPhpDocMissingReturn = $checkPhpDocMissingReturn;
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
			$returnType = ParametersAcceptorSelector::selectSingle($scopeFunction->getVariants())->getReturnType();
			if ($scopeFunction instanceof MethodReflection) {
				$description = sprintf('Method %s::%s()', $scopeFunction->getDeclaringClass()->getDisplayName(), $scopeFunction->getName());
			} else {
				$description = sprintf('Function %s()', $scopeFunction->getName());
			}
		} else {
			throw new ShouldNotHappenException();
		}

		$isVoidSuperType = $returnType->isSuperTypeOf(new VoidType());
		if ($isVoidSuperType->yes() && !$returnType instanceof MixedType) {
			return [];
		}

		if ($statementResult->hasYield()) {
			if ($returnType instanceof TypeWithClassName && $this->checkPhpDocMissingReturn) {
				$generatorReturnType = GenericTypeVariableResolver::getType(
					$returnType,
					Generator::class,
					'TReturn'
				);
				if ($generatorReturnType !== null) {
					$returnType = $generatorReturnType;
					if ($returnType instanceof VoidType) {
						return [];
					}
					if (!$returnType instanceof MixedType) {
						return [
							RuleErrorBuilder::message(
								sprintf('%s should return %s but return statement is missing.', $description, $returnType->describe(VerbosityLevel::typeOnly()))
							)->line($node->getNode()->getStartLine())->build(),
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
			sprintf('%s should return %s but return statement is missing.', $description, $returnType->describe(VerbosityLevel::typeOnly()))
		)->line($node->getNode()->getStartLine());

		if ($node->hasNativeReturnTypehint()) {
			$errorBuilder->nonIgnorable();
		}

		return [
			$errorBuilder->build(),
		];
	}

}
