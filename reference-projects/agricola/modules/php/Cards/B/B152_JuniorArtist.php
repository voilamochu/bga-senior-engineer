<?php
namespace AGR\Cards\B;
use AGR\Managers\Farmers;
use AGR\Managers\ActionCards;
use AGR\Helpers\CardRulings;

class B152_JuniorArtist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B152_JuniorArtist';
    $this->name = clienttranslate('Junior Artist');
    $this->deck = 'B';
    $this->number = 152;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Day Laborer__ action space, you can pay 1 <FOOD> to use an unoccupied __Traveling Players__ or __Lessons__ action space with the same person.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'JUNIOR_ARTIST_JOB_CONTRACT',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') && $event['actionCardType'] == 'DayLaborer';
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    if ($player->hasPlayedCard('C23_JobContract')) {
      $farmers = Farmers::getOnCard('ActionLessons', $player->getId());
      foreach ($farmers as $farmer) {
        if ($farmer['state'] == REMOVE) {
          return;
        }
      }
    }

    $childs = [];
    $farmer = Farmers::getOnCard('ActionDayLaborer', $player->getId())->last();

    foreach (['ActionLessons4', 'ActionLessons', 'ActionTravelingPlayers'] as $space) {
      if ($this->isDoable($space)) {
        $childs[] = $this->useActionSpaceNode($space, $farmer);
      }
    }
    
    if ($childs == []) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->payNode([FOOD => 1]),
        [
          'type' => NODE_XOR,
          'childs' => $childs,
        ],
      ],
    ];
  }

  public function isDoable($cId)
  {
    $player = $this->getPlayer();
    $card = ActionCards::get($cId);

    return $card->canBePlayed($player, null, true)
      || in_array($cId, $player->computeModifiedPlacementConstraints());
  }
}
