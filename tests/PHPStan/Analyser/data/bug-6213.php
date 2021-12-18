<?php declare(strict_types=1);

namespace Bug6213;

use function PHPStan\Testing\assertType;
use DOMDocument;

$document = new DOMDocument('1.0', 'utf-8');
$element = $document->createElement('div', 'content');
assertType('DOMElement', $element);
assertType('DOMNode|null', $element->firstChild);
