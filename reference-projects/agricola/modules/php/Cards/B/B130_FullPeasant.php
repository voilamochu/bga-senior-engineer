<?php
namespace AGR\Cards\B;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Helpers\CardRulings;

class B130_FullPeasant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B130_FullPeasant';
    $this->name = clienttranslate('Full Peasant');
    $this->deck = 'B';
    $this->number = 130;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Grain Utilization__ or __Fencing__ action space while the other is unoccupied, you can pay 1 <FOOD> to use the other space with the same person.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'ONE_JUMP_PER_TURN',
      'LANDS_ON_SECOND_SPACE',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      ($event['actionCardType'] == 'GrainUtilization' || $event['actionCardType'] == 'Fencing') &&
      !$this->isFlagged();
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $space = $event['actionCardId'];
    $other = $space == 'ActionGrainUtilization' ? 'ActionFencing' : 'ActionGrainUtilization';
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
