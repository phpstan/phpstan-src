<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\ExtensionInterface;

/**
 * A service holding per-file analysis state keyed by AST nodes. The parser
 * cache retains ASTs across files, so node-keyed caches never release their
 * entries on their own - NodeScopeResolver resets these services at the start
 * of each file's analysis, releasing the previous file's captured results and
 * everything they transitively hold (callbacks, scopes).
 */
#[ExtensionInterface(tag: self::TAG)]
interface PerFileAnalysisResettable
{

	public const TAG = 'phpstan.perFileAnalysisResettable';

	public function resetFileAnalysisState(): void;

}
