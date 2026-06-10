<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDoc\Tag\AssertTag;
use PHPStan\PhpDoc\Tag\AssertTagParameter;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\Generic\TemplateTypeMap;
use function count;

/**
 * Logic shared between CallableType and ClosureType for type predicates
 * written as a conditional return type referencing the callable's own parameter:
 *
 * `callable(mixed $value): ($value is int ? true : false)`
 *
 * The predicate is stored as AssertTag(s) — the same representation
 * as @phpstan-assert-if-true — so that invoking the callable in a condition
 * narrows the argument via TypeSpecifier, and the template types occurring
 * in the asserted type can be inferred from the assertions of a passed callable.
 */
final class CallableAssertionsHelper
{

	/**
	 * Interprets `($parameterName is[ not] $targetType ? $if : $else)` as a type predicate.
	 *
	 * Returns null when the conditional does not encode a predicate (non-bool branches
	 * or branches that allow no conclusion about the parameter).
	 */
	public static function createAssertionsFromConditional(string $parameterName, Type $targetType, bool $negated, Type $if, Type $else): ?Assertions
	{
		if ($negated) {
			[$if, $else] = [$else, $if];
		}

		if (!$if->isBoolean()->yes() || !$else->isBoolean()->yes()) {
			return null;
		}

		$ifBranch = $if->isTrue()->yes() ? 'true' : ($if->isFalse()->yes() ? 'false' : 'bool');
		$elseBranch = $else->isTrue()->yes() ? 'true' : ($else->isFalse()->yes() ? 'false' : 'bool');

		$parameter = new AssertTagParameter($parameterName, null, null);

		switch ($ifBranch . ' : ' . $elseBranch) {
			case 'true : false':
				// truthy => is the target type, falsy => is not the target type
				$tag = new AssertTag(AssertTag::IF_TRUE, $targetType, $parameter, false, false, true);
				break;
			case 'false : true':
				// truthy => is not the target type, falsy => is the target type
				$tag = new AssertTag(AssertTag::IF_TRUE, $targetType, $parameter, true, false, true);
				break;
			case 'bool : false':
				// truthy => is the target type, falsy proves nothing
				$tag = new AssertTag(AssertTag::IF_TRUE, $targetType, $parameter, false, true, true);
				break;
			case 'false : bool':
				// truthy => is not the target type, falsy proves nothing
				$tag = new AssertTag(AssertTag::IF_TRUE, $targetType, $parameter, true, true, true);
				break;
			case 'true : bool':
				// falsy => is not the target type, truthy proves nothing
				$tag = new AssertTag(AssertTag::IF_FALSE, $targetType, $parameter, true, true, true);
				break;
			case 'bool : true':
				// falsy => is the target type, truthy proves nothing
				$tag = new AssertTag(AssertTag::IF_FALSE, $targetType, $parameter, false, true, true);
				break;
			default:
				return null;
		}

		return Assertions::createFromAssertTags([$tag]);
	}

	/**
	 * Inverse of createAssertionsFromConditional() — used to print the predicate
	 * back as a conditional return type in describe() and toPhpDocNode().
	 *
	 * Returns null when the assertions cannot be expressed as a single conditional
	 * return type (multiple tags, property/method assertions, unknown parameter,
	 * non-bool return type).
	 *
	 * @param list<ParameterReflection> $parameters
	 */
	public static function toConditionalReturnTypeNode(Assertions $assertions, array $parameters, Type $returnType): ?ConditionalTypeForParameterNode
	{
		$tags = $assertions->getAll();
		if (count($tags) !== 1) {
			return null;
		}

		$tag = $tags[0];
		$parameterName = $tag->getParameter()->getParameterName();
		if ($tag->getParameter()->describe() !== $parameterName) {
			return null;
		}

		$found = false;
		foreach ($parameters as $parameter) {
			if ('$' . $parameter->getName() === $parameterName) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			return null;
		}

		if (!$returnType->equals(new BooleanType())) {
			return null;
		}

		$trueNode = new IdentifierTypeNode('true');
		$falseNode = new IdentifierTypeNode('false');
		$boolNode = new IdentifierTypeNode('bool');

		if ($tag->getIf() === AssertTag::IF_TRUE) {
			if (!$tag->isEquality()) {
				[$if, $else] = $tag->isNegated() ? [$falseNode, $trueNode] : [$trueNode, $falseNode];
			} else {
				[$if, $else] = $tag->isNegated() ? [$falseNode, $boolNode] : [$boolNode, $falseNode];
			}
		} elseif ($tag->getIf() === AssertTag::IF_FALSE) {
			if (!$tag->isEquality()) {
				[$if, $else] = $tag->isNegated() ? [$trueNode, $falseNode] : [$falseNode, $trueNode];
			} else {
				[$if, $else] = $tag->isNegated() ? [$trueNode, $boolNode] : [$boolNode, $trueNode];
			}
		} else {
			return null;
		}

		return new ConditionalTypeForParameterNode(
			$parameterName,
			$tag->getType()->toPhpDocNode(),
			$if,
			$else,
			false,
		);
	}

	/**
	 * Adds the predicate encoded in the variant's conditional return type
	 * (like `@return ($value is int ? true : false)` on is_int()) to the given
	 * assertions, unless an equal assertion is already present.
	 */
	public static function withConditionalReturnPredicate(Assertions $assertions, ParametersAcceptor $variant): Assertions
	{
		$returnType = $variant->getReturnType();
		if (!$returnType instanceof ConditionalTypeForParameter) {
			return $assertions;
		}

		foreach ($variant->getParameters() as $parameter) {
			if ('$' . $parameter->getName() !== $returnType->getParameterName()) {
				continue;
			}

			$predicateAssertions = self::createAssertionsFromConditional(
				$returnType->getParameterName(),
				$returnType->getTarget(),
				$returnType->isNegated(),
				$returnType->getIf(),
				$returnType->getElse(),
			);
			if ($predicateAssertions === null) {
				return $assertions;
			}

			foreach ($assertions->getAll() as $tag) {
				foreach ($predicateAssertions->getAll() as $predicateTag) {
					if (self::assertTagsEqual($tag, $predicateTag)) {
						return $assertions;
					}
				}
			}

			return $assertions->union($predicateAssertions);
		}

		return $assertions;
	}

	public static function assertionsEqual(Assertions $assertions, Assertions $otherAssertions): bool
	{
		$tags = $assertions->getAll();
		$otherTags = $otherAssertions->getAll();
		if (count($tags) !== count($otherTags)) {
			return false;
		}

		foreach ($tags as $i => $tag) {
			if (!self::assertTagsEqual($tag, $otherTags[$i])) {
				return false;
			}
		}

		return true;
	}

	private static function assertTagsEqual(AssertTag $tag, AssertTag $otherTag): bool
	{
		return $tag->getParameter()->describe() === $otherTag->getParameter()->describe()
			&& $tag->getIf() === $otherTag->getIf()
			&& $tag->isNegated() === $otherTag->isNegated()
			&& $tag->isEquality() === $otherTag->isEquality()
			&& $tag->getType()->equals($otherTag->getType());
	}

	/**
	 * Infers template types occurring in the assertions of the declared callable
	 * from the assertions of the passed callable, matching the asserted parameters
	 * by position.
	 */
	public static function inferTemplateTypesOnAsserts(CallableParametersAcceptor $declared, ParametersAcceptor $received): TemplateTypeMap
	{
		$typeMap = TemplateTypeMap::createEmpty();
		if ($declared->getAsserts()->getAll() === []) {
			return $typeMap;
		}
		if (!$received instanceof CallableParametersAcceptor) {
			return $typeMap;
		}

		$declaredAssertions = $declared->getAsserts();
		$receivedAssertions = $received->getAsserts();
		if ($receivedAssertions->getAll() === []) {
			return $typeMap;
		}

		$declaredParameterIndexes = self::getParameterIndexes($declared);
		$receivedParameterIndexes = self::getParameterIndexes($received);

		foreach ([
			[$declaredAssertions->getAssertsIfTrue(), $receivedAssertions->getAssertsIfTrue()],
			[$declaredAssertions->getAssertsIfFalse(), $receivedAssertions->getAssertsIfFalse()],
		] as [$declaredTags, $receivedTags]) {
			foreach ($declaredTags as $declaredTag) {
				$declaredIndex = $declaredParameterIndexes[$declaredTag->getParameter()->describe()] ?? null;
				if ($declaredIndex === null) {
					continue;
				}

				foreach ($receivedTags as $receivedTag) {
					if ($receivedTag->isNegated() !== $declaredTag->isNegated()) {
						continue;
					}
					$receivedIndex = $receivedParameterIndexes[$receivedTag->getParameter()->describe()] ?? null;
					if ($receivedIndex !== $declaredIndex) {
						continue;
					}

					$typeMap = $typeMap->union($declaredTag->getType()->inferTemplateTypes($receivedTag->getType()));
				}
			}
		}

		return $typeMap;
	}

	/**
	 * @return array<string, int>
	 */
	private static function getParameterIndexes(ParametersAcceptor $acceptor): array
	{
		$indexes = [];
		foreach ($acceptor->getParameters() as $i => $parameter) {
			$indexes['$' . $parameter->getName()] = $i;
		}

		return $indexes;
	}

}
