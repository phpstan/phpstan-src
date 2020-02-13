<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class NullCoalesceRule implements \PHPStan\Rules\Rule
{

	/** @var \PHPStan\Rules\Properties\PropertyDescriptor */
	private $propertyDescriptor;

	/** @var \PHPStan\Rules\Properties\PropertyReflectionFinder */
	private $propertyReflectionFinder;

	public function __construct(
		PropertyDescriptor $propertyDescriptor,
		PropertyReflectionFinder $propertyReflectionFinder
	)
	{
		$this->propertyDescriptor = $propertyDescriptor;
		$this->propertyReflectionFinder = $propertyReflectionFinder;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Expr\BinaryOp\Coalesce) {
			$error = $this->canBeCoalesced($node->left, $scope, '??');
		} elseif ($node instanceof Node\Expr\AssignOp\Coalesce) {
			$error = $this->canBeCoalesced($node->var, $scope, '??=');
		} else {
			return [];
		}

		if ($error === null) {
			return [];
		}

		return [$error];
	}

	private function canBeCoalesced(Expr $expr, Scope $scope, string $action, ?RuleError $error = null): ?RuleError
	{
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {

			$hasVariable = $scope->hasVariableType($expr->name);

			if ($hasVariable->no()) {
				return $error ?? RuleErrorBuilder::message(
					sprintf('Variable $%s on left side of %s is never defined.', $expr->name, $action)
				)->build();
			}

			$variableType = $scope->getType($expr);

			if ($hasVariable->maybe()) {
				return null;
			}

			if ($hasVariable->yes()) {
				return $error ?? $this->generateError(
					$variableType,
					sprintf('Variable $%s on left side of %s always exists and', $expr->name, $action)
				);
			}

		} elseif ($expr instanceof Node\Expr\ArrayDimFetch && $expr->dim !== null) {

			$type = $scope->getType($expr->var);
			$dimType = $scope->getType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if (!$type->isOffsetAccessible()->yes()) {
				return $error;
			}

			if ($hasOffsetValue->no()) {
				return $error ?? RuleErrorBuilder::message(
					sprintf(
						'Offset %s on %s on left side of %s does not exist.',
						$dimType->describe(VerbosityLevel::value()),
						$type->describe(VerbosityLevel::value()),
						$action
					)
				)->build();
			}

			if ($hasOffsetValue->maybe()) {
				return null;
			}

			// If offset is cannot be null, store this error message and see if one of the earlier offsets is.
			// E.g. $array['a']['b']['c'] ?? null; is a valid coalesce if a OR b or C might be null.
			if ($hasOffsetValue->yes()) {

				$error = $error ?? $this->generateError($type->getOffsetValueType($dimType), sprintf(
					'Offset %s on %s on left side of %s always exists and',
					$dimType->describe(VerbosityLevel::value()),
					$type->describe(VerbosityLevel::value()),
					$action
				));

				if ($error !== null) {
					return $this->canBeCoalesced($expr->var, $scope, $action, $error);
				}
			}

			// Has offset, it is nullable
			return null;

		} elseif ($expr instanceof Node\Expr\PropertyFetch || $expr instanceof Node\Expr\StaticPropertyFetch) {

			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $scope);

			if ($propertyReflection === null) {
				return null;
			}

			$propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $expr);
			$propertyType = $propertyReflection->getWritableType();

			$error = $error ?? $this->generateError(
				$propertyReflection->getWritableType(),
				sprintf('%s (%s) on left side of %s', $propertyDescription, $propertyType->describe(VerbosityLevel::typeOnly()), $action)
			);

			if ($error !== null) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->canBeCoalesced($expr->var, $scope, $action, $error);
				}

				if ($expr->class instanceof Expr) {
					return $this->canBeCoalesced($expr->class, $scope, $action, $error);
				}
			}

			return $error;
		}

		return $error ?? $this->generateError($scope->getType($expr), sprintf('Left side of %s', $action));
	}

	private function generateError(Type $type, string $message): ?RuleError
	{
		$nullType = new NullType();

		if ($type->equals($nullType)) {
			return RuleErrorBuilder::message(
				sprintf('%s is always null.', $message)
			)->build();
		}

		if ($type->isSuperTypeOf($nullType)->no()) {
			return RuleErrorBuilder::message(
				sprintf('%s is not nullable.', $message)
			)->build();
		}

		return null;
	}

}
