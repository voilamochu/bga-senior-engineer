<?php
namespace AGR\Cards\D;
use AGR\Cards\B\B85_FarmHand;
use AGR\Helpers\Utils;

class D129_LumberVirtuoso extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D129_LumberVirtuoso';
    $this->name = clienttranslate('Lumber Virtuoso');
    $this->deck = 'D';
    $this->number = 129;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each harvest in which you have at least 5 <WOOD> in your supply, you can discard down to 5 <WOOD> to take a __Build Stables__ or __Build Wood Rooms__ action by paying the usual costs.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('A __Build Wooden Rooms__ action is a __Build Rooms__ action limited to a wooden house.'),
    ];
  }
  
  public function isListeningTo($event)
  {
    return 
      ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvest') || // Make the card usable
      ($this->isAnytime($event) && $this->isFlagged()) || // Use the card
      ($this->isPlayerEvent($event) && $event['type'] == 'AfterHarvest'); // Make the card unusable
  }
  
  public function onPlayerStartHarvest($player, $event)
  {  
    // enforce at least one prompt during harvest
    $flow = $this->getActions($player);
    if ($flow != []) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->flagCardNode(),
          $flow
        ]
      ];
    } else {
      return $this->flagCardNode();
    }
  }

  public function onPlayerAfterHarvest($player)
  {
    return $this->unflagCardNode();
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $flow = $this->getActions($player);
    if ($flow != []) {
      return $flow;
    }
  }

  public function getActions($player)
  {
    $wood = $player->countReserveResource(WOOD);
    if ($wood < 5) { return []; }
    $stablesFlow = B85_FarmHand::wrapStablesWithFarmHandIfPlayed($player, [
      'action' => STABLES,
      'args' => [
        'costs' => Utils::formatCost([WOOD => 2]),
      ],
    ]);

    $actions = [
      $stablesFlow,
    ];

    if ($this->getPlayer()->getRoomType() == 'roomWood') {
      $actions[] = [
        'action' => CONSTRUCT,
      ];
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->unflagCardNode(),
        $this->payNode([WOOD => $wood-5]),
        [
          'type' => NODE_XOR,
          'childs' => $actions,
        ],
        $this->flagCardNode(),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'bumpUsed',
          ],
        ],
      ],
    ];
  }

  public function bumpUsed()
  {
    $this->incStats('used');
  }

  public function isIndependentBumpUsed()
  {
    return true;
  }
}

