<?php
namespace AGR\Cards\B;

class B41_Hauberg extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B41_Hauberg';
    $this->name = clienttranslate('Hauberg');
    $this->deck = 'B';
    $this->number = 41;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Alternate placing 2 <WOOD> and 1 <PIG> on the next 4 round spaces. You decide what to start with. At the start of these rounds, you get the goods.'
      ),
    ];
    $this->cost = [
      FOOD => 3,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_XOR,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'childs' => [
            $this->futureMeeplesNode([WOOD => 2], ['+1', '+3']),
            $this->futureMeeplesNode([PIG => 1], ['+2', '+4'])
          ]
        ],
        [
          'type' => NODE_SEQ,
          'childs' => [
            $this->futureMeeplesNode([PIG => 1], ['+1', '+3']),
            $this->futureMeeplesNode([WOOD => 2], ['+2', '+4'])
          ]
        ],
      ]
    ];
  }  
}
