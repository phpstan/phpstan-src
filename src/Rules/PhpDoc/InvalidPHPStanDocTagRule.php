<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function sprintf;
use function strpos;

/**
 * @implements Rule<Node>
 */
class InvalidPHPStanDocTagRule implements Rule
{

	private const POSSIBLE_PHPSTAN_TAGS = [
		'@phpstan-param',
		'@phpstan-param-out',
		'@phpstan-var',
		'@phpstan-extends',
		'@phpstan-implements',
		'@phpstan-use',
		'@phpstan-template',
		'@phpstan-template-contravariant',
		'@phpstan-template-covariant',
		'@phpstan-return',
		'@phpstan-throws',
		'@phpstan-ignore-next-line',
		'@phpstan-ignore-line',
		'@phpstan-method',
		'@phpstan-pure',
		'@phpstan-impure',
		'@phpstan-immutable',
		'@phpstan-type',
		'@phpstan-import-type',
		'@phpstan-property',
		'@phpstan-property-read',
		'@phpstan-property-write',
		'@phpstan-consistent-constructor',
		'@phpstan-assert',
		'@phpstan-assert-if-true',
		'@phpstan-assert-if-false',
		'@phpstan-self-out',
		'@phpstan-this-out',
		'@phpstan-allow-private-mutation',
		'@phpstan-readonly',
		'@phpstan-readonly-allow-private-mutation',
		'@phpstan-consistent-templates',
	];

	public function __construct(
		private Lexer $phpDocLexer,
		private PhpDocParser $phpDocParser,
		private bool $checkAllInvalidPhpDocs,
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
			// mirrored with InvalidPhpDocTagValueRule
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
			if (strpos($phpDocTag->name, '@phpstan-') !== 0
				|| in_array($phpDocTag->name, self::POSSIBLE_PHPSTAN_TAGS, true)
			) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Unknown PHPDoc tag: %s',
				$phpDocTag->name,
			))->build();
		}

		return $errors;
	}

}
