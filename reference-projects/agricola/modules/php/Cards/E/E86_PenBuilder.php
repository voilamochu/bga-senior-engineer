<?php
namespace AGR\Cards\E;
use AGR\Core\Notifications;

class E86_PenBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E86_PenBuilder';
    $this->name = clienttranslate('Pen Builder');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 86;
    $this->category = 'FARMYARD_-_PLACE_FOR_ANIMALS';
    $this->desc = [
      clienttranslate(
        'At any time, you can discard 1 <WOOD> from your supply. This card can hold two animals of any type for each <WOOD> discarded this way.'
      ),
    ];
    $this->players = '1+';
    $this->animalHolder = true;
  }

  public function getInvalidAnimals($zone, $raiseException)
  {
    return [];
  }

  public function isListeningTo($event)
  {
    return $this->isAnytime($event);
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->payNode([WOOD => 1]),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'incCounter'
          ]
        ],
      ]
    ];
  }

  public function getIncCounterDescription()
  {
    return [
      'log' => clienttranslate('Increase animal capacity of ${card}'),
      'args' => [
        'card' => $this->name,
      ],
    ];
  }

  public function incCounter()
  {
    $n = $this->getExtraDatas('discards') ?? 0;
    $this->setExtraDatas('discards', $n + 1);
    Notifications::updateDropZones($this->getPlayer());
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    $n = $this->getExtraDatas('discards') ?? 0;

    if ($n > 0) {
      $args['zones'][] = [
        'type' => 'card',
        'card_id' => $this->id,
        'constraints' => [SHEEP, PIG, CATTLE],
        'capacity' => $n*2,
        'locations' => [['type' => 'card', 'card_id' => $this->id]],
      ];
    }
  }
}
