<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * @implements MultipleNodeTypesRule<NodeAbstract>
 */
#[RegisteredRule(level: 2)]
#[ValidatesStubFiles]
final class InvalidPHPStanDocTagRule implements MultipleNodeTypesRule
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
		'@phpstan-sealed',
		'@phpstan-param-immediately-invoked-callable',
		'@phpstan-param-later-invoked-callable',
		'@phpstan-param-closure-this',
		'@phpstan-pure-unless-callable-is-impure',
		'@phpstan-pure-unless-parameter-passed',
		'@phpstan-all-methods-pure',
		'@phpstan-all-methods-impure',
		'@phpstan-ignore-var',
	];

	public function __construct(
		private Lexer $phpDocLexer,
		private PhpDocParser $phpDocParser,
	)
	{
	}

	public function getNodeTypes(): array
	{
		return [Node\Stmt::class, Node\PropertyHook::class];
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
			if (
				!$node->expr instanceof Node\Expr\Assign
				&& !$node->expr instanceof Node\Expr\AssignRef
				&& !$node->expr instanceof Node\Expr\AssignOp
			) {
				return [];
			}
		}

		$errors = [];
		$docComment = $node->getDocComment();
		if ($docComment !== null) {
			$declaration = self::describeDeclaration($node);
			foreach ($this->parsePhpDoc($docComment->getText())->getTags() as $phpDocTag) {
				if ($phpDocTag->name === '@phpstan-ignore-var' && $declaration !== null) {
					$errors[] = self::createMisplacedIgnoreVarError($node, $phpDocTag, $declaration);
					continue;
				}

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
		}

		if ($node instanceof Node\Stmt\ClassMethod) {
			foreach ($node->params as $param) {
				$paramDocComment = $param->getDocComment();
				if (!$param->isPromoted() || $paramDocComment === null) {
					continue;
				}

				foreach ($this->parsePhpDoc($paramDocComment->getText())->getTagsByName('@phpstan-ignore-var') as $phpDocTag) {
					$errors[] = self::createMisplacedIgnoreVarError($param, $phpDocTag, 'a promoted property');
				}
			}
		}

		return $errors;
	}

	private function parsePhpDoc(string $phpDocString): PhpDocNode
	{
		$tokens = new TokenIterator($this->phpDocLexer->tokenize($phpDocString));

		return $this->phpDocParser->parse($tokens);
	}

	/**
	 * Null for statements with inline @var tags, the only place @phpstan-ignore-var has an effect.
	 */
	private static function describeDeclaration(Node $node): ?string
	{
		if ($node instanceof Node\Stmt\Property) {
			return 'a property';
		}
		if ($node instanceof Node\PropertyHook) {
			return 'a property hook';
		}
		if ($node instanceof Node\Stmt\ClassConst) {
			return 'a class constant';
		}
		if ($node instanceof Node\Stmt\Const_) {
			return 'a constant';
		}
		if ($node instanceof Node\Stmt\Function_) {
			return 'a function';
		}
		if ($node instanceof Node\Stmt\ClassMethod) {
			return 'a method';
		}
		if ($node instanceof Node\Stmt\Interface_) {
			return 'an interface';
		}
		if ($node instanceof Node\Stmt\Trait_) {
			return 'a trait';
		}
		if ($node instanceof Node\Stmt\Enum_) {
			return 'an enum';
		}
		if ($node instanceof Node\Stmt\ClassLike) {
			return 'a class';
		}

		return null;
	}

	private static function createMisplacedIgnoreVarError(Node $node, PhpDocTagNode $phpDocTag, string $declaration): IdentifierRuleError
	{
		return RuleErrorBuilder::message(sprintf(
			'PHPDoc tag @phpstan-ignore-var above %s has no effect.',
			$declaration,
		))
			->line(PhpDocLineHelper::detectLine($node, $phpDocTag))
			->identifier('varTag.misplaced')->build();
	}

}
