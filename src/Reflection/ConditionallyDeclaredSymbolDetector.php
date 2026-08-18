<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\Parser;
use Throwable;
use function array_key_exists;
use function count;
use function strtolower;

/**
 * Answers whether a symbol is declared inside a conditional block, which is how
 * every polyfill guards its declaration (`function_exists()`, `class_exists()`,
 * `defined()`, a `PHP_VERSION_ID` comparison, ...). Such a declaration is dead
 * code whenever PHP provides the symbol natively, so it must not shadow it.
 *
 * @phpstan-type Symbols array{functions: array<string, true>, classes: array<string, true>, constants: array<string, true>}
 */
#[AutowiredService]
final class ConditionallyDeclaredSymbolDetector
{

	private const FILE_CACHE_LIMIT = 128;

	/** @var array<string, Symbols> */
	private array $cache = [];

	public function __construct(
		#[AutowiredParameter(ref: '@php8Parser')]
		private Parser $parser,
	)
	{
	}

	public function isConditionallyDeclaredFunction(string $fileName, string $functionName): bool
	{
		return array_key_exists(strtolower($functionName), $this->getSymbols($fileName)['functions']);
	}

	public function isConditionallyDeclaredClass(string $fileName, string $className): bool
	{
		return array_key_exists(strtolower($className), $this->getSymbols($fileName)['classes']);
	}

	/** Constant names are case-sensitive, unlike function and class names. */
	public function isConditionallyDeclaredConstant(string $fileName, string $constantName): bool
	{
		return array_key_exists($constantName, $this->getSymbols($fileName)['constants']);
	}

	/**
	 * @return Symbols
	 */
	private function getSymbols(string $fileName): array
	{
		if (array_key_exists($fileName, $this->cache)) {
			return $this->cache[$fileName];
		}

		$symbols = [
			'functions' => [],
			'classes' => [],
			'constants' => [],
		];
		try {
			$this->findInStmts($this->parser->parseFile($fileName), false, $symbols);
		} catch (Throwable) {
			// an unparseable or unreadable file tells us nothing
		}

		if (count($this->cache) >= self::FILE_CACHE_LIMIT) {
			$this->cache = [];
		}

		return $this->cache[$fileName] = $symbols;
	}

	/**
	 * @param Stmt[] $stmts
	 * @param Symbols $symbols
	 */
	private function findInStmts(array $stmts, bool $conditional, array &$symbols): void
	{
		foreach ($stmts as $stmt) {
			if ($stmt instanceof Stmt\Function_) {
				if ($conditional) {
					$symbols['functions'][strtolower((string) ($stmt->namespacedName ?? $stmt->name))] = true;
				}
				continue;
			}

			if ($stmt instanceof Stmt\ClassLike) {
				if ($conditional && $stmt->name !== null) {
					$symbols['classes'][strtolower((string) ($stmt->namespacedName ?? $stmt->name))] = true;
				}
				continue;
			}

			if ($stmt instanceof Stmt\Expression) {
				if ($conditional) {
					$this->findDefineCall($stmt->expr, $symbols);
				}
				continue;
			}

			if (
				$stmt instanceof Stmt\Namespace_
				|| $stmt instanceof Stmt\Declare_
				|| $stmt instanceof Stmt\Block
			) {
				$this->findInStmts($stmt->stmts ?? [], $conditional, $symbols);
				continue;
			}

			if (!$stmt instanceof Stmt\If_) {
				continue;
			}

			$this->findInStmts($stmt->stmts, true, $symbols);
			foreach ($stmt->elseifs as $elseIf) {
				$this->findInStmts($elseIf->stmts, true, $symbols);
			}
			if ($stmt->else === null) {
				continue;
			}

			$this->findInStmts($stmt->else->stmts, true, $symbols);
		}
	}

	/**
	 * @param Symbols $symbols
	 */
	private function findDefineCall(Node\Expr $expr, array &$symbols): void
	{
		if (!$expr instanceof Node\Expr\FuncCall) {
			return;
		}

		if (!$expr->name instanceof Node\Name || $expr->name->toLowerString() !== 'define') {
			return;
		}

		$args = $expr->getArgs();
		if (!isset($args[0]) || !$args[0]->value instanceof Node\Scalar\String_) {
			return;
		}

		$symbols['constants'][$args[0]->value->value] = true;
	}

}
