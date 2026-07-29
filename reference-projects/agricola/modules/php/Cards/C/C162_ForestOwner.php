<?php
namespace AGR\Cards\C;
use AGR\Managers\Players;
use AGR\Core\Engine;

class C162_ForestOwner extends \AGR\Models\PlayerActionCard
{
  protected $type = OCCUPATION;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C162_ForestOwner';
    $this->name = clienttranslate('Forest Owner');
    $this->deck = 'C';
    $this->number = 162;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'This card is an action space for all. If another player uses it, they get 3 <WOOD> and must give you 1 <WOOD> from the general supply. If you use it, you get 4 <WOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->flow = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'method' => 'activate',
        'cardId' => $this->id,
        'args' => [],
      ],
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function activate()
  {
    $player = Players::getActive()->getId();
    $owner = $this->getPlayer()->getId();
    $usedByOwner = $player == $owner ? 1 : 0;
    $flow = [];

    $flow[] = $this->gainNode([WOOD => 3 + $usedByOwner], $player);

    if (!$usedByOwner) {
      $flow[] = $this->gainNode([WOOD => 1]);
    }

    Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $flow]);
  }
}
