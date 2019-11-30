<?php

namespace Generics\Bug2627;

/**
 * @template-covariant TValue
 */
class Collection {
  /** @var array<int,TValue> $data */
  private $data;

  /**
   * @param array<int,TValue> $data
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * @return array<int,TValue>
   */
  public function getData() {
    return $this->data;
  }

  /**
   * @param array<int,TValue> $data
   */
  public function setData(array $data): void {
    $this->data = $data;
  }

}
