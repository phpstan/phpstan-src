<?php

namespace Bug8127;

use PhpParser\Node\Expr\CallLike;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use function PHPStan\Testing\assertType;

/**
 * @implements Collector<CallLike, array{string, TaintType::TYPE_*, string, int}>
 */
final class SinkCollector implements Collector
{
	public function getNodeType(): string
	{
		return CallLike::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope)
	{}
}

class TaintType
{
	public const TYPE_INPUT = 'input';
	public const TYPE_SQL = 'sql';
	public const TYPE_HTML = 'html';

	public const TYPES = [self::TYPE_INPUT, self::TYPE_SQL, self::TYPE_HTML];
}

/**
 * @implements Rule<CollectedDataNode>
 */
final class TaintRule implements Rule
{
	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		$sinkCollectorData = $node->get(SinkCollector::class);
		assertType("array<string, list<array{string, 'html'|'input'|'sql', string, int}>>", $sinkCollectorData);

		return [];
	}
}
