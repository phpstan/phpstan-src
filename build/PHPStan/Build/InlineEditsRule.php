<?php declare(strict_types = 1);

namespace PHPStan\Build;

use JsonException;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use function count;
use function file_put_contents;
use function fwrite;
use function getenv;
use function json_encode;
use function sprintf;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const STDERR;

/**
 * Writes the edits gathered by InlineCallCollector to INLINE_EDITS_OUT.
 *
 * @implements Rule<CollectedDataNode>
 */
final class InlineEditsRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	/**
	 * @throws JsonException
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$edits = [];
		foreach ($node->get(InlineCallCollector::class) as $items) {
			foreach ($items as $item) {
				$edits[] = $item;
			}
		}
		$out = getenv('INLINE_EDITS_OUT');
		if ($out !== false) {
			file_put_contents($out, json_encode($edits, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
		}
		fwrite(STDERR, sprintf('inline edits collected: %d%s', count($edits), PHP_EOL));

		return [];
	}

}
