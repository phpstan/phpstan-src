<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Fixable\PhpDoc\PhpDocEditor;
use PHPStan\PhpDocParser\Ast\Node as PhpDocAstNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Rules\Functions\CallToFunctionStatementWithoutSideEffectsRule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use function array_filter;
use function array_key_exists;
use function array_merge;
use function count;
use function in_array;
use function is_string;
use function lcfirst;
use function sprintf;
use function str_contains;
use function substr;
use function trim;

#[AutowiredService]
final class FunctionPurityCheck
{

	public function __construct(private PhpDocEditor $phpDocEditor)
	{
	}

	/**
	 * @param 'Function'|'Method'|'PropertyHook' $identifier
	 * @param ExtendedParameterReflection[] $parameters
	 * @param ImpurePoint[] $impurePoints
	 * @param ThrowPoint[] $throwPoints
	 * @param Stmt[] $statements
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Scope $scope,
		string $functionDescription,
		string $identifier,
		FunctionReflection|ExtendedMethodReflection $functionReflection,
		array $parameters,
		Type $returnType,
		array $impurePoints,
		array $throwPoints,
		array $statements,
		bool $isConstructor,
	): array
	{
		$errors = [];
		$isPure = $functionReflection->isPure();

		$pureUnlessCallableParameters = $functionReflection->getPureUnlessCallableIsImpureParameters();
		$pureUnlessCallableParamNames = [];
		foreach ($parameters as $parameter) {
			if (!array_key_exists($parameter->getName(), $pureUnlessCallableParameters)) {
				continue;
			}

			$pureUnlessCallableParamNames[$parameter->getName()] = true;

			$acceptors = $parameter->getType()->getCallableParametersAcceptors($scope);
			if (count($acceptors) === 0) {
				continue;
			}

			$allPure = true;
			foreach ($acceptors as $acceptor) {
				if ($acceptor->isPure()->yes()) {
					continue;
				}

				$allPure = false;
				break;
			}

			if (!$allPure) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s is marked @pure-unless-callable-is-impure for parameter $%s, but $%s is already a pure callable, so %s can be marked @phpstan-pure instead.',
				$functionDescription,
				$parameter->getName(),
				$parameter->getName(),
				lcfirst($functionDescription),
			))->identifier(sprintf('pure%s.redundantUnlessCallable', $identifier))->build();
		}

		$pureUnlessParameterPassedParameters = $functionReflection->getPureUnlessParameterPassedParameters();
		$pureUnlessParameterPassedParamNames = [];
		foreach ($parameters as $parameter) {
			if (!array_key_exists($parameter->getName(), $pureUnlessParameterPassedParameters)) {
				continue;
			}

			$pureUnlessParameterPassedParamNames[$parameter->getName()] = true;

			if (!$parameter->isOptional()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s is marked @pure-unless-parameter-passed for parameter $%s, but $%s is not optional, so %s is never pure.',
					$functionDescription,
					$parameter->getName(),
					$parameter->getName(),
					lcfirst($functionDescription),
				))->identifier(sprintf('pure%s.nonOptionalParameterPassed', $identifier))->build();
			}

			// Passing a by-value parameter cannot introduce a side effect on its own,
			// so the body would have to be impure for the tag to mean anything - and
			// that impurity is not conditional on the argument being passed. Only a
			// by-ref out parameter is written through without producing an impure
			// point, which is what makes the conditional verdict checkable.
			if (!$parameter->passedByReference()->no()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s is marked @pure-unless-parameter-passed for parameter $%s, but $%s is not passed by reference.',
				$functionDescription,
				$parameter->getName(),
				$parameter->getName(),
			))->identifier(sprintf('pure%s.parameterPassedNotByRef', $identifier))->build();
		}

		if ($isPure->yes()) {
			foreach ($parameters as $parameter) {
				if (!$parameter->passedByReference()->createsNewVariable()) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s is marked as pure but parameter $%s is passed by reference.',
					$functionDescription,
					$parameter->getName(),
				))->identifier(sprintf('pure%s.parameterByRef', $identifier))->build();
			}

			$throwType = $functionReflection->getThrowType();
			if (
				$returnType->isVoid()->yes()
				&& !$isConstructor
				&& ($throwType === null || $throwType->isVoid()->yes())
				&& $functionReflection->getAsserts()->getAll() === []
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s is marked as pure but returns void.',
					$functionDescription,
				))->identifier(sprintf('pure%s.void', $identifier))->build();
			}

			$errors = array_merge($errors, $this->reportImpurePoints($impurePoints, $pureUnlessCallableParamNames, $functionDescription));
		} elseif ($pureUnlessCallableParamNames !== [] || $pureUnlessParameterPassedParamNames !== []) {
			// A function declared @pure-unless-callable-is-impure is pure except
			// for the flagged callables, so its body is checked for purity while
			// the flagged callables' own invocations are exempt. The same holds for
			// @pure-unless-parameter-passed: writing through the flagged by-ref
			// parameter is not an impure point, so the rest of the body still has
			// to be pure for the conditional verdict to hold.
			$errors = array_merge($errors, $this->reportImpurePoints($impurePoints, $pureUnlessCallableParamNames, $functionDescription));
		} elseif ($isPure->no()) {
			if (
				count($throwPoints) === 0
				&& count($impurePoints) === 0
				&& count($functionReflection->getAsserts()->getAll()) === 0
				&& (
					!$functionReflection instanceof ExtendedMethodReflection
					|| $functionReflection->isFinal()->yes()
					|| $functionReflection->getDeclaringClass()->isFinal()
				)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s is marked as impure but does not have any side effects.',
					$functionDescription,
				))->identifier(sprintf('impure%s.pure', $identifier))->build();
			}
		} elseif ($returnType->isVoid()->yes()) {
			if (
				count($throwPoints) === 0
				&& count($impurePoints) === 0
				&& !$isConstructor
				&& (!$functionReflection instanceof ExtendedMethodReflection || $functionReflection->isPrivate())
				&& count($functionReflection->getAsserts()->getAll()) === 0
			) {
				$hasByRef = false;
				foreach ($parameters as $parameter) {
					if (!$parameter->passedByReference()->createsNewVariable()) {
						continue;
					}

					$hasByRef = true;
					break;
				}

				$statements = array_filter($statements, static function (Stmt $stmt): bool {
					if ($stmt instanceof Stmt\Nop) {
						return false;
					}

					if (!$stmt instanceof Stmt\Expression) {
						return true;
					}
					if (!$stmt->expr instanceof FuncCall) {
						return true;
					}
					if (!$stmt->expr->name instanceof Name) {
						return true;
					}

					return !in_array($stmt->expr->name->toString(), CallToFunctionStatementWithoutSideEffectsRule::PHPSTAN_TESTING_FUNCTIONS, true);
				});

				if (!$hasByRef && count($statements) > 0) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'%s returns void but does not have any side effects.',
						$functionDescription,
					))->identifier('void.pure')->build();
				}
			}
		}

		return $errors;
	}

	/**
	 * Whether a function without a purity tag could get @phpstan-pure without
	 * any error from check(), and without changing what calling it as a
	 * statement means. Overridable methods are left out, because the tag
	 * would bind the overriding methods too.
	 *
	 * @param ExtendedParameterReflection[] $parameters
	 * @param ImpurePoint[] $impurePoints
	 * @param ThrowPoint[] $throwPoints
	 */
	public function canBeMarkedPure(
		FunctionReflection|ExtendedMethodReflection $functionReflection,
		array $parameters,
		Type $returnType,
		array $impurePoints,
		array $throwPoints,
		bool $isGenerator,
		bool $returnsByRef,
		bool $isConstructor,
	): bool
	{
		if (!$functionReflection->isPure()->maybe()) {
			return false;
		}

		if (
			$functionReflection->getPureUnlessCallableIsImpureParameters() !== []
			|| $functionReflection->getPureUnlessParameterPassedParameters() !== []
		) {
			return false;
		}

		// a caller can write through a returned reference
		if (!$returnType->isVoid()->no() || $isGenerator || $returnsByRef) {
			return false;
		}

		if (count($impurePoints) !== 0) {
			return false;
		}

		// a function that throws on purpose may be called only for that, and
		// a call to a pure function as a statement is reported as useless;
		// implicit throw points come from every call and do not count
		foreach ($throwPoints as $throwPoint) {
			if ($throwPoint->isExplicit()) {
				return false;
			}
		}

		$throwType = $functionReflection->getThrowType();
		if ($throwType !== null && !$throwType->isVoid()->yes()) {
			return false;
		}

		if (count($functionReflection->getAsserts()->getAll()) !== 0) {
			return false;
		}

		foreach ($parameters as $parameter) {
			if (!$parameter->passedByReference()->equals(PassedByReference::createNo())) {
				return false;
			}
		}

		if ($isConstructor) {
			return false;
		}

		if ($functionReflection instanceof ExtendedMethodReflection) {
			return $functionReflection->isPrivate()
				|| $functionReflection->isFinal()->equals(TrinaryLogic::createYes())
				|| $functionReflection->getDeclaringClass()->isFinal();
		}

		return true;
	}

	/**
	 * @param 'Function'|'Method' $identifier
	 */
	public function createMissingPureTagError(string $functionDescription, string $identifier, Stmt\Function_|Stmt\ClassMethod $node): IdentifierRuleError
	{
		return RuleErrorBuilder::message(sprintf(
			'%s does not have any side effects and can be marked @phpstan-pure.',
			$functionDescription,
		))
			->identifier(sprintf('pure%s.missing', $identifier))
			->fixNode($node, function (Stmt\Function_|Stmt\ClassMethod $node): Stmt\Function_|Stmt\ClassMethod {
				// a tag appended to a single-line PHPDoc would stay on its line
				$docComment = $node->getDocComment();
				if ($docComment !== null && !str_contains($docComment->getText(), "\n")) {
					$text = trim(substr($docComment->getText(), 3, -2));
					$node->setDocComment(new Doc($text === '' ? '/** */' : sprintf("/**\n * %s\n */", $text)));
				}

				$this->phpDocEditor->edit($node, static function (PhpDocAstNode $phpDocNode): ?PhpDocAstNode {
					if (!$phpDocNode instanceof PhpDocNode) {
						return null;
					}

					$phpDocNode->children[] = new PhpDocTagNode('@phpstan-pure', new GenericTagValueNode(''));

					return $phpDocNode;
				});

				return $node;
			})
			->build();
	}

	/**
	 * @param ImpurePoint[] $impurePoints
	 * @param array<string, true> $pureUnlessCallableParamNames
	 * @return list<IdentifierRuleError>
	 */
	private function reportImpurePoints(array $impurePoints, array $pureUnlessCallableParamNames, string $functionDescription): array
	{
		$errors = [];
		foreach ($impurePoints as $impurePoint) {
			if ($this->isPureUnlessCallableInvocation($impurePoint, $pureUnlessCallableParamNames)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s %s in pure %s.',
				$impurePoint->isCertain() ? 'Impure' : 'Possibly impure',
				$impurePoint->getDescription(),
				lcfirst($functionDescription),
			))
				->line($impurePoint->getNode()->getStartLine())
				->identifier(sprintf(
					'%s.%s',
					$impurePoint->isCertain() ? 'impure' : 'possiblyImpure',
					$impurePoint->getIdentifier(),
				))
				->build();
		}

		return $errors;
	}

	/**
	 * @param array<string, true> $pureUnlessCallableParamNames
	 */
	private function isPureUnlessCallableInvocation(ImpurePoint $impurePoint, array $pureUnlessCallableParamNames): bool
	{
		if ($pureUnlessCallableParamNames === []) {
			return false;
		}

		$node = $impurePoint->getNode();
		if (!$node instanceof FuncCall) {
			return false;
		}
		if (!$node->name instanceof Variable) {
			return false;
		}
		if (!is_string($node->name->name)) {
			return false;
		}

		return array_key_exists($node->name->name, $pureUnlessCallableParamNames);
	}

}
