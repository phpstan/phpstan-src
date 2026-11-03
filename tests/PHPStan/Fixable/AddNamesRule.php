<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Fixable\PhpDoc\PhpDocEditor;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\FullyQualifiedNameNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function preg_match_all;
use function sprintf;

/**
 * Turns each `@add-name \Some\Class` tag into a native parameter type
 * and a `@param` tag, to show new names are written the same way
 * in PHP code and in PHPDocs.
 *
 * @implements Rule<Node\Stmt\ClassMethod>
 */
final class AddNamesRule implements Rule
{

	public function __construct(private PhpDocEditor $phpDocEditor)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassMethod::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null || preg_match_all('/@add-name\s+\\\\?(\S+)/', $docComment->getText(), $matches) === 0) {
			return [];
		}

		$names = $matches[1];

		return [
			RuleErrorBuilder::message(sprintf('Adding %d names.', count($names)))
				->identifier('tests.addNames')
				->fixNode($node, function (Node\Stmt\ClassMethod $method) use ($names) {
					$i = 0;
					$this->phpDocEditor->edit($method, static function (\PHPStan\PhpDocParser\Ast\Node $phpDocNode) use (&$i) {
						if (!$phpDocNode instanceof PhpDocTagNode || $phpDocNode->name !== '@add-name') {
							return null;
						}

						$i++;

						return new PhpDocTagNode('@param', new ParamTagValueNode(
							new FullyQualifiedNameNode((string) $phpDocNode->value),
							false,
							'$p' . $i,
							'',
							false,
						));
					});

					foreach ($names as $j => $name) {
						$method->params[] = new Node\Param(
							new Node\Expr\Variable('p' . ($j + 1)),
							type: new FullyQualified($name),
						);
					}

					return $method;
				})
				->build(),
		];
	}

}
