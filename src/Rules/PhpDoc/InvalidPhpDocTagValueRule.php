<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node>
 */
class InvalidPhpDocTagValueRule implements \PHPStan\Rules\Rule
{

	private const POSSIBLE_PHPSTAN_TAGS = [
		'@phpstan-param',
		'@phpstan-var',
		'@phpstan-template',
		'@phpstan-extends',
		'@phpstan-implements',
		'@phpstan-use',
		'@phpstan-template',
		'@phpstan-template-covariant',
		'@phpstan-return',
	];

	/** @var Lexer */
	private $phpDocLexer;

	/** @var PhpDocParser */
	private $phpDocParser;

	public function __construct(Lexer $phpDocLexer, PhpDocParser $phpDocParser)
	{
		$this->phpDocLexer = $phpDocLexer;
		$this->phpDocParser = $phpDocParser;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Stmt\ClassLike
			&& !$node instanceof Node\FunctionLike
			&& !$node instanceof Node\Stmt\Foreach_
			&& !$node instanceof Node\Stmt\Property
			&& !$node instanceof Node\Expr\Assign
			&& !$node instanceof Node\Expr\AssignRef
		) {
			return [];
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
			if (strpos($phpDocTag->name, '@phpstan-') === 0
				&& !in_array($phpDocTag->name, self::POSSIBLE_PHPSTAN_TAGS, true)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Encountered unknown tag that had the phpstan prefix: %s',
					$phpDocTag->name
				))->build();
			}
			if (!($phpDocTag->value instanceof InvalidTagValueNode)) {
				continue;
			}

			if (strpos($phpDocTag->name, '@psalm-') === 0) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag %s has invalid value (%s): %s',
				$phpDocTag->name,
				$phpDocTag->value->value,
				$phpDocTag->value->exception->getMessage()
			))->build();
		}

		return $errors;
	}

}
