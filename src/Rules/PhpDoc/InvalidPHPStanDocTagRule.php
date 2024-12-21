<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * @implements Rule<NodeAbstract>
 */
final class InvalidPHPStanDocTagRule implements Rule
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
		'@phpstan-ignore',
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
		'@phpstan-require-extends',
		'@phpstan-require-implements',
		'@phpstan-param-immediately-invoked-callable',
		'@phpstan-param-later-invoked-callable',
		'@phpstan-param-closure-this',
	];

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
		// mirrored with InvalidPhpDocTagValueRule
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
			if (!str_starts_with($phpDocTag->name, '@phpstan-')
				|| in_array($phpDocTag->name, self::POSSIBLE_PHPSTAN_TAGS, true)
			) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Unknown PHPDoc tag: %s',
				$phpDocTag->name,
			))
				->line(PhpDocLineHelper::detectLine($node, $phpDocTag))
				->identifier('phpDoc.phpstanTag')->build();
		}

		return $errors;
	}

}
