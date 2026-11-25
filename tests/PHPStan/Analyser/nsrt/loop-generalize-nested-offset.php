<?php declare(strict_types = 1);

namespace LoopGeneralizeNestedOffset;

use function PHPStan\Testing\assertType;

final class Directive
{

}

final class Block
{

	public function getHash(): string
	{
		return '';
	}

	/** @return list<Directive> */
	public function getPathDirectives(): array
	{
		return [];
	}

}

function topLevel(Block $block, string $hash): void
{
	$byHash = [];
	if (rand(0, 1)) {
		$byHash[$hash] = [
			'block' => $block,
			'pathDirectives' => [],
		];
	} else {
		$byHash[$hash] = [
			'block' => $block,
			'pathDirectives' => [],
		];
	}
	foreach ($block->getPathDirectives() as $directive) {
		assertType('list<LoopGeneralizeNestedOffset\Directive>', $byHash[$hash]['pathDirectives']);
		$byHash[$hash]['pathDirectives'][] = $directive;
	}

	assertType('non-empty-array<string, array{block: LoopGeneralizeNestedOffset\Block, pathDirectives: list<LoopGeneralizeNestedOffset\Directive>}>', $byHash);
}

/**
 * @param array<string, list<Block>> $blocksByDomain
 */
function nested(array $blocksByDomain): void
{
	$byHash = [];
	foreach ($blocksByDomain as $domain => $blocks) {
		foreach ($blocks as $block) {
			$hash = $block->getHash();
			if (!isset($byHash[$hash])) {
				$byHash[$hash] = [
					'block' => $block,
					'pathDirectives' => [],
				];
			}

			foreach ($block->getPathDirectives() as $directive) {
				$byHash[$hash]['pathDirectives'][] = $directive;
			}
		}
	}

	assertType('array<string, array{block: LoopGeneralizeNestedOffset\Block, pathDirectives: list<LoopGeneralizeNestedOffset\Directive>}>', $byHash);
}
