<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\Analyser;
use PHPStan\File\FileHelper;

class PhpDocInheritanceResolverIntegrationTest extends \PHPStan\Testing\TestCase
{

	private const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

	public function testMergeVarTags(): void
	{
		$file = self::DATA_DIR . 'inheritdoc-var.php';
		$errors = $this->runAnalyse($file);
		$this->assertCount(5, $errors);

		$this->assertSame('Parameter #1 $n of method PhpDoc\VarTag\One::requireInt() expects int, PhpDoc\VarTag\A given.', $errors[0]->getMessage());
		$this->assertSame($file, $errors[0]->getFile());
		$this->assertSame(14, $errors[0]->getLine());

		$this->assertSame('Parameter #1 $n of method PhpDoc\VarTag\One::requireInt() expects int, PhpDoc\VarTag\B given.', $errors[1]->getMessage());
		$this->assertSame($file, $errors[1]->getFile());
		$this->assertSame(22, $errors[1]->getLine());

		$this->assertSame('Parameter #1 $n of method PhpDoc\VarTag\One::requireInt() expects int, PhpDoc\VarTag\B given.', $errors[2]->getMessage());
		$this->assertSame($file, $errors[2]->getFile());
		$this->assertSame(30, $errors[2]->getLine());

		$this->assertSame('Parameter #1 $n of method PhpDoc\VarTag\One::requireInt() expects int, PhpDoc\VarTag\B given.', $errors[3]->getMessage());
		$this->assertSame($file, $errors[3]->getFile());
		$this->assertSame(37, $errors[3]->getLine());

		$this->assertSame('Parameter #1 $n of method PhpDoc\VarTag\One::requireInt() expects int, PhpDoc\VarTag\B given.', $errors[4]->getMessage());
		$this->assertSame($file, $errors[4]->getFile());
		$this->assertSame(42, $errors[4]->getLine());
	}

	public function testMergeParamTags(): void
	{
		$file = self::DATA_DIR . 'inheritdoc-param.php';
		$errors = $this->runAnalyse($file);
		$this->assertCount(3, $errors);

		$this->assertSame('Method PhpDoc\ParamTag\GrandparentClass::method() has parameter $two with no typehint specified.', $errors[0]->getMessage());
		$this->assertSame($file, $errors[0]->getFile());
		$this->assertSame(12, $errors[0]->getLine());

		$this->assertSame('Parameter #1 $n of method PhpDoc\ParamTag\ChildClass::requireInt() expects int, PhpDoc\ParamTag\C given.', $errors[1]->getMessage());
		$this->assertSame($file, $errors[1]->getFile());
		$this->assertSame(26, $errors[1]->getLine());

		$this->assertSame('Parameter #1 $n of method PhpDoc\ParamTag\ChildClass::requireInt() expects int, PhpDoc\ParamTag\B given.', $errors[2]->getMessage());
		$this->assertSame($file, $errors[2]->getFile());
		$this->assertSame(27, $errors[2]->getLine());
	}

	public function testMergeReturnTags(): void
	{
		$file = self::DATA_DIR . 'inheritdoc-return.php';
		$errors = $this->runAnalyse($file);
		$this->assertCount(2, $errors);

		$this->assertSame('Method PhpDoc\ReturnTag\ParentClass::method() should return PhpDoc\ReturnTag\C but returns PhpDoc\ReturnTag\B.', $errors[0]->getMessage());
		$this->assertSame($file, $errors[0]->getFile());
		$this->assertSame(30, $errors[0]->getLine());

		$this->assertSame('Method PhpDoc\ReturnTag\ChildClass::method() should return PhpDoc\ReturnTag\C but returns int.', $errors[1]->getMessage());
		$this->assertSame($file, $errors[1]->getFile());
		$this->assertSame(35, $errors[1]->getLine());
	}

	public function testMergeThrowsTags(): void
	{
		$file = self::DATA_DIR . 'inheritdoc-throws.php';
		$errors = $this->runAnalyse($file);
		$this->assertCount(5, $errors);

		$this->assertSame('PHPDoc tag @throws with type PhpDoc\ThrowsTag\A is not subtype of Throwable', $errors[0]->getMessage());
		$this->assertSame($file, $errors[0]->getFile());
		$this->assertSame(13, $errors[0]->getLine());

		$this->assertSame('PHPDoc tag @throws with type PhpDoc\ThrowsTag\B is not subtype of Throwable', $errors[1]->getMessage());
		$this->assertSame($file, $errors[1]->getFile());
		$this->assertSame(19, $errors[1]->getLine());

		$this->assertSame('PHPDoc tag @throws with type PhpDoc\ThrowsTag\A|PhpDoc\ThrowsTag\B|PhpDoc\ThrowsTag\C|PhpDoc\ThrowsTag\D is not subtype of Throwable', $errors[2]->getMessage());
		$this->assertSame($file, $errors[2]->getFile());
		$this->assertSame(28, $errors[2]->getLine());

		$this->assertSame('PHPDoc tag @throws with type PhpDoc\ThrowsTag\A|PhpDoc\ThrowsTag\B|PhpDoc\ThrowsTag\C|PhpDoc\ThrowsTag\D is not subtype of Throwable', $errors[3]->getMessage());
		$this->assertSame($file, $errors[3]->getFile());
		$this->assertSame(34, $errors[3]->getLine());

		$this->assertSame('PHPDoc tag @throws with type PhpDoc\ThrowsTag\A|PhpDoc\ThrowsTag\B|PhpDoc\ThrowsTag\C|PhpDoc\ThrowsTag\D is not subtype of Throwable', $errors[4]->getMessage());
		$this->assertSame($file, $errors[4]->getFile());
		$this->assertSame(39, $errors[4]->getLine());
	}

	/**
	 * @param string $file
	 * @return \PHPStan\Analyser\Error[]
	 */
	private function runAnalyse(string $file): array
	{
		$file = $this->getFileHelper()->normalizePath($file);
		/** @var \PHPStan\Analyser\Analyser $analyser */
		$analyser = self::getContainer()->getByType(Analyser::class);
		/** @var \PHPStan\File\FileHelper $fileHelper */
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		/** @var \PHPStan\Analyser\Error[] $errors */
		$errors = $analyser->analyse([$file])->getErrors();
		foreach ($errors as $error) {
			$this->assertSame($fileHelper->normalizePath($file), $error->getFile());
		}

		return $errors;
	}

}
