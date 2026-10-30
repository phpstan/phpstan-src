<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_merge;
use function in_array;
use function sprintf;
use function str_contains;
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
		return [Node\Stmt::class, Node\PropertyHook::class, Node\Expr\Closure::class, Node\Expr\ArrowFunction::class];
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
		// closure PHPDocs are only checked for @phpstan-ignore-var, which has no effect there
		if ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
			return array_merge(
				$this->processMisplacedIgnoreVar($node, $node->getDocComment(), $node instanceof Node\Expr\Closure ? 'above a closure' : 'above an arrow function'),
				$this->processParameters($node),
			);
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
			$placement = self::describeMisplacement($node, $scope);
			foreach ($this->parsePhpDoc($docComment->getText())->getTags() as $phpDocTag) {
				if ($placement !== null && self::isIgnoreVarTag($phpDocTag)) {
					$errors[] = self::createMisplacedIgnoreVarError($node, $phpDocTag, $placement);
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

		if ($node instanceof Node\FunctionLike) {
			foreach ($this->processParameters($node) as $error) {
				$errors[] = $error;
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
	 * @return list<IdentifierRuleError>
	 */
	private function processParameters(Node\FunctionLike $node): array
	{
		$errors = [];
		foreach ($node->getParams() as $param) {
			$placement = $param->isPromoted() ? 'above a promoted property' : 'above a parameter';
			foreach ($this->processMisplacedIgnoreVar($param, $param->getDocComment(), $placement) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processMisplacedIgnoreVar(Node $node, ?Doc $docComment, string $placement): array
	{
		if ($docComment === null || !str_contains($docComment->getText(), '@phpstan-ignore-var')) {
			return [];
		}

		$errors = [];
		foreach ($this->parsePhpDoc($docComment->getText())->getTags() as $phpDocTag) {
			if (!self::isIgnoreVarTag($phpDocTag)) {
				continue;
			}

			$errors[] = self::createMisplacedIgnoreVarError($node, $phpDocTag, $placement);
		}

		return $errors;
	}

	private static function isIgnoreVarTag(PhpDocTagNode $phpDocTag): bool
	{
		return $phpDocTag->name === '@phpstan-ignore-var' || PhpDocNodeResolver::isIgnoreVarInVarTagDescription($phpDocTag);
	}

	/**
	 * Null for statements with inline @var tags, the only place @phpstan-ignore-var has an effect.
	 */
	private static function describeMisplacement(Node $node, Scope $scope): ?string
	{
		if ($node instanceof Node\Stmt\Property) {
			return 'above a property';
		}
		if ($node instanceof Node\PropertyHook) {
			return 'above a property hook';
		}
		if ($node instanceof Node\Stmt\ClassConst) {
			return 'above a class constant';
		}
		if ($node instanceof Node\Stmt\Const_) {
			return 'above a constant';
		}
		if ($node instanceof Node\Stmt\Function_) {
			return 'above a function';
		}
		if ($node instanceof Node\Stmt\ClassMethod) {
			return 'above a method';
		}
		if ($node instanceof Node\Stmt\Interface_) {
			return 'above an interface';
		}
		if ($node instanceof Node\Stmt\Trait_) {
			return 'above a trait';
		}
		if ($node instanceof Node\Stmt\Enum_) {
			return 'above an enum';
		}
		if ($node instanceof Node\Stmt\ClassLike) {
			return 'above a class';
		}
		if ($node instanceof Node\Stmt\EnumCase) {
			return 'above an enum case';
		}
		if ($node instanceof Node\Stmt\TraitUse) {
			return 'above a trait use';
		}
		if ($node instanceof Node\Stmt\Nop && $scope->isInClass() && $scope->getFunction() === null) {
			return 'at the end of a class body';
		}

		return null;
	}

	private static function createMisplacedIgnoreVarError(Node $node, PhpDocTagNode $phpDocTag, string $placement): IdentifierRuleError
	{
		return RuleErrorBuilder::message(sprintf(
			'PHPDoc tag @phpstan-ignore-var %s has no effect.',
			$placement,
		))
			->line(PhpDocLineHelper::detectLine($node, $phpDocTag))
			->identifier('varTag.misplaced')->build();
	}

}
