<?php // lint < 8.0
declare(strict_types=1);

namespace Bug11200;

use function PHPStan\Testing\assertType;

class Bug11200SplFileObjectTestsPhpLt8
{
  public function fgetss() : void
  {
    // fgetss has been removed as of PHP 8.0.0
    if ( \version_compare(\PHP_VERSION,'8.0.0','<') )
    {
      $file = new \SplFileObject('php://memory', 'r');
      assertType('bool', $file->eof());
      if ( $file->eof() )
      {
        return;
      }
      assertType('false', $file->eof());
      // call method that has side effects
      $file->fgetss();
      // the value of eof may have changed
      assertType('bool', $file->eof());
    }
  }
}