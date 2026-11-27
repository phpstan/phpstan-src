<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\NarrowedSubjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function str_ends_with;
use function strlen;
use function substr;

/**
 * Gives the templates of the callable a closure is passed to a name of the
 * closure's own while ClosureTypeResolver::getClosureType() walks the body.
 *
 * The walk of a closure argument sees the callee's unresolved templates (the
 * `T` of `callable(T): U`), and the callee resolves them in the closure's
 * return type afterwards. Templates are resolved by name, so a generic call
 * in the body - to the same callee or any other function with a `T` - would
 * resolve the outer `T` as its own: `map(['a'], fn ($a) => pair(2, $a))`
 * returned `array{2, 2}`. A renamed template is no template of any call in
 * the body, and the name is the same for every walk of the closure, so
 * walks in the same context still share their cached type.
 */
#[ReferencedByTurboExtension(key: 'closureWalkTemplateTypes')]
final class ClosureWalkTemplateTypes
{

	public static function rename(Type $type, string $suffix): Type
	{
		if (!$type->hasTemplateOrLateResolvableType()) {
			return $type;
		}

		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($suffix): Type {
			$type = $traverse($type);
			if (!self::isCalleeTemplate($type)) {
				return $type;
			}

			return TemplateTypeFactory::create($type->getScope(), $type->getName() . $suffix, $type->getBound(), $type->getVariance(), $type->getStrategy(), $type->getDefault());
		});
	}

	public static function restore(ClosureType $type, string $suffix): ClosureType
	{
		if (!$type->hasTemplateOrLateResolvableType()) {
			return $type;
		}

		$restored = TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($suffix): Type {
			$type = $traverse($type);
			if (!self::isCalleeTemplate($type) || !str_ends_with($type->getName(), $suffix)) {
				return $type;
			}

			$name = substr($type->getName(), 0, -strlen($suffix));
			if ($name === '') {
				return $type;
			}

			return TemplateTypeFactory::create($type->getScope(), $name, $type->getBound(), $type->getVariance(), $type->getStrategy(), $type->getDefault());
		});
		if (!$restored instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		return $restored;
	}

	/** @phpstan-assert-if-true TemplateType $type */
	private static function isCalleeTemplate(Type $type): bool
	{
		return $type instanceof TemplateType
			&& !$type instanceof NarrowedSubjectType
			&& !$type->isArgument()
			&& $type->getScope()->getFunctionName() !== null;
	}

}
