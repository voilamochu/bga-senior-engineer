<?php
namespace AGR\Cards\A;

class A138_Harpooner extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A138_Harpooner';
    $this->name = clienttranslate('Harpooner');
    $this->deck = 'A';
    $this->number = 138;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Fishing__ space you can also pay 1 <WOOD> to get 1 <FOOD> for each person you have, and 1 <REED>'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->payGainNode([WOOD => 1], [FOOD => $player->countFarmers(), REED => 1]);
    // actionId => 'Harpoon' => OBSOLETE ??
  }
}
