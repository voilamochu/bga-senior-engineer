<?php
namespace AGR\Cards\A;
use AGR\Helpers\CardRulings;

// TODO: technical reading of the rules allows this card's effect to be used while the baking window is open
// option 1: reconsider implementation
// option 2: change the wording to say "after"
// option 3: ignore

class A30_BakingSheet extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A30_BakingSheet';
    $this->name = clienttranslate('Baking Sheet');
    $this->deck = 'A';
    $this->number = 30;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take a __Bake Bread__ action, you can use this card to exchange exactly 1 <GRAIN> for 2 <FOOD> and 1 bonus <SCORE>.'
      ),
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('No Grain Field');
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'MUST_BAKE_NORMALLY',
    ]);
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) > 0) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Exchange') && $event['trigger'] == BREAD;
  }

  public function onPlayerAfterExchange($player, $event)
  {
    return $this->payGainNode([GRAIN => 1], [FOOD => 2, SCORE => 1]);
  }
}
