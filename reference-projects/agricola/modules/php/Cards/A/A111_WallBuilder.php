<?php
namespace AGR\Cards\A;
use AGR\Managers\Players;

class A111_WallBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A111_WallBuilder';
    $this->name = clienttranslate('Wall Builder');
    $this->deck = 'A';
    $this->number = 111;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build at least 1 room, you can place 1 <FOOD> on each of the next 4 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct');
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    return $this->futureMeeplesNode([FOOD => 1], 4);
  }
}
