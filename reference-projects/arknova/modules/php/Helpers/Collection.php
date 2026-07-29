<?php

namespace ARK\Helpers;

class Collection extends \ArrayObject
{
  public function getIds(): array
  {
    return array_keys($this->getArrayCopy());
  }

  public function empty(): bool
  {
    return empty($this->getArrayCopy());
  }

  public function jsonSerialize(): array
  {
    $t = [];
    foreach ($this->getArrayCopy() as $key => $obj) {
      $t[$key] = is_object($obj) ? $obj->jsonSerialize() : $obj;
    }
    return $t;
  }

  public function has(string|int $key): bool
  {
    return array_key_exists($key, $this->getArrayCopy());
  }

  public function first(): mixed
  {
    $arr = $this->toArray();
    return isset($arr[0]) ? $arr[0] : null;
  }

  public function last(): mixed
  {
    $arr = $this->toArray();
    return empty($arr) ? null : $arr[count($arr) - 1];
  }

  public function rand(): mixed
  {
    $arr = $this->getArrayCopy();
    $key = array_rand($arr, 1);
    return $arr[$key];
  }

  public function toArray(): array
  {
    return array_values($this->getArrayCopy());
  }

  public function toAssoc(): array
  {
    return $this->getArrayCopy();
  }

  public function map(callable $func): Collection
  {
    return new Collection(array_map($func, $this->toAssoc()));
  }

  public function merge(Collection $arr): Collection
  {
    return new Collection($this->toAssoc() + $arr->toAssoc());
  }

  public function reduce(callable $func, mixed $init): mixed
  {
    return array_reduce($this->toArray(), $func, $init);
  }

  public function filter(callable $func): Collection
  {
    return new Collection(array_filter($this->toAssoc(), $func, ARRAY_FILTER_USE_BOTH));
  }

  public function limit(int $n): Collection
  {
    return new Collection(array_slice($this->toAssoc(), 0, $n, true));
  }

  public function includes(mixed $t): bool
  {
    return in_array($t, $this->getArrayCopy());
  }

  public function ui(): array
  {
    return $this->map(function ($elem) {
      return $elem->getUiData();
    })->toArray();
  }

  public function uiAssoc(): array
  {
    return $this->map(function ($elem) {
      return $elem->getUiData();
    })->toAssoc();
  }

  public function order(callable $callback): Collection
  {
    $t = $this->getArrayCopy();
    \uasort($t, $callback);
    return new Collection($t);
  }

  /*****
   * Méthods for collection of object
   */
  public function where(string $field, mixed $value): Collection
  {
    return is_null($value)
      ? $this
      : $this->filter(function ($obj) use ($field, $value) {
        $method = 'get' . ucfirst($field);
        $objValue = $obj->$method();
        return is_array($value)
          ? in_array($objValue, $value)
          : (strpos($value, '%') !== false
            ? like_match($value, $objValue)
            : $objValue == $value);
      });
  }

  public function whereNull(string $field): Collection
  {
    return $this->filter(function ($obj) use ($field) {
      $method = 'get' . ucfirst($field);
      $objValue = $obj->$method();
      return is_null($objValue);
    });
  }

  public function orderBy(string $field, string $asc = 'ASC'): Collection
  {
    return $this->order(function ($a, $b) use ($field, $asc) {
      $method = 'get' . ucfirst($field);
      return $asc == 'ASC' ? $a->$method() - $b->$method() : $b->$method() - $a->$method();
    });
  }

  public function update(string $field, mixed $value): Collection
  {
    $method = 'set' . ucfirst($field);
    foreach ($this->getArrayCopy() as $obj) {
      $obj->$method($value);
    }
    return $this;
  }
}

function like_match(string $pattern, string $subject): bool
{
  $pattern = str_replace('%', '.*', preg_quote($pattern, '/'));
  return (bool) preg_match("/^{$pattern}$/i", $subject);
}
