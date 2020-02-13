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
class CoalesceRule implements \PHPStan\Rules\Rule
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
			$error = $this->canBeCoalesced($node->left, $scope, 'Coalesce');
		} elseif ($node instanceof Node\Expr\AssignOp\Coalesce) {
			$error = $this->canBeCoalesced($node->var, $scope, 'Null-coalescing assignment');
		} else {
			return [];
		}

		if ($error === null) {
			return [];
		}

		return [$error];
	}

	private function canBeCoalesced(Node $node, Scope $scope, string $action, ?RuleError $error = null): ?RuleError
	{
		if ($node instanceof \PhpParser\Node\Expr\FuncCall ||
			$node instanceof \PhpParser\Node\Expr\MethodCall ||
			$node instanceof \PhpParser\Node\Expr\StaticCall) {

			if ($node->name instanceof Expr) {
				return null;
			}

			$resultingType = $scope->getType($node);

			$error = $this->generateError(
				$resultingType,
				$node,
				sprintf('%s of return value for call to \'%s\'', $action, $node->name->toString())
			);

		} elseif ($node instanceof Node\Expr\Variable && is_string($node->name)) {

			$hasVariable = $scope->hasVariableType($node->name);

			if ($hasVariable->no()) {
				return $error ?? RuleErrorBuilder::message(
					sprintf('%s of undefined variable $%s.', $action, $node->name)
				)->line($node->getLine())->build();
			}

			$variableType = $scope->getVariableType($node->name);

			if ($hasVariable->maybe()) {
				return null;
			}

			if ($hasVariable->yes()) {
				return $error ?? $this->generateError(
					$variableType,
					$node,
					sprintf('%s of variable $%s', $action, $node->name)
				);
			}

		} elseif ($node instanceof Node\Expr\ArrayDimFetch && $node->dim !== null) {

			$type = $scope->getType($node->var);
			$dimType = $scope->getType($node->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);

			if ($hasOffsetValue->no() || $type->isOffsetAccessible()->no()) {
				return $error ?? RuleErrorBuilder::message(
					sprintf(
						'%s of invalid offset %s on %s.',
						$action,
						$dimType->describe(VerbosityLevel::value()),
						$type->describe(VerbosityLevel::value())
					)
				)->line($node->getLine())->build();
			}

			if ($hasOffsetValue->maybe()) {
				return null;
			}

			// If offset is cannot be null, store this error message and see if one of the earlier offsets is.
			// E.g. $array['a']['b']['c'] ?? null; is a valid coalesce if a OR b or C might be null.
			if ($hasOffsetValue->yes()) {

				$error = $error ?? $this->generateError($type->getOffsetValueType($dimType), $node, sprintf(
					'%s of offset %s on %s',
					$action,
					$dimType->describe(VerbosityLevel::value()),
					$type->describe(VerbosityLevel::value())
				));

				if ($error !== null) {
					return $this->canBeCoalesced($node->var, $scope, $action, $error);
				}
			}

			// Has offset, it is nullable
			return null;

		} elseif ($node instanceof Node\Expr\PropertyFetch || $node instanceof Node\Expr\StaticPropertyFetch) {

			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($node, $scope);

			if ($propertyReflection === null) {
				return null;
			}

			$propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $node);
			$propertyType = $propertyReflection->getWritableType();

			$error = $error ?? $this->generateError(
				$propertyReflection->getWritableType(),
				$node,
				sprintf('%s of %s (%s)', $action, $propertyDescription, $propertyType->describe(VerbosityLevel::typeOnly()))
			);

			if ($error !== null && property_exists($node, 'var')) {
				return $this->canBeCoalesced($node->var, $scope, $action, $error);
			}

		}

		return $error;
	}

	private function generateError(Type $type, Node $node, string $message): ?RuleError
	{
		$nullType = new NullType();

		if ($type->equals($nullType)) {
			return $this->generateAlwaysNullError($node, $message);
		}

		if ($type->isSuperTypeOf($nullType)->no()) {
			return $this->generateNeverNullError($node, $message);
		}

		return null;
	}

	private function generateAlwaysNullError(Node $node, string $message): RuleError
	{
		return RuleErrorBuilder::message(
			sprintf('%s, which is always null.', $message)
		)->line($node->getLine())->build();
	}

	private function generateNeverNullError(Node $node, string $message): RuleError
	{
		return RuleErrorBuilder::message(
			sprintf('%s, which cannot be null.', $message)
		)->line($node->getLine())->build();
	}

}
