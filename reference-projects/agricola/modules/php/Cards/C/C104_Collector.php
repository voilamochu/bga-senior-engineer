<?php
namespace AGR\Cards\C;

use AGR\Core\Engine;
use AGR\Core\Notifications;

class C104_Collector extends \AGR\Models\PlayerActionCard
{
  protected $type = OCCUPATION;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C104_Collector';
    $this->name = clienttranslate('Collector');
    $this->deck = 'C';
    $this->number = 104;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'This card is an action space for you only. When you use it for the 1st/2nd/3rd/4th time, you get 1 <BEGGING> marker and 6/7/8/9 different goods of your choice.'
      ),
    ];
    $this->players = '1+';
    $this->resMap = [1 => 6, 2 => 7, 3 => 8, 4 => 9];
    $this->privateSpace = true;

    $this->flow = [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'flagUse',
            'args' => [],
          ],
        ],
        [
          'action' => GAIN,
          'args' => [BEGGING => 1],
          'source' => $this->name,
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'takeResource',
            'args' => [],
          ],
        ],
      ],
    ];
  }

  public function getTypeStr()
  {
    return clienttranslate('occupation');
  }

  public function canBePlayed($player, $onlyCheckSpecificPlayer = null, $ignoreResources = false, $ignoreDoable = false)
  {
    return ($this->getExtraDatas('used') ?? 0) < 4 && parent::canBePlayed($player, $onlyCheckSpecificPlayer, $ignoreResources);
  }

  public function flagUse()
  {
    $use = $this->getExtraDatas('used') ?? 0;
    $use++;
    $this->setExtraDatas('used', $use);
  }

  public function argsTakeResource()
  {
    return [
      'cardId' => $this->id,
      'nb' => $this->resMap[$this->getExtraDatas('used')],
      'description' => clienttranslate('${actplayer} must select different goods to get (Collector)'),
      'descriptionmyturn' => clienttranslate('Select ${nb} different goods to get (Collector)'),
    ];
  }

  public function actTakeResource($resources)
  {
    $toGain = [];
    $resources = array_unique($resources);
    if (count($resources) != $this->resMap[$this->getExtraDatas('used')]) {
      throw new \BgaVisibleSystemException('Not enough or too much resource. Should not happen');
    }

    foreach ($resources as $resource) {
      $toGain[$resource] = 1;
    }
    Engine::insertAsChild(['action' => GAIN, 'args' => $toGain, 'cardId' => $this->id, 'source' => $this->name]);
  }
}
