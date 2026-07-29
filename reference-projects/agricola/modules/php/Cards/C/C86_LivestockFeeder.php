<?php
namespace AGR\Cards\C;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class C86_LivestockFeeder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C86_LivestockFeeder';
    $this->name = clienttranslate('Livestock Feeder');
    $this->deck = 'C';
    $this->number = 86;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <GRAIN>. This card can hold 1 animal of any type for each <GRAIN> in your supply.'
      ),
    ];
    $this->players = '1+';
    $this->occupationPrerequisites = ['min' => 2];
    $this->animalHolder = true;
  }

  public function getInvalidAnimals($zone, $raiseException)
  {
    return [];
  }

  public function onBuy($player)
  {
    return $this->gainNode([GRAIN => 1]);
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    $grain = $player->countReserveResource(GRAIN);
    if ($grain > 0) {
      $args['zones'][] = [
        'type' => 'card',
        'card_id' => $this->id,
        'constraints' => [SHEEP, PIG, CATTLE],
        'capacity' => $grain,
        'locations' => [['type' => 'card', 'card_id' => $this->id]],
      ];
    }
  }
}
