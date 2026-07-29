<?php

namespace AGR\Cards\A;

use AGR\Managers\PlayerCards;
use AGR\Models\Occupation;
use const NODE_OR;

class A131_CraftTeacher extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A131_CraftTeacher';
    $this->name = clienttranslate('Craft Teacher');
    $this->deck = 'A';
    $this->number = 131;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time after you build the major improvement __Joinery__, __Pottery__, and __Basketmaker\'s Workshop__, you can play up to 2 occupations without paying an occupation cost.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong3or4p = true;

    $this->rulings = [
      clienttranslate('This card triggers every time you build one of the mentioned major improvements in any way.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement') &&
      PlayerCards::get($event['cardId'])->getType() == MAJOR;
  }

  public function onPlayerAfterImprovement($player, $event)
  {
    $card = PlayerCards::get($event['cardId']);
    if (!in_array($card->getId(), ['Major_Joinery', 'Major_Pottery', 'Major_Basket'])) {
      return;
    }
    return [
      'countAsUse' => true,
      'type' => NODE_OR,
      'optional' => true,
      'childs' => [
        [
          'action' => OCCUPATION,
          'source' => $this->name,
          'pId' => $this->pId,
          'args' => [
            'cost' => [],
          ],
        ],
        [
          'action' => OCCUPATION,
          'source' => $this->name,
          'pId' => $this->pId,
          'args' => [
            'cost' => [],
          ],
        ],
      ],
    ];
  }
}
