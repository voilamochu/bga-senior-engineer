<?php
namespace AGR\Cards\C;
use AGR\Managers\Meeples;

class C132_TimberShingleMaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C132_TimberShingleMaker';
    $this->name = clienttranslate('Timber Shingle Maker');
    $this->deck = 'C';
    $this->number = 132;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you renovate to stone, you can place up to 1 <WOOD> from your supply in each of your rooms. During scoring, each such <WOOD> is worth 1 bonus <SCORE>.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation');
  }

  public function onPlayerAfterRenovation($player, $event)
  {     
    if ($this->getPlayer()->getRoomType() == 'roomStone') {
      $childs = [];
      for ($i = 1; $i <= $player->countRooms(); $i++) {
        $childs[] = [
          'type' => NODE_SEQ,
          'childs' => [
            $this->payGainNode([WOOD => $i], [SCORE => $i], null, false),
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'markFarmyardPlacement',
              ],
            ],
          ],
        ];
      }
      return  [
        'type' => NODE_XOR,
         'optional' => true,
         'doableRequiresChild' => true,
        'childs' => $childs,
      ];
    }
  }

  public function markFarmyardPlacement()
  {
    Meeples::markFarmyardGoodPlacement($this->pId);
  }

  public function isIndependentMarkFarmyardPlacement()
  {
    return true;
  }
}
