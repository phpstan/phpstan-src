<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Testing\PHPStanTestCase;
use function sprintf;

class DefaultStubFilesProviderTest extends PHPStanTestCase
{

	private string $currentWorkingDirectory;

	protected function setUp(): void
	{
		$this->currentWorkingDirectory = $this->getContainer()->getParameter('currentWorkingDirectory');
	}

	public function testGetStubFiles(): void
	{
		$thirdPartyStubFile = sprintf('%s/vendor/thirdpartyStub.stub', $this->currentWorkingDirectory);
		$defaultStubFilesProvider = $this->createDefaultStubFilesProvider(['/projectStub.stub', $thirdPartyStubFile]);
		$stubFiles = $defaultStubFilesProvider->getStubFiles();
		$this->assertContains('/projectStub.stub', $stubFiles);
		$this->assertContains($thirdPartyStubFile, $stubFiles);
	}

	public function testGetProjectStubFiles(): void
	{
		$thirdPartyStubFile = sprintf('%s/vendor/thirdpartyStub.stub', $this->currentWorkingDirectory);
		$defaultStubFilesProvider = $this->createDefaultStubFilesProvider(['/projectStub.stub', $thirdPartyStubFile]);
		$projectStubFiles = $defaultStubFilesProvider->getProjectStubFiles();
		$this->assertContains('/projectStub.stub', $projectStubFiles);
		$this->assertNotContains($thirdPartyStubFile, $projectStubFiles);
	}

	public function testGetProjectStubFilesWhenPathContainsWindowsSeparator(): void
	{
		$thirdPartyStubFile = sprintf('%s\\vendor\\thirdpartyStub.stub', $this->currentWorkingDirectory);
		$defaultStubFilesProvider = $this->createDefaultStubFilesProvider(['/projectStub.stub', $thirdPartyStubFile]);
		$projectStubFiles = $defaultStubFilesProvider->getProjectStubFiles();
		$this->assertContains('/projectStub.stub', $projectStubFiles);
		$this->assertNotContains($thirdPartyStubFile, $projectStubFiles);
	}

	/**
	 * @param string[] $stubFiles
	 */
	private function createDefaultStubFilesProvider(array $stubFiles): DefaultStubFilesProvider
	{
		return new DefaultStubFilesProvider($this->getContainer(), $stubFiles, $this->currentWorkingDirectory);
	}

}
