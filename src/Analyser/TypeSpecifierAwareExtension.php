<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/** @api */
interface TypeSpecifierAwareExtension
{

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void;

}
