<?php
namespace AGR\Cards\E;
use AGR\Helpers\CardRulings;

class E80_RockGarden extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E80_RockGarden';
    $this->name = clienttranslate('Rock Garden');
    $this->deck = 'E';
    $this->author = 'barbarossa89';
    $this->number = 80;
    $this->category = 'BUILDING_RESOURCES_-_STONE';
    $this->desc = [
      clienttranslate(
        'You can only plant <STONE> on this card. Plant as though it were 3 fields, but it is considered 1 field. Sow and harvest <STONE> on this card as you would vegetables.'
      ),
    ];
    $this->holder = true;
    $this->field = true;

    $this->rulings = CardRulings::fromKeys([
      'CAN_SOW_MULTIPLE',
    ]);
  }

  public function getFieldDetails()
  {
    return [
      'constraints' => STONE,
    ];
  }
}
