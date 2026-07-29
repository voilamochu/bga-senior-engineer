<?php
namespace AGR\Cards\A;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Helpers\CardRulings;

class A129_Swagman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A129_Swagman';
    $this->name = clienttranslate('Swagman');
    $this->deck = 'A';
    $this->number = 129;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use the __Farm Expansion__ or __Grain Seeds__ action space, you can use the respective other space with the same person (even if it is occupied).'
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
      ($event['actionCardType'] == 'FarmExpansion' || $event['actionCardType'] == 'GrainSeeds') &&
      !$this->isFlagged();
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $space = $event['actionCardId'];
    $other = $space == 'ActionFarmExpansion' ? 'ActionGrainSeeds' : 'ActionFarmExpansion';
    $farmer = Farmers::getOnCard($space, $player->getId())->last();

    if (!$this->isDoable($other)) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
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

    return $card->canBePlayed($player, 'dummy', true);
  }
}
