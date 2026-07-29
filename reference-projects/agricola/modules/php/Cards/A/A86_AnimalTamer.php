<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class A86_AnimalTamer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A86_AnimalTamer';
    $this->name = clienttranslate('Animal Tamer');
    $this->deck = 'A';
    $this->number = 86;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get your choice of 1 <WOOD> or 1 <GRAIN>. Instead of just 1 animal total, you can keep any 1 animal in each room of your house.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = CardRulings::fromKeys([
      'NEGATED_BY_MILKING_PLACE',
    ]);
  }

  public function onBuy($player)
  {
    $this->refreshDropZones();

    return [
      'type' => NODE_XOR,
      'childs' => [$this->gainNode([WOOD => 1]), $this->gainNode([GRAIN => 1])],
    ];
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    Utils::filter($args['zones'], function ($zone) {
      return $zone['type'] != 'room';
    });

    foreach ($player->board()->getRooms() as $room) {
      $args['zones'][] = [
        'type' => 'room',
        'capacity' => 1,
        'locations' => [
          [
            'x' => $room['x'],
            'y' => $room['y'],
          ],
        ],
      ];
    }
  }
}
