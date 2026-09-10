<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use function file_get_contents;
use function sprintf;
use function substr;
use function substr_count;

/**
 * @extends RuleTestCase<Rule<CollectedDataNode>>
 */
class InlineCallCollectorTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new /** @implements Rule<CollectedDataNode> */ class implements Rule {

			public function getNodeType(): string
			{
				return CollectedDataNode::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				$errors = [];
				foreach ($node->get(InlineCallCollector::class) as $file => $edits) {
					$contents = (string) file_get_contents($file);
					foreach ($edits as $edit) {
						$errors[] = RuleErrorBuilder::message(sprintf('%s => %s', $edit['callee'], $edit['replacement']))
							->identifier('test.inline')
							->file($file)
							->line(substr_count(substr($contents, 0, $edit['start']), "\n") + 1)
							->build();
					}
				}

				return $errors;
			}

		};
	}

	protected function getCollectors(): array
	{
		return [
			new InlineCallCollector(
				self::getContainer()->getService('defaultAnalysisParser'),
				self::createReflectionProvider(),
				[__DIR__ . '/data/inline-call-collector'],
			),
		];
	}

	public function testInlinesOnlyWhatNoSubclassCanOverride(): void
	{
		$this->analyse([__DIR__ . '/data/inline-call-collector/world.php'], [
			// FinalGetter::getValue (final class), ClosedWorldGetter::getValue
			// (nothing in the world overrides it) and getFinalValue (final method),
			// FinalApiGetter::getValue (@api but final), ReadsOthers::getClosedWorld
			// (private) are inlined; ClosedWorldGetter::getOverriddenValue
			// (OverridingGetter overrides it), AbstractHooks::getHooks (abstract
			// class: any subclass may override it) and ApiGetter::getValue and
			// describeAdditionalCacheKey (@api class: third parties may extend
			// it) are not; AbstractHooks::getSecret is private, so it is.
			[
				'InlineCallCollectorTest\AbstractHooks::getSecret => \'secret\'',
				75,
			],
			[
				'InlineCallCollectorTest\FinalGetter::getValue => $finalGetter->value',
				145,
			],
			[
				'InlineCallCollectorTest\ClosedWorldGetter::getValue => $closedWorldGetter->value',
				146,
			],
			[
				'InlineCallCollectorTest\ClosedWorldGetter::getFinalValue => $closedWorldGetter->value',
				148,
			],
			[
				'InlineCallCollectorTest\FinalApiGetter::getValue => $finalApiGetter->value',
				150,
			],
			[
				'InlineCallCollectorTest\ClosedWorldGetter::getValue => $this->getClosedWorld()->value',
				151,
			],
			[
				'InlineCallCollectorTest\ReadsOthers::getClosedWorld => $this->closedWorld',
				151,
			],
		]);
	}

}
