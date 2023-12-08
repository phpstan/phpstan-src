<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use Nette\Utils\Json;
use PHPStan\File\FileWriter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function unlink;

/**
 * @extends RuleTestCase<PhpStanNamespaceIn3rdPartyPackageRule>
 */
class PhpStanNamespaceIn3rdPartyPackageRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PhpStanNamespaceIn3rdPartyPackageRule(new ApiRuleHelper());
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		@unlink(__DIR__ . '/composer.json');
	}

	public function testRulePhpStanNamespaceInPhpStanPackage(): void
	{
		$this->createComposerJson('phpstan/foo');
		$this->analyse([__DIR__ . '/data/phpstan-namespace.php'], []);
	}

	public function testRulePhpStanNamespaceIn3rdPartyPackage(): void
	{
		$this->createComposerJson('my/foo');
		$this->analyse([__DIR__ . '/data/phpstan-namespace.php'], [
			[
				'Declaring PHPStan namespace is not allowed in 3rd party packages.',
				3,
				"See:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			],
		]);
	}

	public function testRuleCustomNamespaceInPhpStanPackage(): void
	{
		$this->createComposerJson('phpstan/foo');
		$this->analyse([__DIR__ . '/data/custom-namespace.php'], []);
	}

	public function testRuleCustomNamespaceIn3rdPartyPackage(): void
	{
		$this->createComposerJson('my/foo');
		$this->analyse([__DIR__ . '/data/custom-namespace.php'], []);
	}

	private function createComposerJson(string $packageName): void
	{
		FileWriter::write(__DIR__ . '/composer.json', Json::encode(['name' => $packageName], Json::PRETTY));
	}

}
