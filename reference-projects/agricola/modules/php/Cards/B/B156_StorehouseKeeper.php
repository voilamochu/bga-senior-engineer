<?php
namespace AGR\Cards\B;

class B156_StorehouseKeeper extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B156_StorehouseKeeper';
    $this->name = clienttranslate('Storehouse Keeper');
    $this->deck = 'B';
    $this->number = 156;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Resource Market__ action space, you also get your choice of 1 <CLAY> or 1 <GRAIN>.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'ResourceMarket');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_XOR,
      'childs' => [$this->gainNode([CLAY => 1]), $this->gainNode([GRAIN => 1])],
    ];
  }
}
