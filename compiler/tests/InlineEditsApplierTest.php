<?php declare(strict_types = 1);

namespace PHPStan\Compiler;

use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;
use function file_get_contents;
use function file_put_contents;
use function json_encode;
use function mkdir;
use function rmdir;
use function strlen;
use function strpos;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use const JSON_THROW_ON_ERROR;

final class InlineEditsApplierTest extends TestCase
{

	public function testApply(): void
	{
		$caller = tempnam(sys_get_temp_dir(), 'caller');
		$callee = tempnam(sys_get_temp_dir(), 'callee');
		$source = "<?php\n\$a = \$foo->getBar();\n\$b = \$foo->getBar();\n";
		file_put_contents($caller, $source);
		file_put_contents($callee, "<?php\nclass Foo {\n\tprivate int \$bar = 1;\n\tpublic function __construct(private readonly ?string \$baz = null)\n\t{\n\t}\n}\n");

		$start = strpos($source, '$foo->getBar()');
		$edits = [
			[
				'file' => $caller,
				'start' => $start,
				'end' => $start + strlen('$foo->getBar()') - 1,
				'replacement' => '$foo->bar',
				'callee' => 'Foo::getBar',
				'publicize' => [['class' => 'Foo', 'property' => 'bar', 'file' => $callee]],
			],
			[
				// nested inside the first one: outermost wins, it is skipped
				'file' => $caller,
				'start' => $start,
				'end' => $start + 3,
				'replacement' => 'NOPE',
				'callee' => 'Foo::getBar',
				'publicize' => [],
			],
			[
				'file' => $caller,
				'start' => $start + strlen('$foo->getBar();' . "\n" . '$b = '),
				'end' => $start + strlen('$foo->getBar();' . "\n" . '$b = ') + strlen('$foo->getBar()') - 1,
				'replacement' => '$foo->bar',
				'callee' => 'Foo::getBar',
				'publicize' => [['class' => 'Foo', 'property' => 'baz', 'file' => $callee]],
			],
		];
		$editsFile = tempnam(sys_get_temp_dir(), 'edits');
		file_put_contents($editsFile, json_encode($edits, JSON_THROW_ON_ERROR));

		$stats = (new InlineEditsApplier())->apply($editsFile);

		self::assertSame(['edits' => 2, 'files' => 1, 'properties' => 2], $stats);
		self::assertSame("<?php\n\$a = \$foo->bar;\n\$b = \$foo->bar;\n", file_get_contents($caller));
		self::assertSame("<?php\nclass Foo {\n\t#[\\PHPStan\\Reflection\\Attribute\\PrivateProperty] public int \$bar = 1;\n\tpublic function __construct(#[\\PHPStan\\Reflection\\Attribute\\PrivateProperty] public readonly ?string \$baz = null)\n\t{\n\t}\n}\n", file_get_contents($callee));

		unlink($caller);
		unlink($callee);
		unlink($editsFile);
	}

	public function testRefusesToPublicizeProtectedPackageProperty(): void
	{
		$vendor = tempnam(sys_get_temp_dir(), 'vendor');
		unlink($vendor);
		mkdir($vendor . '/acme/lib', 0777, true);
		$caller = tempnam(sys_get_temp_dir(), 'caller');
		$callee = $vendor . '/acme/lib/Foo.php';
		$source = "<?php\n\$a = \$foo->getBar();\n";
		file_put_contents($caller, $source);
		$calleeSource = "<?php\nclass Foo {\n\tprivate int \$bar = 1;\n}\n";
		file_put_contents($callee, $calleeSource);

		$start = strpos($source, '$foo->getBar()');
		$edits = [
			[
				'file' => $caller,
				'start' => $start,
				'end' => $start + strlen('$foo->getBar()') - 1,
				'replacement' => '$foo->bar',
				'callee' => 'Foo::getBar',
				'publicize' => [['class' => 'Foo', 'property' => 'bar', 'file' => $callee]],
			],
		];
		$editsFile = tempnam(sys_get_temp_dir(), 'edits');
		file_put_contents($editsFile, json_encode($edits, JSON_THROW_ON_ERROR));

		try {
			(new InlineEditsApplier([$vendor . '/acme']))->apply($editsFile);
			self::fail('Expected the property to be refused');
		} catch (ShouldNotHappenException $e) {
			self::assertStringContainsString('Refusing to make Foo::$bar public, it is declared in a protected package', $e->getMessage());
		} finally {
			self::assertSame($calleeSource, file_get_contents($callee));
			unlink($caller);
			unlink($callee);
			unlink($editsFile);
			rmdir($vendor . '/acme/lib');
			rmdir($vendor . '/acme');
			rmdir($vendor);
		}
	}

}
