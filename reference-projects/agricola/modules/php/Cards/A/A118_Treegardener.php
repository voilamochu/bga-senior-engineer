<?php
namespace AGR\Cards\A;

class A118_Treegardener extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A118_Treegardener';
    $this->name = clienttranslate('Treegardener');
    $this->deck = 'A';
    $this->number = 118;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, you get 1 <WOOD> and you can buy up to 2 additional <WOOD> for 1 <FOOD> each.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFieldPhase';
  }

  public function onPlayerHarvestFieldPhase($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
	    $this->gainNode([WOOD => 1]),
		[
          'type' => NODE_XOR,
		  'optional' => true,
		  'doableRequiresChild' => true,
          'childs' => [
            $this->payGainNode([FOOD => 1], [WOOD => 1], clienttranslate("Treegardener's effect"), false),
            $this->payGainNode([FOOD => 2], [WOOD => 2], clienttranslate("Treegardener's effect"), false),
          ]
		]
	  ]
    ];
  }
}
