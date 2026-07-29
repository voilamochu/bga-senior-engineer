<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;

class A88_HedgeKeeper extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A88_HedgeKeeper';
    $this->name = clienttranslate('Hedge Keeper');
    $this->deck = 'A';
    $this->number = 88;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you take a __Build Fences__ action, you do not have to pay <WOOD> for 3 of the fences you build.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = [
      clienttranslate('You may spend 0 <WOOD> to build 1-3 fences.'),
      clienttranslate('__Mini Pasture__ (B002), __Overhaul__ (C001), and other fencing effects that are not the literal __Build Fences__ action, do not trigger this card.'),
    ];
  }

  public function onPlayerComputeCostsFencing($player, &$args)
  {
    if (!empty($args['costs']['constraints'] ?? [])) {
      return;
    }
    Utils::addCost($args['costs'], [
      'max' => 3,
      'nb' => 1,
      'sources' => [$this->id],
    ]);
  }
}
