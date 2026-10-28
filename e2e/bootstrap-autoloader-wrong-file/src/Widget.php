<?php declare(strict_types = 1);

namespace BootstrapAutoloaderWrongFile;

// The declared name does not match the file name, so the autoloader includes this
// file for BootstrapAutoloaderWrongFile\Widget and Widget is never declared.
class Widgets
{

}
