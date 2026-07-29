<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Core\Globals;
use AGR\Helpers\CardRulings;

class E134_Omnifarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E134_Omnifarmer';
    $this->name = clienttranslate('Omnifarmer');
    $this->deck = 'E';
    $this->author = 'apeirox';
    $this->number = 134;
    $this->category = 'BONUS_POINTS';
    $this->desc = [
      clienttranslate(
        'Each harvest, you can place 1 harvested crop or 1 newborn animal on this card, irretrievably. Once this game, if there are 2/3/4/5 different goods on this, you get 3/5/7/9 bonus <SCORE>.'
      ),
    ];
    $this->players = '3+';
    $this->holder = true;
    $this->extraVp = true;

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'NEWBORN_ANIMAL_NEEDS_SPACE',
      ]),
      [
      clienttranslate('Once goods are on this card, they are unusable except to score points with this card.'),
      ]
    );
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'AfterHarvest')
    || ($this->getGoods() != [] && !$this->isFlagged() &&
    (($this->isActionEvent($event, 'Reorganize') && $event['trigger'] == HARVEST && Globals::getHarvest() == true)
    || ($this->isActionEvent($event, 'Reap') && ($event['trigger'] ?? null) == HARVEST)));  
  }

  public function onPlayerAfterHarvest($player)
  {
    return $this->unflagCardNode();
  }

  public function onPlayerAfterReap($player, $event)
  {
    $goods = $this->getGoods();
    $available = [];
    $harvestedTypes = array_unique(array_column($event['crops'], 'type'));
    foreach ($goods as $good) {
      if (in_array($good, CROPS) && in_array($good, $harvestedTypes)) {
        $available[] = $good;
      }
    }
    if(count($available)<1){
      return;
    }
    return $this->payGood($player,$available);
  }

  public function onPlayerAfterReorganize($player, $event)
  {
    $goods = $this->getGoods();
    $available = [];
    $animals = [SHEEP, PIG, CATTLE];
    foreach ($goods as $good) {
      $n = 3;
      if($player->hasPlayedCard('E84_DollysMother') && $good == SHEEP){
        $n = 2;
      }
      if (in_array($good,$animals) && $player->countAnimalsOnBoard()[$good]>=$n) {
        $available[] = $good;
      }
    }
    if(count($available)<1){
      return;
    }
    return $this->payGood($player,$available);
  }

  public function payGood($player,$goods)
  {
    $childs = [];
    
    foreach ($goods as $good) {
      $childs[] = [
        'type' => NODE_SEQ,
        'childs' => [
          $this->flagCardNode(),
          $this->payNode([$good => 1]),
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'addGood',
              'args' => [$good],
            ],
          ],
        ],
      ];
    }

    return [
      'type' => NODE_XOR,
      'optional' => true,
      'customDescription' => 'Pay a good to this card',
      'childs' => $childs,
    ];
  }

  public function getGoods()
  {
    $goods = [GRAIN, VEGETABLE, SHEEP, PIG, CATTLE];
    $available = [];

    foreach ($goods as $good) {
      if ($meeples = Meeples::getResourcesOnCard($this->id, null, $good)->empty()) {
        $available[] = $good;
      }
    }
    return $available;
  }

  public function addGood($type) {
    $created = Meeples::createResourceOnCard($type, $this->id);
    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function computeBonusScore()
  {
    $num = 5-count($this->getGoods());
    $scoreMap = [0, 0, 3, 5, 7, 9];
    $toGain = $scoreMap[$num] ?? 9;

    $this->addBonusScoringEntry($toGain);
  }

}
