<?php declare(strict_types = 1);

namespace Bug3589;

class Foo{}
class Bar{}

/**
 * @template Tpl
 */
class Id{}

class FooRepository
{
	/**
	 * @param Id<Foo> $fooId
	 */
	public function load(Id $fooId): Foo
	{
		// ...
		return new Foo;
	}
}

$fooRepository = new FooRepository;

// Expected behavior: no error
/** @var Id<Foo> */
$fooId = new Id;
$fooRepository->load($fooId);

// Expected behavior: error on line 33
/** @var Id<Bar> */
$barId = new Id;
$fooRepository->load($barId);

// Expected behavior: errors
// - line 38 - Template Tpl is not specified
// - line 39 - Parameter #1 fooId of method FooRepository::load() expects Id<Foo>, nonspecified Id given.
$unknownId = new Id;
$fooRepository->load($unknownId);
