<?php declare(strict_types = 1);

namespace Bug7740;

use function PHPStan\Testing\assertType;

interface Quacks {

}

class Duck implements Quacks {

}

class Cat {

}

/**
 * @param object $animal
 * @return ($animal is Quacks ? true : false)
 */
function quacks(object $animal): bool {

}

assertType('true', quacks(new Duck()));
assertType('false', quacks(new Cat()));
