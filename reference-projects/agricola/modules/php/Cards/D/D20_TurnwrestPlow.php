<?php
namespace AGR\Cards\D;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class D20_TurnwrestPlow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D20_TurnwrestPlow';
    $this->name = clienttranslate('Turnwrest Plow');
    $this->deck = 'D';
    $this->number = 20;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Place 2 field tiles on this card. Each time you use the __Farmland__ or __Cultivation__ action space, you can also plow up to 2 fields from this card.'
      ),
    ];
    $this->cost = [
      WOOD => 3,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->holder = true;
  }

  public function onBuy($player)
  {
    $created = Meeples::createResourceInLocation('field', $this->id, $player->getId(), null, null, 2);
    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    return ($this->isActionCardEvent($event, 'Farmland') || $this->isActionCardEvent($event, 'Cultivation')) &&
      Meeples::getResourcesOnCard($this->id)->count() > 0;
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    if (!$this->isListeningTo($event)) {
      return null;
    }

    $node = [
      'type' => NODE_SEQ,
      'optional' => true,
      'customDescription' => clienttranslate('Plow additional fields'),
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

    if (Meeples::getResourcesOnCard($this->id)->count() == 2) {
      $tmp = $node;
      $tmp['customDescription'] = clienttranslate('Plow additional field (Turnwrest Plow)');
      $node['childs'][] = $tmp;
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
