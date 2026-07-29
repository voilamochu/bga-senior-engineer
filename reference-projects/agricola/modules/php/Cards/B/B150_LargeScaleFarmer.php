<?php
namespace AGR\Cards\B;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Helpers\CardRulings;

class B150_LargeScaleFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B150_LargeScaleFarmer';
    $this->name = clienttranslate('Large-Scale Farmer');
    $this->deck = 'B';
    $this->number = 150;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Farm Expansion__ or __Major Improvement__ action space while the other is unoccupied, you can pay 1 <FOOD> to use that other space with the same person.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'ONE_JUMP_PER_TURN',
      'LANDS_ON_SECOND_SPACE',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      ($event['actionCardType'] == 'FarmExpansion' || $event['actionCardType'] == 'MajorImprovement') &&
      !$this->isFlagged();
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $space = $event['actionCardId'];
    $other = $space == 'ActionFarmExpansion' ? 'ActionMajorImprovement' : 'ActionFarmExpansion';
    $farmer = Farmers::getOnCard($space, $player->getId())->last();

    if (!$this->isDoable($other)) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->payNode([FOOD => 1]),
        $this->flagCardNode(),
        $this->useActionSpaceNode($other, $farmer),
        $this->unflagCardNode(),
      ],
    ];
  }

  public function isDoable($cId)
  {
    $player = $this->getPlayer();
    $card = ActionCards::get($cId);

    return $card->canBePlayed($player, null, true);
  }
}
