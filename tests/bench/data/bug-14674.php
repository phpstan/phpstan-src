<?php declare(strict_types = 1);

namespace Bug14674;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhpStanPerformanceTest extends TestCase {

  /**
   * @param \Closure $closure
   */
  #[DataProvider('performanceProvider')]
  public function testPerformance(\Closure $closure): void {
    $this->assertTrue($closure());
  }

  /**
   * @return iterable<string, array{closure: \Closure}>
   */
  public static function performanceProvider(): iterable {
    foreach(['0', '1'] as $level_1) {
      $keys = [$level_1];

      foreach(['0', '1'] as $level_2) {
        $keys[] = $level_2;

        foreach(['0', '1'] as $level_3) {
          $keys[] = $level_3;

          foreach(['0', '1'] as $level_4) {
            $keys[] = $level_4;

            foreach(['0', '1'] as $level_5) {
              $keys[] = $level_5;

              foreach(['0', '1'] as $level_6) {
                $keys[] = $level_6;

                foreach(['0', '1'] as $level_7) {
                  $keys[] = $level_7;

                  foreach(['0', '1'] as $level_8) {
                    $keys[] = $level_8;

                    foreach(['0', '1'] as $level_9) {
                      $keys[] = $level_9;

                      foreach(['0', '1'] as $level_10) {
                        $keys[] = $level_10;

                        foreach(['0', '1'] as $level_11) {
                          $keys[] = $level_11;

                          foreach(['0', '1'] as $level_12) {
                            $keys[] = $level_12;

                            foreach(['0', '1'] as $level_13) {
                              $keys[] = $level_13;

                              $case = [
                                'closure' => function () use ($level_1, $level_3, $level_5, $level_13) {
                                  return $level_1 === '1' && $level_3 === '1' && $level_5 === '1' && $level_13 === '1';
                                },
                              ];

                              yield implode('-', $keys) => $case;
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

}
