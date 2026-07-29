<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;

class E138_LivestockExpert extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E138_LivestockExpert';
    $this->name = clienttranslate('Livestock Expert');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 138;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'If you play this card in round 11 or before, choose an animal type: you immediately get a number of animals of that type equal to the number you already have on your farm.'
      ),
    ];
    $this->players = '3+';
  }

  public function onBuy($player)
  {
    if (Globals::getTurn() > 11) {
      return;
    }
    $childs = [];

    foreach ([SHEEP, PIG, CATTLE] as $type) {
      $n = $player->countAnimalsOnBoard()[$type];

      if ($n > 0) {
        $childs[] = $this->gainNode([$type => $n]);
      }
    }

    if ($childs == []) {
      return;
    }

    return [
      'type' => NODE_XOR,
      'childs' => $childs
    ];
  }
}
