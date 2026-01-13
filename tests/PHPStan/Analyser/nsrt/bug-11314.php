<?php declare(strict_types = 1);

namespace Bug11314;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type Breed 'Siamese'|'British Shorthair'|'Maine Coon'
 */
class Cat
{
	/**
	 * @var Breed
	 */
	public string $breed;

	public function doFoo(): void
	{
		assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $this->breed);
	}
}

$cat = new Cat();
assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $cat->breed);

/**
 * @phpstan-import-type Breed from Cat
 *
 * @template T of Breed
 */
class Cat2
{
	/**
	 * @var Breed
	 */
	public string $breed; // Should be of type Breed, but "@template T of Breed" removes the type

	public function doFoo(): void
	{
		assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $this->breed);
	}
}

$cat2 = new Cat2();
assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $cat2->breed);

/**
 * @phpstan-import-type Breed from Cat
 */
class Cat3
{
	/**
	 * @var Breed
	 */
	public string $breed; // Here it works without the "@template"

	public function doFoo(): void
	{
		assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $this->breed);
	}
}

$cat3 = new Cat3();
assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $cat3->breed);

/**
 * @phpstan-type Breed 'Siamese'|'British Shorthair'|'Maine Coon'
 *
 * @template T of Breed
 */
class Cat4
{
	/**
	 * @var Breed
	 */
	public string $breed; // Should be of type Breed, but "@template T of Breed" removes the type

	public function doFoo(): void
	{
		assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $this->breed);
	}
}

$cat4 = new Cat4();
assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $cat4->breed);

/**
 * @phpstan-type Breed 'Siamese'|'British Shorthair'|'Maine Coon'
 *
 * @template T of Breed
 */
class Cat5
{
	/**
	 * @var T
	 */
	public string $breed; // Should be of type Breed, but "@template T of Breed" removes the type

	public function doFoo(): void
	{
		assertType("T of 'British Shorthair'|'Maine Coon'|'Siamese' (class Bug11314\Cat5, argument)", $this->breed);
	}
}

$cat5 = new Cat5();
assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $cat5->breed);
