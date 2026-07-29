<?php
namespace AGR\Cards\D;
use AGR\Managers\Players;

class D157_PartyOrganizer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D157_PartyOrganizer';
    $this->name = clienttranslate('Party Organizer');
    $this->deck = 'D';
    $this->number = 157;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'As soon as the next player but you gains their 5th person, you immediately get 8 <FOOD> (not retroactively). During scoring, if only you have 5 people, you get 3 bonus <SCORE>.'
      ),
    ];
    $this->players = '4+';
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'WishChildren', 'opponent') && !$this->isFlagged();
  }
  
  public function onOpponentAfterWishChildren($player, $event) 
  {
    if ($event['farmers'] == 5) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->gainNode([FOOD => 8]),
          $this->flagCardNode(),
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
  }
  
  public function computeBonusScore()
  {
    foreach (Players::getAll() as $player) {
      if ($player->countFarmers() == 5 && ($this->pId != $player->getId())) {
        return;
      }
    }
    
    if (Players::get($this->pId)->countFarmers() == 5) {
      $this->addBonusScoringEntry(3);
    }
  }

  public function markCard()
  {
    $this->setInfobox("✓");
  }
}
