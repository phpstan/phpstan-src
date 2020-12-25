<?php

namespace MethodCallStatementNoSideEffects;

class Bzz {
	function regular(string $a): string {return $a;}
	/**
	 * @phpstan-pure
	 */
	function pure1(string $a): string {return $a;}
	/**
	 * @psalm-pure
	 */
	function pure2(string $a): string {return $a;}
}
(new Bzz())->regular('test');
(new Bzz())->pure1('test');
(new Bzz())->pure2('test');
