<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Bartlett\Sarif\Definition\ArtifactLocation;
use Bartlett\Sarif\Definition\Location;
use Bartlett\Sarif\Definition\Message;
use Bartlett\Sarif\Definition\PhysicalLocation;
use Bartlett\Sarif\Definition\PropertyBag;
use Bartlett\Sarif\Definition\Region;
use Bartlett\Sarif\Definition\Result;
use Bartlett\Sarif\Definition\Run;
use Bartlett\Sarif\Definition\Tool;
use Bartlett\Sarif\Definition\ToolComponent;
use Bartlett\Sarif\SarifLog;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

/**
 * @author Laurent Laville
 */
class SarifErrorFormatter implements ErrorFormatter
{

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		$driver = new ToolComponent('PHPStan');
		$driver->setInformationUri('https://phpstan.org');
		$driver->setVersion('1.1.2');

		$tool = new Tool($driver);

		$results = [];

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$file = $fileSpecificError->getFile();

			$result = new Result(new Message($fileSpecificError->getMessage()));

			$artifactLocation = new ArtifactLocation();
			$artifactLocation->setUri($this->pathToArtifactLocation($file));
			$artifactLocation->setUriBaseId('WORKINGDIR');

			$location = new Location();
			$physicalLocation = new PhysicalLocation($artifactLocation);
			$physicalLocation->setRegion(new Region($fileSpecificError->getLine()));
			$location->setPhysicalLocation($physicalLocation);
			$result->addLocations([$location]);

			$properties = new PropertyBag();
			$properties->addProperty('ignorable', $fileSpecificError->canBeIgnored());
			$result->setProperties($properties);

			$results[] = $result;
		}

		$run = new Run($tool);
		$workingDir = new ArtifactLocation();
		$workingDir->setUri($this->pathToUri(getcwd() . '/'));
		$originalUriBaseIds = [
			'WORKINGDIR' => $workingDir,
		];
		$run->addAdditionalProperties($originalUriBaseIds);
		$run->addResults($results);

		$log = new SarifLog([$run]);

		$output->writeLineFormatted((string) $log);

		return $analysisResult->hasErrors() ? 1 : 0;
	}

	/**
	 * Returns path to resource (file) scanned.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function pathToArtifactLocation(string $path): string
	{
		$workingDir = getcwd();
		if ($workingDir === false) {
			$workingDir = '.';
		}
		if (substr($path, 0, strlen($workingDir)) === $workingDir) {
			// relative path
			return substr($path, strlen($workingDir) + 1);
		}

		// absolute path with protocol
		return $this->pathToUri($path);
	}

	/**
	 * Returns path to resource (file) scanned with protocol.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function pathToUri(string $path): string
	{
		if (parse_url($path, PHP_URL_SCHEME) !== null) {
			// already a URL
			return $path;
		}

		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

		// file:///C:/... on Windows systems
		if (substr($path, 0, 1) !== '/') {
			$path = '/' . $path;
		}

		return 'file://' . $path;
	}

}
