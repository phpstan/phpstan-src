<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

/** @api */
interface TypeNodeResolverAwareExtension
{

	public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void;

}
