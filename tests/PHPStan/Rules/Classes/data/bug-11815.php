<?php // lint >= 8.2

declare(strict_types = 1);

class Dimensions
{
    public function __construct(
        public int $width,
        public int $height,
    ) {
    }
}

class StoreProcessorResult
{
    public function __construct(
        public string $path,
        public string $mimetype,
        public Dimensions $dimensions,
        public int $filesize,
        public true|null $identical = null,
     ) {
     }
}

/**
 * @return array{path: string, identical?: true}
 */
function getPath(): array
{
	$data = ['path' => 'some/path'];
	if ((bool)rand(0, 1)) {
		$data['identical'] = true;
	}
	return $data;
}

$data = getPath();
$data['dimensions'] = new Dimensions(100, 100);
$data['mimetype'] = 'image/png';
$data['filesize'] = 123456;

$dto = new StoreProcessorResult(...$data);
