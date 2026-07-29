<?php
namespace AGR\Cards\D;
use AGR\Managers\Actions;
use AGR\Helpers\Utils;

class D119_WoodBarterer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D119_WoodBarterer';
    $this->name = clienttranslate('Wood Barterer');
    $this->deck = 'D';
    $this->number = 119;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time before you use an action space with a __Build Fences__ or __Build Rooms__ action, you can choose to either get 2 <WOOD> or exchange up to 2 <WOOD> for 1 <REED> each.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = [
      clienttranslate('This card does not trigger from actions given by cards, such as from __Cottager__ (B087).'),
    ];
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if (
      $args['action'] == \FENCING ||
      $args['action'] == \CONSTRUCT ||
      (isset($args['ctx']) && !is_array($args['ctx']) && $args['ctx']->getCardId() == 'ActionFarmRedevelopment') ||
      (isset($args['ctx']) && !is_array($args['ctx']) && $args['ctx']->getCardId() == 'ActionFarmExpansion')
    ) {
      $args['isDoable'] = Actions::get($args['action'])->isDoable($player, true);
    }
  }

  public function isListeningTo($event)
  {
    // return $this->isActionEvent($event, 'PlaceFarmer', 'player', true) &&
    //   in_array($event['actionCardType'], ['Farmland', 'GrainUtilization', 'Cultivation']);
    return $this->isActionCardEvent($event, 'FarmExpansion') ||
      $this->isActionCardEvent($event, 'Fencing') ||
      $this->isActionCardEvent($event, 'FarmRedevelopment');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->gainNode([WOOD => 2]),
        $this->payGainNode([WOOD => 1], [REED => 1], null, false),
        $this->payGainNode([WOOD => 2], [REED => 2], null, false),
      ],
    ];
  }
}
