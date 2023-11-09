<?php // lint >= 8.0

namespace Bug8663;

/**
 * Provides example to demonstrate an issue with PHPStan.
 */
class StanExample2
{

    /**
     * An exception is caught but not captured.
     *
     * That's OK for PHP 8 but not for 7.4 - PHPStan does not report the issue.
     */
    public function catchExceptionsWithoutCapturing(): void
    {
        try {
            print 'Lets do something nasty here.';
            throw new \Exception('This is nasty');
        } catch (\Exception) {
            print 'Exception occured';
        }
    }
}
