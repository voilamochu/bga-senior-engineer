<?php
namespace AGR\Cards\E;

class E9_BarteringHut extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E9_BarteringHut';
    $this->name = clienttranslate('Bartering Hut');
    $this->deck = 'E';
    $this->author = '';
    $this->number = 9;
    $this->category = 'PASSING_-_ANIMAL';
    $this->desc = [
      clienttranslate(
        'Up to two times: Immediately spend any 2/3/4 building resources for 1 <SHEEP>/<PIG>/<CATTLE> from the general supply.'
      ),
    ];
    $this->passing = true;
  }
  public function onBuy($player){
    $childs = [];
    foreach ([SHEEP => 2, PIG => 3, CATTLE => 4] as $type => $turns) {
      $childAnimal = [];
           $childAnimal[] = $this->gainNode([$type => 1]);
          for($i = 1; $i <= $turns; $i++){
            $childAnimal[] = [
               'type' => NODE_XOR,
               'childs' => [
                 $this->payNode([WOOD => 1]),
                 $this->payNode([CLAY => 1]),
                 $this->payNode([REED => 1]),
                 $this->payNode([STONE => 1]),
               ],
            ];

          }
      $childs[] = [
        'type' => NODE_SEQ,
        'childs' => $childAnimal,
      ];
    }

   $oneTime =  [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => $childs,
    ]; 
   return [
      'type' => NODE_SEQ,
      'childs' => [
        $oneTime,
        $oneTime,
      ],
    ];
  }
}
