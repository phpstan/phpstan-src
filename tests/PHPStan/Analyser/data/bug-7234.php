<?php

namespace Bug7234;

use function PHPStan\Testing\assertType;

class Expr {

    /**
     * @template T of string
     * @param T ...$x
     * @return ($x is array<literal-string> ? literal-string&non-empty-string : string)
     */
    public function countDistinct(...$x) {
		return 'COUNT(DISTINCT ' . implode(', ', func_get_args()) . ')';
    }

}

class QueryBuilder {

    /**
     * @return Expr
     */
    public function expr() {
		return new Expr();
    }

}

function (QueryBuilder $qb, $value) {
	assertType('literal-string&non-empty-string', $qb->expr()->countDistinct('A', 'B', 'C'));
	assertType('string', $qb->expr()->countDistinct($value, 'B', 'C'));
	assertType('string', $qb->expr()->countDistinct('A', $value, 'C'));
};
