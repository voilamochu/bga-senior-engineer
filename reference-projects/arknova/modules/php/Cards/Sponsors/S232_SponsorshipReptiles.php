<?php
namespace ARK\Cards\Sponsors;

class S232_SponsorshipReptiles extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S232_SponsorshipReptiles';
    $this->number = 232;
    $this->name = clienttranslate('Sponsorship: Reptiles');
    $this->lvl = 3;
    $this->prerequisites = [
      REPTILE => 1,
    ];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for each reptile icon in your zoo.')],
      INCOME => [
        clienttranslate(
          'Gain 3 money for 1 to 2 reptile icons, 6 money for 3 to 4 reptile icons or 9 money for 5 or more reptile icons in your zoo in the income phase of each break.'
        ),
      ],
    ];
  }

  public function getImmediate()
  {
    $player = $this->getPlayer();
    $icons = $player->countCardIcons();
    $n = $icons[REPTILE];
    return $n == 0 ? [] : [[APPEAL => $n]];
  }

  public function getIncome()
  {
    $player = $this->getPlayer();
    $icons = $player->countCardIcons();
    $n = $icons[REPTILE];
    $map = [0 => 0, 1 => 3, 2 => 3, 3 => 6, 4 => 6];
    $money = $map[$n] ?? 9;
    return [[MONEY => $money]];
  }
}
