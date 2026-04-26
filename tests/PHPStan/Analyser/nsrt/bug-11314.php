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
}

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
	public string $breed;
}

/**
 * @phpstan-import-type Breed from Cat
 */
class Cat3
{
	/**
	 * @var Breed
	 */
	public string $breed;
}

function () {
	$cat = new Cat();
	assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $cat->breed);

	$cat2 = new Cat2();
	assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $cat2->breed);

	$cat3 = new Cat3();
	assertType("'British Shorthair'|'Maine Coon'|'Siamese'", $cat3->breed);
};
