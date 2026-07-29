<?php

namespace AGR\Cards\A;

use AGR\Models\AbtractCard;
use AGR\Models\MinorImprovement;
use AGR\Helpers\CardRulings;

class A21_FamilyFriendHome extends MinorImprovement

// TODO: test with Building Tycoon
// NOTE: may have noteworthy interactions with other simultaneously triggered grow actions

{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A21_FamilyFriendHome';
    $this->name = clienttranslate('Family Friendly Home');
    $this->deck = 'A';
    $this->number = 21;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you take a __Build Rooms__ action while having more rooms than people already, you also get a __Family Growth__ action and 1 <FOOD>.'
      ),
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'EXACTLY_ONE_GROWTH_ACTION',
      ]),
      [
      clienttranslate('This card is only triggered by a __Build Rooms__ action exactly, not cards that simply let you build a room such as __Cottager__ (B087).'),
      ]
    );
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct', 'player');
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    $farmers = $this->getPlayer()->countFarmers();
    $oldRoomCount = $event['oldRoomCount'];

    if ($oldRoomCount > $farmers && $farmers < 5 && $event['trueAction']) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->gainNode([FOOD => 1]),
          [
            'action' => WISHCHILDREN,
            'cardId' => $this->id,
            'args' => ['constraints' => ['freeRoom'], 'cardLocation' => $this->id],
            'source' => $this->name
          ],
        ]
      ];
    }
  }
}
