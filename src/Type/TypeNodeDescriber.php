<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Override;
use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NameNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * Prints a PHPDoc type node for describe(): class names without the leading
 * backslash, as everywhere else in PHPStan's type descriptions.
 */
#[ReferencedByTurboExtension(key: 'typeNodeDescriber')]
final class TypeNodeDescriber
{

	private static ?NodeTraverser $traverser = null;

	private static ?Printer $printer = null;

	public static function describe(TypeNode $typeNode): string
	{
		self::$traverser ??= new NodeTraverser([
			new class () extends AbstractNodeVisitor {

				#[Override]
				public function enterNode(Node $node): ?Node
				{
					if ($node instanceof NameNode) {
						return new IdentifierTypeNode($node->toString());
					}

					return null;
				}

			},
		]);
		self::$printer ??= new Printer();
		[$typeNode] = self::$traverser->traverse([$typeNode]);

		return self::$printer->print($typeNode);
	}

}
