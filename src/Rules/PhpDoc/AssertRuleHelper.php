<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function sprintf;
use function substr;

class AssertRuleHelper
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(ExtendedMethodReflection|FunctionReflection $reflection, ParametersAcceptor $acceptor): array
	{
		$parametersByName = [];
		foreach ($acceptor->getParameters() as $parameter) {
			$parametersByName[$parameter->getName()] = $parameter->getType();
		}

		if ($reflection instanceof ExtendedMethodReflection) {
			$class = $reflection->getDeclaringClass();
			$parametersByName['this'] = new ObjectType($class->getName(), null, $class);
		}

		$context = InitializerExprContext::createEmpty();

		$errors = [];
		foreach ($reflection->getAsserts()->getAll() as $assert) {
			$parameterName = substr($assert->getParameter()->getParameterName(), 1);
			if (!array_key_exists($parameterName, $parametersByName)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Assert references unknown parameter $%s.', $parameterName))
					->identifier('parameter.notFound')
					->build();
				continue;
			}

			if (!$assert->isExplicit()) {
				continue;
			}

			$assertedExpr = $assert->getParameter()->getExpr(new TypeExpr($parametersByName[$parameterName]));
			$assertedExprType = $this->initializerExprTypeResolver->getType($assertedExpr, $context);
			if ($assertedExprType instanceof ErrorType) {
				continue;
			}

			$assertedType = $assert->getType();

			$isSuperType = $assertedType->isSuperTypeOf($assertedExprType);
			if ($isSuperType->maybe()) {
				continue;
			}

			$assertedExprString = $assert->getParameter()->describe();

			if ($assert->isNegated() ? $isSuperType->yes() : $isSuperType->no()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Asserted %stype %s for %s with type %s can never happen.',
					$assert->isNegated() ? 'negated ' : '',
					$assertedType->describe(VerbosityLevel::precise()),
					$assertedExprString,
					$assertedExprType->describe(VerbosityLevel::precise()),
				))->identifier('assert.impossibleType')->build();
			} elseif ($assert->isNegated() ? $isSuperType->no() : $isSuperType->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Asserted %stype %s for %s with type %s does not narrow down the type.',
					$assert->isNegated() ? 'negated ' : '',
					$assertedType->describe(VerbosityLevel::precise()),
					$assertedExprString,
					$assertedExprType->describe(VerbosityLevel::precise()),
				))->identifier('assert.alreadyNarrowedType')->build();
			}
		}

		return $errors;
	}

}
