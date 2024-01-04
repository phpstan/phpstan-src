<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use Nette\Utils\Strings;
use PhpParser\Node;
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
 * @implements Rule<Node>
 */
class InvalidPhpDocTagValueRule implements Rule
{

	public function __construct(
		private Lexer $phpDocLexer,
		private PhpDocParser $phpDocParser,
		private bool $checkAllInvalidPhpDocs,
		private bool $invalidPhpDocTagLine,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->checkAllInvalidPhpDocs) {
			if (
				!$node instanceof Node\Stmt\ClassLike
				&& !$node instanceof Node\FunctionLike
				&& !$node instanceof Node\Stmt\Foreach_
				&& !$node instanceof Node\Stmt\Property
				&& !$node instanceof Node\Expr\Assign
				&& !$node instanceof Node\Expr\AssignRef
				&& !$node instanceof Node\Stmt\ClassConst
			) {
				return [];
			}
		} else {
			// mirrored with InvalidPHPStanDocTagRule
			if ($node instanceof VirtualNode) {
				return [];
			}
			if ($node instanceof Node\Stmt\Expression) {
				return [];
			}
			if ($node instanceof Node\Expr && !$node instanceof Node\Expr\Assign && !$node instanceof Node\Expr\AssignRef) {
				return [];
			}
		}

		// todo
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$phpDocString = $docComment->getText();
		$tokens = new TokenIterator($this->phpDocLexer->tokenize($phpDocString));
		$phpDocNode = $this->phpDocParser->parse($tokens);

		$errors = [];
		foreach ($phpDocNode->getTags() as $phpDocTag) {
			if (str_starts_with($phpDocTag->name, '@psalm-')) {
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
					$this->trimExceptionMessage($phpDocTag->value->type->getException()->getMessage()),
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
				$this->trimExceptionMessage($phpDocTag->value->exception->getMessage()),
			))
				->line(PhpDocLineHelper::detectLine($node, $phpDocTag))
				->identifier('phpDoc.parseError')->build();
		}

		return $errors;
	}

	private function trimExceptionMessage(string $message): string
	{
		if ($this->invalidPhpDocTagLine) {
			return $message;
		}

		return Strings::replace($message, '~( on line \d+)$~', '');
	}

}
