<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;

/** @api */
interface TypeNodeResolverExtension
{

	public const EXTENSION_TAG = 'phpstan.phpDoc.typeNodeResolverExtension';

	public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type;

}
