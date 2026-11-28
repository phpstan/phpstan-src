<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Like phpstan-doctrine's BranchingQueryBuilderType, equals() compares an
 * identity each call of the extension creates anew.
 */
final class Bug15872TrackedBuilderType extends ObjectType
{

	public function __construct(public readonly string $id)
	{
		parent::__construct('Bug15872\Builder');
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self && $type->id === $this->id;
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		return $type instanceof self ? IsSuperTypeOfResult::createFromBoolean($this->equals($type)) : parent::isSuperTypeOf($type);
	}

}
