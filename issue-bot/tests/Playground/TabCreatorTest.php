<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

use PHPUnit\Framework\TestCase;
use function count;

class TabCreatorTest extends TestCase
{

	/**
	 * @return array<array{array<int, list<PlaygroundError>>, list<PlaygroundResultTab>}>
	 */
	public function dataCreate(): array
	{
		return [
			[
				[
					70100 => [

					],
					70200 => [

					],
				],
				[
					new PlaygroundResultTab('PHP 7.1 – 7.2', []),
				],
			],
			[
				[
					70100 => [
						new PlaygroundError(2, 'Foo'),
					],
					70200 => [
						new PlaygroundError(2, 'Foo'),
					],
				],
				[
					new PlaygroundResultTab('PHP 7.1 – 7.2 (1 error)', [
						new PlaygroundError(2, 'Foo'),
					]),
				],
			],
			[
				[
					70100 => [
						new PlaygroundError(2, 'Foo'),
						new PlaygroundError(3, 'Foo'),
					],
					70200 => [
						new PlaygroundError(2, 'Foo'),
						new PlaygroundError(3, 'Foo'),
					],
				],
				[
					new PlaygroundResultTab('PHP 7.1 – 7.2 (2 errors)', [
						new PlaygroundError(2, 'Foo'),
						new PlaygroundError(3, 'Foo'),
					]),
				],
			],
			[
				[
					70100 => [
						new PlaygroundError(2, 'Foo'),
						new PlaygroundError(3, 'Foo'),
					],
					70200 => [
						new PlaygroundError(3, 'Foo'),
					],
				],
				[
					new PlaygroundResultTab('PHP 7.2 (1 error)', [
						new PlaygroundError(3, 'Foo'),
					]),
					new PlaygroundResultTab('PHP 7.1 (2 errors)', [
						new PlaygroundError(2, 'Foo'),
						new PlaygroundError(3, 'Foo'),
					]),
				],
			],
		];
	}

	/**
	 * @dataProvider dataCreate
	 * @param array<int, list<PlaygroundError>> $versionedErrors
	 * @param list<PlaygroundResultTab> $expectedTabs
	 * @return void
	 */
	public function testCreate(array $versionedErrors, array $expectedTabs): void
	{
		$tabCreator = new TabCreator();
		$tabs = $tabCreator->create($versionedErrors);
		self::assertCount(count($expectedTabs), $tabs);

		foreach ($tabs as $i => $tab) {
			$expectedTab = $expectedTabs[$i];
			self::assertSame($expectedTab->getTitle(), $tab->getTitle());
			self::assertCount(count($expectedTab->getErrors()), $tab->getErrors());
			foreach ($tab->getErrors() as $j => $error) {
				$expectedError = $expectedTab->getErrors()[$j];
				self::assertSame($expectedError->getMessage(), $error->getMessage());
				self::assertSame($expectedError->getLine(), $error->getLine());
			}
		}
	}

}
