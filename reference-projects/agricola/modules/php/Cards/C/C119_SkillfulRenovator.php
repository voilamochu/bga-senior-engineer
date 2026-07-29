<?php
namespace AGR\Cards\C;
use AGR\Managers\Farmers;

class C119_SkillfulRenovator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C119_SkillfulRenovator';
    $this->name = clienttranslate('Skillful Renovator');
    $this->deck = 'C';
    $this->number = 119;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <WOOD> and 1 <CLAY>. Each time after you renovate, you get a number of <WOOD> equal to the number of people you placed that round.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = [
      clienttranslate('If you renovate with your 3rd person placed in a round, this card triggers a payout of 3 <WOOD>.'),
      clienttranslate('Newborns do not count as people placed this round.'),
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1, CLAY => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation');
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    $played = $player->countPlacedFarmers();
    if ($played > 0) {
      return $this->gainNode([WOOD => $played]);
    }
  }
}
