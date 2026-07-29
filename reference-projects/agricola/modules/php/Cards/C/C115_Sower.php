<?php
namespace AGR\Cards\C;

use AGR\Helpers\UsedText;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;

class C115_Sower extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C115_Sower';
    $this->name = clienttranslate('Sower');
    $this->deck = 'C';
    $this->number = 115;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build a major improvement, place 1 <REED> from the general supply on this card. At any time, you can move the <REED> to your supply or exchange it for a __Sow__ action.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
    $this->usedText = UsedText::get('SOWS');
  }

  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'Improvement') && PlayerCards::get($event['cardId'])->hasType(MAJOR)) ||
      ($this->isAnytime($event) && Meeples::getResourcesOnCard($this->id, null, REED)->count() > 0);
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $meeple = $this->getNextResource();

    return [
      'countAsUse' => true,
      'type' => NODE_XOR,
      'childs' => [
        $this->receiveNode($meeple['id'], true),
        [
          'type' => NODE_SEQ,
          'childs' => [
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'discardReed'
              ]
            ],
            [
              'optional' => true,
              'action' => SOW
            ]
          ]
        ],
      ]
    ];
  }

  public function onPlayerAfterImprovement($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'addReed',
      ]
    ];
  }

  public function getDiscardReedDescription()
  {
    return [
      'log' => clienttranslate('Discard ${resources_desc} from ${card}'),
      'args' => [
        'resources_desc' => '1 <REED>',
        'card' => $this->name,
      ],
    ];
  }

  public function discardReed()
  {
    $meeple = $this->getNextResource();
    Meeples::DB()->delete($meeple['id']);
    Notifications::silentKill([$meeple]);
  }

  public function getAddReedDescription()
  {
    return [
      'log' => clienttranslate('Add ${resources_desc} to ${card}'),
      'args' => [
        'resources_desc' => '1 <REED>',
        'card' => $this->name,
      ],
    ];
  }

  public function addReed()
  {
    $player = $this->getPlayer();

    $created = Meeples::createResourceInLocation(REED, $this->id, $player->getId(), null, null, 1);
    Notifications::accumulate(Meeples::getMany($created), true);
  }
}
