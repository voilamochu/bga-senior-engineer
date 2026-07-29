<?php

namespace ARK\Actions;

use ARK\Managers\ZooCards;
use ARK\Models\Player;

class SpecialEffect extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SPECIAL_EFFECT;
  }

  public function isDoable(Player $player): bool
  {
    $args = $this->getCtxArgs();
    $card = ZooCards::get($args['cardId']);
    $method = 'is' . \ucfirst($args['method']) . 'Doable';
    $arguments = $args['args'] ?? [];
    return \method_exists($card, $method) ? $card->$method($player, ...$arguments) : true;
  }

  public function getDescription(): string|array
  {
    $args = $this->getCtxArgs();
    $card = ZooCards::get($args['cardId']);
    $method = 'get' . \ucfirst($args['method']) . 'Description';
    $arguments = $args['args'] ?? [];
    return \method_exists($card, $method) ? $card->$method(...$arguments) : '';
  }

  public function isIndependent(?Player $player = null): bool
  {
    $args = $this->getCtxArgs();
    $card = ZooCards::get($args['cardId']);
    $method = 'isIndependent' . \ucfirst($args['method']);
    return \method_exists($card, $method) ? $card->$method($player) : false;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    $args = $this->getCtxArgs();
    $card = ZooCards::get($args['cardId']);
    $method = $args['method'];
    return \method_exists($card, $method);
  }

  public function stSpecialEffect()
  {
    $args = $this->getCtxArgs();
    $card = ZooCards::get($args['cardId']);
    $method = $args['method'];
    $arguments = $args['args'] ?? [];
    if (\method_exists($card, $method)) {
      $card->$method(...$arguments);
      $this->resolveAction();
    }
  }

  public function argsSpecialEffect()
  {
    $args = $this->getCtxArgs();
    $card = ZooCards::get($args['cardId']);
    $method = 'args' . \ucfirst($args['method']);
    $arguments = $args['args'] ?? [];
    return \method_exists($card, $method) ? $card->$method(...$arguments) : [];
  }

  public function actSpecialEffect(...$actArgs)
  {
    $args = $this->getCtxArgs();
    $card = ZooCards::get($args['cardId']);
    $method = 'act' . \ucfirst($args['method']);
    $arguments = $args['args'] ?? [];
    if (!\method_exists($card, $method)) {
      throw new \BgaVisibleSystemException('Corresponding act function does not exists : ' . $method);
    }

    $card->$method(...array_merge($actArgs, $arguments));
    $this->resolveAction();
  }
}
