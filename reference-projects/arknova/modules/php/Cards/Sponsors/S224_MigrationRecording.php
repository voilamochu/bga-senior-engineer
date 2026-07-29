<?php
namespace ARK\Cards\Sponsors;

class S224_MigrationRecording extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S224_MigrationRecording';
    $this->number = 224;
    $this->name = clienttranslate('Migration Recording');
    $this->lvl = 4;
    $this->categories = [SCIENCE];
    $this->prerequisites = [
      SCIENCE => 1,
    ];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 X-token')],
      PASSIVE => [
        clienttranslate(
          'Each time you support a Release into the Wild conservation project (cards 113–122), you gain 1 additional conservation point. You may support each release conservation project multiple times by releasing another animal of the required species. However, you can still only place 1 player token per Association action on any given conservation project. Even if you place multiple player tokens on the same conservation project, each of these tokens counts as 1 separate conservation project that you supported for other cards.'
        ),
      ],
    ];
  }

  public function getImmediate()
  {
    return [[XTOKEN => 1]];
  }
}
