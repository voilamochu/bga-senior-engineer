<?php
namespace ARK\Cards\Sponsors;

class S227_WazaSpecialAssignment extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S227_WazaSpecialAssignment';
    $this->number = 227;
    $this->name = clienttranslate('Waza Special Assignment');
    $this->lvl = 6;
    $this->prerequisites = [REPUTATION => 6];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Decide whether you want to focus on small or large animals for the rest of the game and place a player token from your supply onto the box with the hand icon beneath the Animal type you chose.'
        ),
        \clienttranslate(' Get an Animal card of the chosen type and add it to your hand.'),
      ],
      PASSIVE => [
        clienttranslate(
          'Every time you play an animal of the chosen type, the appeal of your zoo increases by 2 (for small animals) or 4 respectively (for large animals).'
        ),
        clienttranslate('You can no longer play animals of the non-selected type for the rest of the game.'),
        clienttranslate('The still visible hand icon beneath this animal type is there to remind you.'),
        clienttranslate(
          'Small animals are animals that require a standard enclosure of 1 or 2 spaces, as well as Petting Zoo animals.'
        ),
        clienttranslate('Large animals require a standard enclosure of 4 or 5 spaces.'),
        clienttranslate(
          'This card has no effect on animals that require a standard enclosure of 3 spaces. You may still play them, but you do not gain a bonus.'
        ),
      ],
    ];
  }

  public function getImmediate()
  {
    return [[WAZA_SPECIAL => 1]];
  }
}
