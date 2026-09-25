<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15296;

use function PHPStan\Testing\assertType;

interface Arrayable {}
class Model implements Arrayable {}
final class User extends Model {}

/** @template TModel of Model */
final class Collection
{
	/**
	 * @return ($key is Model ? TModel|null : ($key is (Arrayable|array<mixed>) ? static : TModel|null))
	 */
	public function find(mixed $key): mixed
	{
		return null;
	}
}

/** @param Collection<User> $collection */
function test(Collection $collection, ?User $user): void
{
	assertType('Bug15296\User|null', $collection->find($user));
}

class Variants
{

	/**
	 * @return ($key is not Model ? ($key is Arrayable ? 'arrayable' : 'other') : 'model')
	 */
	public function negated(mixed $key): string
	{
		return 'other';
	}

	/**
	 * @return ($key is Model ? ($key is User ? 'user' : 'model') : 'other')
	 */
	public function inIf(mixed $key): string
	{
		return 'other';
	}

	/**
	 * @return ($key is User ? 'user' : ($key is Model ? 'model' : ($key is null ? 'null' : 'other')))
	 */
	public function deep(mixed $key): string
	{
		return 'other';
	}

}

function testVariants(Variants $v, ?User $user, Model|int $modelOrInt, User|Model|null $any): void
{
	assertType("'model'|'other'", $v->negated($modelOrInt));
	assertType("'model'", $v->negated($user ?? new User()));
	assertType("'other'|'user'", $v->inIf($user));
	assertType("'model'|'other'|'user'", $v->inIf($modelOrInt));
	assertType("'model'|'null'|'user'", $v->deep($any));
}
