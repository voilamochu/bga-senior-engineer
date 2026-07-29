<?php
namespace ARK\Cards\Sponsors;

class S228_WazaSmallAnimalProgram extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S228_WazaSmallAnimalProgram';
    $this->number = 228;
    $this->name = clienttranslate('Waza Small Animal Program');
    $this->lvl = 5;
    $this->prerequisites = [REPUTATION => 3];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 2 money for each small animal in your zoo')],
      PASSIVE => [
        clienttranslate(
          'Every time you play only small animals during the Animals action, you may play 1 additional small animal from your hand at the normal cost. The normal rules for playing Animal cards apply.'
        ),
        clienttranslate(
          'After that, add 1 small animal from the display to your hand, if available (even if you did not play 1 additional small animal from your hand).'
        ),
        \clienttranslate('The Small Animal card does not have to be within reputation range.'),
        clienttranslate(
          'Small animals are animals that require a standard enclosure of 1 or 2 spaces, as well as Petting Zoo animals.'
        ),
      ],
    ];
  }

  public function getImmediate()
  {
    $n = $this->getPlayer()
      ->getPlayedAnimal()
      ->filter(function ($animal) {
        return $animal->isSmall();
      })
      ->count();

    return $n == 0 ? [] : [[MONEY => 2 * $n]];
  }
}
