<?php
namespace AGR\Cards\E;


class E131_MarketMaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E131_MarketMaster';
    $this->name = clienttranslate('Market Master');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 131;
    $this->category = 'ACTION_-_IMPROVEMENTS_OR_OCCUPATIONS';
    $this->desc = [
      clienttranslate(
        'Immediately after each time you place your last person in a round on the __Traveling Players__ accumulation space, you can play 1 occupation for an occupation cost of 1 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->implemented = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'TravelingPlayers', 'player', true);
  }

  public function onPlayerImmediatelyAfterPlaceFarmer($player, $args)
  {
    if ($player->hasFarmerAvailable()) {
      return;
    }
    return [
        'action' => OCCUPATION,
        'cardId' => $this->id,
        'optional' => true,
        'args' => ['max' => 1, 'cost' => [FOOD => 1]],
      ];
  }
}
