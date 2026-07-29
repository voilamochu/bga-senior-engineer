<?php
namespace AGR\Cards\D;
use AGR\Core\Engine;

class D137_TradeTeacher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D137_TradeTeacher';
    $this->name = clienttranslate('Trade Teacher');
    $this->deck = 'D';
    $this->number = 137;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use a __Lesson__ action space, you can buy up to 2 different goods: <GRAIN>, <STONE>, <SHEEP>, and <PIG> for 1 <FOOD> each; <CATTLE> and <VEGETABLE> for 2 food each.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong3or4p = true;
  }

  public function isListeningTo($event)
  {
    if (!$this->isActionEvent($event, 'PlaceFarmer')) {
      return false;
    }

    return $event['actionCardType'] == 'Lessons';
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'takeResource',
        'args' => [],
      ],
    ];
  }

  public function getTakeResourceDescription()
  {
    return clienttranslate('Buy goods');
  }

  public function argsTakeResource()
  {
    return [
      'cardId' => $this->id,
      'nb' => 2,
      'description' => clienttranslate('${actplayer} may buy goods (Trade Teacher)'),
      'descriptionmyturn' => clienttranslate('Select up to 2 different goods to buy (Trade Teacher)'),
    ];
  }

  public function actTakeResource($resources)
  {
    $map = [
      GRAIN => 1,
      STONE => 1,
      SHEEP => 1,
      PIG => 1,
      CATTLE => 2,
      VEGETABLE => 2,
    ];
    $toGain = [];
    $resources = array_unique($resources);
    if (count($resources) > 2) {
      throw new \BgaVisibleSystemException('You can only buy up to 2 different goods.');
    }

    $food = 0;
    foreach ($resources as $resource) {
      $toGain[$resource] = 1;
      $food += $map[$resource];
    }

    $flow = $this->payGainNode([FOOD => $food], $toGain, null, false);
    Engine::insertAsChild($flow);
  }
}
