<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class C72_FestivalPlanning extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C72_FestivalPlanning';
    $this->name = clienttranslate('Festival Planning');
    $this->deck = 'C';
    $this->number = 72;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, immediately carry out the field phase on your farmyard only (this is not a harvest). Afterwards, you get a __Major or Minor Improvement__ action.'
      ),
    ];
    $this->cost = [
      FOOD => '1',
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = CardRulings::fromKeys([
      'PRIVATE_FIELD_PHASE',
    ]);
  }

  public function onBuy($player)
  {
    $childs = [];

    // Reap if there are harvestable crops
    $crops = $player->board()->getHarvestCrops();
    if (count($crops) > 0) {
      $childs[] = [
        'action' => REAP,
        'pId' => $player->getId(),
        'source' => $this->name,
        'args' => [
          'trigger' => PRIVATE_FIELD_PHASE,
        ],
      ];
    }

    // Then take a Major or Minor Improvement action
    $childs[] = Utils::wrapOptional([
      'action' => IMPROVEMENT,
      'pId' => $player->getId(),
      'source' => $this->name,
      'args' => [
        'types' => [MINOR, MAJOR],
        'actionType' => 'MajorOrMinor',
      ],
    ]);

    return [
      'type' => NODE_SEQ,
      'childs' => $childs,
    ];
  }

}
