<?php
namespace AGR\Cards\E;

class E57_CheeseFondue extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E57_CheeseFondue';
    $this->name = clienttranslate('Cheese Fondue');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 57;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Each time you bake at least 1 <GRAIN> into bread, you get 1 additional <FOOD> if you have at least 1 <SHEEP> and (another) 1 additional <FOOD> if you have at least 1 <CATTLE>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Exchange') && $event['trigger'] == BREAD;
  }

  public function onPlayerAfterExchange($player, $event)
  {
    $n = ($player->countAnimalsOnBoard()[SHEEP] > 0) + ($player->countAnimalsOnBoard()[CATTLE] > 0);

    if ($n > 0) {
      return $this->gainNode([FOOD => $n]);
    }
  }
}
