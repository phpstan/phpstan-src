<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use Override;
use Tally\Expectation\ExpectationSource;
use Tally\Expectation\SourceLocation;
use Tally\Metrics;

/** The expected numbers of a benchmark in data/, kept in the comment at its top */
final class AnalysedFileExpectations implements ExpectationSource
{

	public function __construct(private AnalysedFileHeader $header, private string $file, private ?Metrics $metrics)
	{
	}

	#[Override]
	public function read(): ?Metrics
	{
		return $this->metrics;
	}

	#[Override]
	public function file(): string
	{
		return $this->file;
	}

	#[Override]
	public function rewrite(string $code, Metrics $measured): string
	{
		return $this->header->write($code, $measured);
	}

	#[Override]
	public function location(): SourceLocation
	{
		return new SourceLocation($this->file, $this->metrics === null ? 1 : AnalysedFileHeader::LINE);
	}

}
