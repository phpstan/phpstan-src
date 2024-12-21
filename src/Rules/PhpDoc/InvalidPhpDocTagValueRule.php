<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\InvalidTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function str_starts_with;

/**
 * @implements Rule<NodeAbstract>
 */
final class InvalidPhpDocTagValueRule implements Rule
{

	public function __construct(
		private Lexer $phpDocLexer,
		private PhpDocParser $phpDocParser,
	)
	{
	}

	public function getNodeType(): string
	{
		return NodeAbstract::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		// mirrored with InvalidPHPStanDocTagRule
		if ($node instanceof VirtualNode) {
			return [];
		}
		if (!$node instanceof Node\Stmt && !$node instanceof Node\PropertyHook) {
			return [];
		}
		if ($node instanceof Node\Stmt\Expression) {
			if (!$node->expr instanceof Node\Expr\Assign && !$node->expr instanceof Node\Expr\AssignRef) {
				return [];
			}
		}

		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$phpDocString = $docComment->getText();
		$tokens = new TokenIterator($this->phpDocLexer->tokenize($phpDocString));
		$phpDocNode = $this->phpDocParser->parse($tokens);

		$errors = [];
		foreach ($phpDocNode->getTags() as $phpDocTag) {
			if (str_starts_with($phpDocTag->name, '@phan-') || str_starts_with($phpDocTag->name, '@psalm-')) {
				continue;
			}

			if ($phpDocTag->value instanceof TypeAliasTagValueNode) {
				if (!$phpDocTag->value->type instanceof InvalidTypeNode) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag %s %s has invalid value: %s',
					$phpDocTag->name,
					$phpDocTag->value->alias,
					$phpDocTag->value->type->getException()->getMessage(),
				))
					->line(PhpDocLineHelper::detectLine($node, $phpDocTag))
					->identifier('phpDoc.parseError')->build();

				continue;
			} elseif (!($phpDocTag->value instanceof InvalidTagValueNode)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag %s has invalid value (%s): %s',
				$phpDocTag->name,
				$phpDocTag->value->value,
				$phpDocTag->value->exception->getMessage(),
			))
				->line(PhpDocLineHelper::detectLine($node, $phpDocTag))
				->identifier('phpDoc.parseError')->build();
		}

		return $errors;
	}

}
