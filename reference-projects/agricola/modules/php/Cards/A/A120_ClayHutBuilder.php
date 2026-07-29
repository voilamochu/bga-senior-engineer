<?php
namespace AGR\Cards\A;

class A120_ClayHutBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A120_ClayHutBuilder';
    $this->name = clienttranslate('Clay Hut Builder');
    $this->deck = 'A';
    $this->number = 120;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once you no longer live in a wooden house, place 2 <CLAY> on each of the next 5 round spaces. At the start of these rounds, you get the <CLAY>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->onPlayerAfterRenovation($player, []);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation');
  }

  public function onPlayerAfterRenovation($player, $event)
  {     
    if ($this->getPlayer()->getRoomType() == 'roomWood' || $this->isFlagged()) {
      return null;
    }

    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([CLAY => 2], 5),
        $this->flagCardNode(),
      ]
    ];
  }
}
