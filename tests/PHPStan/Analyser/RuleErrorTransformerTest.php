<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PHPStan\File\FileReader;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\PHPStanTestCase;
use function count;
use function is_string;
use function sprintf;

final class RuleErrorTransformerTest extends PHPStanTestCase
{

	private const SPACES_FILE = __DIR__ . '/data/rule-error-transformer/spaces.php';
	private const TABS_FILE = __DIR__ . '/data/rule-error-transformer/tabs.php';

	private function createCountingParser(): CountingParser
	{
		/** @var Parser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionPhpParser');

		return new CountingParser($parser);
	}

	/**
	 * @return array{Node\Stmt[], list<Variable>}
	 */
	private function parseFile(string $file): array
	{
		/** @var Parser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionPhpParser');
		$nodes = $parser->parse(FileReader::read($file));
		if ($nodes === null) {
			$this->fail(sprintf('Could not parse %s', $file));
		}

		$variables = [];
		foreach ((new NodeFinder())->findInstanceOf($nodes, Variable::class) as $variable) {
			if (!is_string($variable->name)) {
				continue;
			}

			$variables[] = $variable;
		}

		return [$nodes, $variables];
	}

	private function createFixableError(Variable $variable): FixableNodeRuleError&IdentifierRuleError
	{
		return RuleErrorBuilder::message('Rename variable.')
			->identifier('tests.ruleErrorTransformer')
			->fixNode($variable, static function (Variable $node): Variable {
				if (is_string($node->name)) {
					$node->name .= 'Renamed';
				}

				return $node;
			})
			->build();
	}

	private function createScope(string $file): Scope
	{
		$scope = $this->createMock(Scope::class);
		$scope->method('getFile')->willReturn($file);
		$scope->method('getFileDescription')->willReturn($file);
		$scope->method('isInTrait')->willReturn(false);

		return $scope;
	}

	public function testFileIsParsedOnceForMultipleFixableErrors(): void
	{
		[$fileNodes, $variables] = $this->parseFile(self::SPACES_FILE);
		$this->assertGreaterThan(1, count($variables));

		$parser = $this->createCountingParser();
		$transformer = new RuleErrorTransformer($parser);
		$scope = $this->createScope(self::SPACES_FILE);

		foreach ($variables as $variable) {
			$error = $transformer->transform($this->createFixableError($variable), $scope, $fileNodes, $variable);
			$this->assertNotNull($error->getFixedErrorDiff());
		}

		$this->assertSame(1, $parser->parseCount);
	}

	public function testCachingDoesNotChangeProducedDiffs(): void
	{
		foreach ([self::SPACES_FILE, self::TABS_FILE] as $file) {
			[$fileNodes, $variables] = $this->parseFile($file);
			$sharedTransformer = new RuleErrorTransformer($this->createCountingParser());
			$scope = $this->createScope($file);

			foreach ($variables as $variable) {
				$freshTransformer = new RuleErrorTransformer($this->createCountingParser());

				$expected = $freshTransformer->transform($this->createFixableError($variable), $scope, $fileNodes, $variable);
				$actual = $sharedTransformer->transform($this->createFixableError($variable), $scope, $fileNodes, $variable);

				$expectedDiff = $expected->getFixedErrorDiff();
				$actualDiff = $actual->getFixedErrorDiff();
				$this->assertNotNull($expectedDiff);
				$this->assertNotNull($actualDiff);
				$this->assertSame($expectedDiff->originalHash, $actualDiff->originalHash);
				$this->assertSame($expectedDiff->diff, $actualDiff->diff);
			}
		}
	}

	public function testInterleavedFilesDoNotShareIndentation(): void
	{
		[$spacesNodes, $spacesVariables] = $this->parseFile(self::SPACES_FILE);
		[$tabsNodes, $tabsVariables] = $this->parseFile(self::TABS_FILE);

		$transformer = new RuleErrorTransformer($this->createCountingParser());
		$spacesScope = $this->createScope(self::SPACES_FILE);
		$tabsScope = $this->createScope(self::TABS_FILE);

		$count = count($spacesVariables) < count($tabsVariables) ? count($spacesVariables) : count($tabsVariables);
		for ($i = 0; $i < $count; $i++) {
			$spacesError = $transformer->transform($this->createFixableError($spacesVariables[$i]), $spacesScope, $spacesNodes, $spacesVariables[$i]);
			$tabsError = $transformer->transform($this->createFixableError($tabsVariables[$i]), $tabsScope, $tabsNodes, $tabsVariables[$i]);

			$spacesDiff = $spacesError->getFixedErrorDiff();
			$tabsDiff = $tabsError->getFixedErrorDiff();
			$this->assertNotNull($spacesDiff);
			$this->assertNotNull($tabsDiff);

			$freshSpaces = (new RuleErrorTransformer($this->createCountingParser()))->transform($this->createFixableError($spacesVariables[$i]), $spacesScope, $spacesNodes, $spacesVariables[$i])->getFixedErrorDiff();
			$freshTabs = (new RuleErrorTransformer($this->createCountingParser()))->transform($this->createFixableError($tabsVariables[$i]), $tabsScope, $tabsNodes, $tabsVariables[$i])->getFixedErrorDiff();
			$this->assertNotNull($freshSpaces);
			$this->assertNotNull($freshTabs);

			$this->assertSame($freshSpaces->diff, $spacesDiff->diff);
			$this->assertSame($freshTabs->diff, $tabsDiff->diff);
		}
	}

	public function testNoFileNodesDoesNotReadTheFile(): void
	{
		[, $variables] = $this->parseFile(self::SPACES_FILE);
		$parser = $this->createCountingParser();
		$transformer = new RuleErrorTransformer($parser);

		$error = $transformer->transform(
			$this->createFixableError($variables[0]),
			$this->createScope(self::SPACES_FILE),
			[],
			$variables[0],
		);

		$this->assertNull($error->getFixedErrorDiff());
		$this->assertSame(0, $parser->parseCount);
	}

}
