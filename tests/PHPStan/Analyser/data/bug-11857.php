<?php

namespace Bug11857;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PhpParser\Node\Expr\MethodCall;

use function PHPStan\Testing\assertType;

class RelationDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'belongsTo';
    }

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type {
        $returnType = $methodReflection->getVariants()[0]->getReturnType();
		$argType    = $scope->getType($methodCall->getArgs()[0]->value);
		$modelClass = $argType->getClassStringObjectType()->getObjectClassNames()[0];

        return new GenericObjectType($returnType->getObjectClassNames()[0], [
			new ObjectType($modelClass),
			$scope->getType($methodCall->var),
		]);
    }
}

abstract class Model
{
	/** @return BelongsTo<*, *> */
	public function belongsTo(string $related): BelongsTo
	{
		return new BelongsTo();
	}
}

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 */
class BelongsTo {}

class User extends Model {}

class Post extends Model
{
	/** @return BelongsTo<User, $this> */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	/** @return BelongsTo<User, self> */
	public function userSelf(): BelongsTo
	{
		/** @phpstan-ignore return.type */
		return $this->belongsTo(User::class);
	}
}

class ChildPost extends Post {}

final class Comment extends Model
{
	// This model is final, so either of these
	// two methods would work. It seems that
	// PHPStan is automatically converting the
	// `$this` to a `self` type in the user docblock,
	// but it is not doing so likewise for the `$this`
	// that is returned by the dynamic return extension.

	/** @return BelongsTo<User, $this> */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	/** @return BelongsTo<User, self> */
	public function user2(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}

function test(ChildPost $child): void
{
	assertType('Bug11857\BelongsTo<Bug11857\User, Bug11857\ChildPost>', $child->user());
	// This demonstrates why `$this` is needed in non-final models
	assertType('Bug11857\BelongsTo<Bug11857\User, Bug11857\ChildPost>', $child->userSelf());
}
