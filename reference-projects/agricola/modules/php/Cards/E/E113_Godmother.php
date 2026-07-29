<?php
namespace AGR\Cards\E;

class E113_Godmother extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E113_Godmother';
    $this->name = clienttranslate('Godmother');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 113;
    $this->category = 'CROPS_-_VEGETABLE';
    $this->desc = [clienttranslate('Each time you take a __Family Growth__ action, you also get 1 <VEGETABLE>.')];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'WishChildren');
  }
  
  public function onPlayerAfterWishChildren($player, $event)
  {
    return $this->gainNode([VEGETABLE => 1]);
  }
}
