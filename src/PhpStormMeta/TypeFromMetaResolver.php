<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta;

use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\PhpStormMeta\TypeMapping\CallReturnOverrideCollection;
use PHPStan\PhpStormMeta\TypeMapping\CallReturnTypeOverride;
use PHPStan\PhpStormMeta\TypeMapping\PassedArgumentType;
use PHPStan\PhpStormMeta\TypeMapping\PassedArrayElementType;
use PHPStan\PhpStormMeta\TypeMapping\ReturnTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function explode;
use function trim;

class TypeFromMetaResolver
{

	public function __construct(
		private readonly CallReturnOverrideCollection $overrides,
	)
	{
	}

	public function resolveReferencedType(
		string $fqn,
		Arg ...$args,
	): ?Type
	{
		$override = $this->overrides->getOverrideForCall($fqn);

		if ($override !== null) {
			$arg = $args[$override->argumentOffset] ?? null;
			if ($arg !== null) {
				return $this->resolveTypeFromArgument($override->returnType, $arg);
			}
		}

		return null;
	}

	private function resolveTypeFromArgument(CallReturnTypeOverride $overrideType, Arg $arg): ?Type
	{
		$argValue = $arg->value;
		if ($overrideType instanceof ReturnTypeMap) {
			if ($argValue instanceof String_) {
				$resolvedType = $overrideType->getMappingForArgument($argValue->value);
				return $this->parseResolvedType($resolvedType);
			}
			return null;
		}

		if ($overrideType instanceof PassedArgumentType) {
			// TODO
			return null;
		}

		if ($overrideType instanceof PassedArrayElementType) {
			// TODO
			return null;
		}

		throw new InvalidArgumentException();
	}

	private function parseResolvedType(FullyQualified|string|null $resolvedType): ?Type
	{
		if ($resolvedType === null) {
			return null;
		}

		if ($resolvedType instanceof FullyQualified) {
			return new ObjectType($resolvedType->toString());
		}

		$resolvedType = trim($resolvedType);
		if ($resolvedType === '') {
			return null;
		}

		$unionTypes = explode('|', $resolvedType);
		if (count($unionTypes) === 1) {
			return new ObjectType($resolvedType);
		}

		$resolvedSubtypes = [];
		foreach ($unionTypes as $subtype) {
			$resolvedSubtypes [] = new ObjectType($subtype);
		}

		return TypeCombinator::union(...$resolvedSubtypes);
	}

}
