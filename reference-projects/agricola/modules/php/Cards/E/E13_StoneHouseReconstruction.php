<?php
namespace AGR\Cards\E;

class E13_StoneHouseReconstruction extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E13_StoneHouseReconstruction';
    $this->name = clienttranslate('Stone House Reconstruction');
    $this->deck = 'E';
    $this->author = 'pain';
    $this->number = 13;
    $this->category = 'FARMYARD_-_HOUSE_BUILDING_OR_RENOVATION';
    $this->desc = [
      clienttranslate(
        'At any time, you can renovate your clay house to a stone house without placing a person. (You must pay the normal renovation cost.)'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      STONE => 1,
    ];
    $this->implemented = true;
  }

  public function isListeningTo($event)
  {
    return $this->isAnytime($event) && $this->getPlayer()->getRoomType() == 'roomClay'&& !$this->isFlagged();
  }
  
  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        [
          'action' => RENOVATION,
          'pId' => $this->pId,
        ],
        $this->unflagCardNode(),
      ]
    ];
  }
}
