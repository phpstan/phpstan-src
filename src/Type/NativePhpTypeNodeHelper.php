<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PHPStan\Turbo\ReferencedByTurboExtension;
use function array_map;
use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function sort;
use function strtolower;

/**
 * The compound halves of Type::toNativePhpTypeNode(), shared by the PHP
 * implementations and the turbo extension.
 */
#[ReferencedByTurboExtension(key: 'nativePhpTypeNodeHelper')]
final class NativePhpTypeNodeHelper
{

	/** @return Identifier|Name|ComplexType|null */
	public static function union(UnionType $unionType, NativeTypePosition $position)
	{
		$nonNullTypes = [];
		$hasNull = false;
		foreach ($unionType->getTypes() as $member) {
			if ($member->isNull()->yes()) {
				$hasNull = true;
				continue;
			}

			$nonNullTypes[] = $member;
		}

		// prefer ?Foo over Foo|null
		if ($hasNull && count($nonNullTypes) === 1 && $position->isNullableAllowed()) {
			$innerNode = $nonNullTypes[0]->toNativePhpTypeNode($position->toNullableInner());
			if ($innerNode instanceof Identifier || $innerNode instanceof Name) {
				return new NullableType($innerNode);
			}
		}

		$memberPosition = $position->toUnionMember();
		$nodes = [];
		foreach ($nonNullTypes as $type) {
			$node = $type->toNativePhpTypeNode($memberPosition);
			if ($node === null) {
				return null;
			}

			foreach ($node instanceof Node\UnionType ? $node->types : [$node] as $memberNode) {
				if ($memberNode instanceof Node\IntersectionType) {
					$names = array_map(static fn (Node $name): string => strtolower($name->toString()), $memberNode->types);
					sort($names);
					$nodes[implode('&', $names)] = $memberNode;
					continue;
				}

				if (!$memberNode instanceof Identifier && !$memberNode instanceof Name) {
					return null;
				}

				$nodes[strtolower($memberNode->toString())] = $memberNode;
			}
		}

		// PHP rejects redundant members, e.g. Foo|object, static|object, array|iterable, or A|(A&B)
		foreach ($nodes as $key => $node) {
			if (isset($nodes['object']) && ($node instanceof Name || $node instanceof Node\IntersectionType)) {
				unset($nodes[$key]);
				continue;
			}

			if ($key === 'array' && isset($nodes['iterable'])) {
				unset($nodes[$key]);
				continue;
			}

			if (!$node instanceof Node\IntersectionType) {
				continue;
			}

			foreach (explode('&', $key) as $part) {
				if (isset($nodes[$part])) {
					unset($nodes[$key]);
					continue 2;
				}
			}
		}

		$nodes = array_values($nodes);
		if (count($nodes) === 1 && ($nodes[0] instanceof Identifier || $nodes[0] instanceof Name)) {
			if (!$hasNull) {
				return $nodes[0];
			}

			if ($position->isNullableAllowed() && !in_array($nodes[0]->toLowerString(), ['false', 'true'], true)) {
				return new NullableType($nodes[0]);
			}
		}

		if (!$position->isUnionAllowed()) {
			return null;
		}

		if ($hasNull) {
			if (!$memberPosition->isNullAllowed()) {
				return null;
			}

			$nodes[] = new Identifier('null');
		}

		return new Node\UnionType($nodes);
	}

	/** @return Identifier|Name|ComplexType|null */
	public static function intersection(IntersectionType $intersectionType, NativeTypePosition $position)
	{
		if ($intersectionType->isString()->yes()) {
			return (new StringType())->toNativePhpTypeNode($position);
		}

		if ($intersectionType->isArray()->yes()) {
			return (new ArrayType(new MixedType(), new MixedType()))->toNativePhpTypeNode($position);
		}

		// only class names can be intersected, other members like HasMethodType are dropped
		$memberPosition = $position->toIntersectionMember();
		$names = [];
		foreach ($intersectionType->getTypes() as $member) {
			$node = $member->toNativePhpTypeNode($memberPosition);
			if (!$node instanceof Name) {
				continue;
			}

			$names[$node->toLowerString()] = $node;
		}

		if ($names === []) {
			if ($intersectionType->isObject()->yes()) {
				return (new ObjectWithoutClassType())->toNativePhpTypeNode($position);
			}

			return null;
		}

		if (count($names) === 1) {
			return array_values($names)[0];
		}

		if (!$position->isIntersectionAllowed()) {
			return null;
		}

		return new Node\IntersectionType(array_values($names));
	}

}
