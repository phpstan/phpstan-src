<?php declare(strict_types = 1);

namespace PHPStan\Rules\Missing;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\GenericTypeVariableResolver;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\ExecutionEndNode>
 */
class MissingReturnRule implements Rule
{

	/** @var bool */
	private $checkExplicitMixedMissingReturn;

	/** @var bool */
	private $checkPhpDocMissingReturn;

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
		} elseif ($scopeFunction !== null) {
			$returnType = ParametersAcceptorSelector::selectSingle($scopeFunction->getVariants())->getReturnType();
			if ($scopeFunction instanceof MethodReflection) {
				$description = sprintf('Method %s::%s()', $scopeFunction->getDeclaringClass()->getDisplayName(), $scopeFunction->getName());
			} else {
				$description = sprintf('Function %s()', $scopeFunction->getName());
			}
		} else {
			throw new \PHPStan\ShouldNotHappenException();
		}

		if ($returnType instanceof VoidType) {
			return [];
		}

		if ($statementResult->hasYield()) {
			if ($returnType instanceof TypeWithClassName && $this->checkPhpDocMissingReturn) {
				$generatorReturnType = GenericTypeVariableResolver::getType(
					$returnType,
					\Generator::class,
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

		if (!$node->hasNativeReturnTypehint() && !$this->checkPhpDocMissingReturn) {
			return [];
		}

		if (
			$returnType instanceof MixedType
			&& !$returnType instanceof TemplateMixedType
			&& (
				!$returnType->isExplicitMixed()
				|| !$this->checkExplicitMixedMissingReturn
			)
		) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('%s should return %s but return statement is missing.', $description, $returnType->describe(VerbosityLevel::typeOnly()))
			)->line($node->getNode()->getStartLine())->build(),
		];
	}

}
