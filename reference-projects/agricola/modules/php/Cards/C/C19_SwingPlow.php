<?php
namespace AGR\Cards\C;

use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class C19_SwingPlow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C19_SwingPlow';
    $this->name = clienttranslate('Swing Plow');
    $this->deck = 'C';
    $this->number = 19;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Place 4 field tiles on this card. Each time you use the __Farmland__ action space, you can also plow up to 2 fields from this card.'
      ),
    ];
    $this->cost = [
      WOOD => '3',
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
    $this->holder = true;
  }

  public function onBuy($player)
  {
    $created = Meeples::createResourceInLocation('field', $this->id, $player->getId(), null, null, 4);
    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Farmland') && Meeples::getResourcesOnCard($this->id)->count() > 0;
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    if (!$this->isListeningTo($event)) {
      return null;
    }

    $node = [
      'type' => NODE_SEQ,
      'optional' => true,
      'pId' => $this->pId,
      'customDescription' => clienttranslate('Plow additional fields'),
      'childs' => [
        [
          'action' => PLOW,
          'args' => ['source' => $this->name, 'cardId' => $this->id],
          'pId' => $this->pId,
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'removeField',
            'args' => [],
          ],
        ],
      ],
    ];

    if (Meeples::getResourcesOnCard($this->id)->count() >= 2) {
      $inner = $node;
      $inner['customDescription'] = clienttranslate('Plow additional field (Swing Plow)');
      $node['childs'][] = $inner;
    }

    return $node;
  }

  public function removeField()
  {
    $field = Meeples::getResourcesOnCard($this->id)->first();
    Meeples::DB()->delete($field['id']);
    Notifications::silentDestroy([$field]);
  }
}
