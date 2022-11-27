<?php declare(strict_types=1);

namespace PHPStan\Type\Test\C;

/** @template T */
interface Invariant {
}

/** @template-covariant T */
interface Covariant {
}

/** @template-contravariant T */
interface Contravariant {
}
