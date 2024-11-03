<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\Error;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use Throwable;

class Bug11857Test extends PHPStanTestCase
{

	public function dataIntegrationTests(): iterable
	{
		yield [__DIR__ . '/data/bug-11857.php'];
	}

    /** @dataProvider dataIntegrationTests */
    public function testIntegration(string $file): void
    {
		$this->assertNoErrors($this->runAnalyse($file));
    }

    /** @return Error[] */
    private function runAnalyse(string $file): array
    {
        $file = $this->getFileHelper()->normalizePath($file);

        $analyser   = self::getContainer()->getByType(Analyser::class);
        $fileHelper = self::getContainer()->getByType(FileHelper::class);

        $errors = $analyser->analyse([$file], null, null, true, null)->getErrors();

        foreach ($errors as $error) {
            $this->assertSame($fileHelper->normalizePath($file), $error->getFilePath());
        }

        return $errors;
    }

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/bug-11857.neon'];
	}
}
