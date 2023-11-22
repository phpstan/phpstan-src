<?php declare(strict_types = 1);

namespace PHPStan\Rules\Types;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function array_merge;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node>
 */
class InvalidTypesInUnionRule implements Rule
{

	private const ONLY_STANDALONE_TYPES = [
		'mixed',
		'never',
		'void',
	];

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Node\FunctionLike && !$node instanceof ClassPropertyNode) {
			return [];
		}

		if ($node instanceof Node\FunctionLike) {
			return $this->processFunctionLikeNode($node);
		}

		return $this->processClassPropertyNode($node);
	}

	/**
	 * @return list<RuleError>
	 */
	private function processFunctionLikeNode(Node\FunctionLike $functionLike): array
	{
		$errors = [];

		foreach ($functionLike->getParams() as $param) {
			if (!$param->type instanceof Node\ComplexType) {
				continue;
			}

			$errors = array_merge($errors, $this->processComplexType($param->type));
		}

		if ($functionLike->getReturnType() instanceof Node\ComplexType) {
			$errors = array_merge($errors, $this->processComplexType($functionLike->getReturnType()));
		}

		return $errors;
	}

	/**
	 * @return list<RuleError>
	 */
	private function processClassPropertyNode(ClassPropertyNode $classPropertyNode): array
	{
		if (!$classPropertyNode->getNativeType() instanceof Node\ComplexType) {
			return [];
		}

		return $this->processComplexType($classPropertyNode->getNativeType());
	}

	/**
	 * @return list<RuleError>
	 */
	private function processComplexType(Node\ComplexType $complexType): array
	{
		if (!$complexType instanceof Node\UnionType && !$complexType instanceof Node\NullableType) {
			return [];
		}

		if ($complexType instanceof Node\UnionType) {
			foreach ($complexType->types as $type) {
				if ($type instanceof Node\Identifier && in_array($type->toString(), self::ONLY_STANDALONE_TYPES, true)) {
					return [
						RuleErrorBuilder::message(sprintf('Type %s cannot be part of a union type declaration.', $type->toString()))
							->line($complexType->getLine())
							->nonIgnorable()
							->build(),
					];
				}
			}

			return [];
		}

		if ($complexType->type instanceof Node\Identifier && in_array($complexType->type->toString(), self::ONLY_STANDALONE_TYPES, true)) {
			return [
				RuleErrorBuilder::message(sprintf('Type %s cannot be part of a nullable type declaration.', $complexType->type->toString()))
					->line($complexType->getLine())
					->nonIgnorable()
					->build(),
			];
		}

		return [];
	}

}
