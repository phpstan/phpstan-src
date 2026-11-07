<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\ExpectationFailedException;
use function realpath;

/**
 * @extends RuleTestCase<Rule<String_>>
 */
final class RuleTestCaseFailureMessageTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new /** @implements Rule<String_> */ class implements Rule {

			public function getNodeType(): string
			{
				return String_::class;
			}

			/**
			 * @param String_ $node
			 */
			public function processNode(Node $node, Scope $scope): array
			{
				if ($node->value !== 'foo') {
					return [];
				}

				return [
					RuleErrorBuilder::message('Foo string.')
						->identifier('test.fooString')
						->fixNode($node, static fn (String_ $node) => new String_('bar'))
						->build(),
				];
			}

		};
	}

	public function testFixFailureMentionsFiles(): void
	{
		$file = __DIR__ . '/data/rule-test-case-fix.php';
		$expectedFile = __DIR__ . '/data/rule-test-case-fix.php.fixed';
		try {
			$this->fix($file, $expectedFile);
		} catch (ExpectationFailedException $e) {
			$this->assertStringContainsString((string) realpath($file), $e->getMessage());
			$this->assertStringContainsString((string) realpath($expectedFile), $e->getMessage());
			return;
		}

		$this->fail('fix() should have failed.');
	}

	public function testAnalyseFailureMentionsFiles(): void
	{
		$file = __DIR__ . '/data/rule-test-case-fix.php';
		try {
			$this->analyse([$file], []);
		} catch (ExpectationFailedException $e) {
			$this->assertStringContainsString((string) realpath($file), $e->getMessage());
			return;
		}

		$this->fail('analyse() should have failed.');
	}

}
