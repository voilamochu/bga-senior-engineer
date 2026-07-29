<?php
namespace AGR\Cards\E;

class E16_BriarHedge extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E16_BriarHedge';
    $this->name = clienttranslate('Briar Hedge');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 16;
    $this->category = 'FARMYARD_-__FENCING_OR_STABLE_BUILDING';
    $this->desc = [
      clienttranslate('You do not need to pay wood for fences that you build on the edge of your farmyard board.'),
    ];
    $this->prerequisite = clienttranslate('1 Animal of Each Type');
    $this->implemented = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countAnimalsOnBoard()[SHEEP] < 1 || $player->countAnimalsOnBoard()[PIG] < 1 || $player->countAnimalsOnBoard()[CATTLE] < 1) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
}