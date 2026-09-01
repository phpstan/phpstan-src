<?php // lint >= 8.0

namespace TemplateDefaultReferringOtherNested;

use function PHPStan\Testing\assertType;

class Entity
{

}

class User extends Entity
{

}

class Post extends Entity
{

}

/**
 * @template TRelated of Entity
 * @template TDeclaring of Entity
 * @template TBare = TRelated
 * @template TResult = TRelated|null
 * @template TList of array = list<TRelated>
 * @template TShape of array = array{related: TRelated, declaring: TDeclaring}
 */
interface Relation
{

	/** @return TBare */
	public function bare(): mixed;

	/** @return TResult */
	public function getResults(): mixed;

	/** @return TList */
	public function all(): array;

	/** @return TShape */
	public function shape(): array;

}

/**
 * @param Relation<User, Post> $defaulted
 * @param Relation<User, Post, User, User> $explicit
 */
function test(Relation $defaulted, Relation $explicit): void
{
	assertType('TemplateDefaultReferringOtherNested\User', $defaulted->bare());
	assertType('TemplateDefaultReferringOtherNested\User|null', $defaulted->getResults());
	assertType('list<TemplateDefaultReferringOtherNested\User>', $defaulted->all());
	assertType('array{related: TemplateDefaultReferringOtherNested\User, declaring: TemplateDefaultReferringOtherNested\Post}', $defaulted->shape());

	assertType('TemplateDefaultReferringOtherNested\User', $explicit->bare());
	assertType('TemplateDefaultReferringOtherNested\User', $explicit->getResults());
}
