<?php declare(strict_types = 1);

namespace Bug11314Generics;

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
