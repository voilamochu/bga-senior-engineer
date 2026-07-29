<?php
namespace AGR\Cards\B;

use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class B19_MoldboardPlow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B19_MoldboardPlow';
    $this->name = clienttranslate('Moldboard Plow');
    $this->deck = 'B';
    $this->number = 19;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Place 2 field tiles on this card. Twice this game, when you use the __Farmland__ action space, you can also plow 1 field from this card.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->holder = true;
  }

  public function onBuy($player)
  {
    $created = Meeples::createResourceInLocation('field', $this->id, $player->getId(), null, null, 2);
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

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => PLOW,
          'args' => ['source' => $this->name, 'cardId' => $this->id],
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
  }

  public function removeField()
  {
    $field = Meeples::getResourcesOnCard($this->id)->first();
    Meeples::DB()->delete($field['id']);
    Notifications::silentDestroy([$field]);
  }
}
