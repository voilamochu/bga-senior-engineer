<?php
namespace AGR\Cards\C;

class C24_BedintheGrainField extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C24_BedintheGrainField';
    $this->name = clienttranslate('Bed in the Grain Field');
    $this->deck = 'C';
    $this->number = 24;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'At the start of the next harvest, you get a __Family Growth__ action if you have room for the newborn.'
      ),
    ];
    $this->prerequisite = clienttranslate('1 Grain Field');
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('Only works in the next harvest after it is played.'),
      clienttranslate('The newborn must be fed.'),
    ];
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) < 1) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'StartHarvest' &&
      !$this->isFlagged();
  }

  public function onPlayerStartHarvest($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'childs' => [
            [
              'action' => WISHCHILDREN,
              'args' => ['constraints' => ['freeRoom'], 'insideHouse' => true],
              'source' => $this->name,
            ],
          ],
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'markCard',
          ]
        ],
      ]
    ];    
  }

  public function markCard()
  {
    $this->setInfobox("✓");
  }
}
