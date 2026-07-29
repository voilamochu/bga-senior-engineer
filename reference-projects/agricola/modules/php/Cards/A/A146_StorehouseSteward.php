<?php
namespace AGR\Cards\A;

class A146_StorehouseSteward extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A146_StorehouseSteward';
    $this->name = clienttranslate('Storehouse Steward');
    $this->deck = 'A';
    $this->number = 146;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take exactly 2/3/4/5 <FOOD> from a food accumulation space, you also get 1 <STONE>/<REED>/<CLAY>/<WOOD>. (If you take 6 or more <FOOD>, you do not get a bonus good).'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, FOOD);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    $args = ['pId' => $this->pId];		  
    $n = count($event['meeples']);

	if ($n == 2) {
      $args[STONE] = 1;
	} 
	elseif ($n == 3) {
      $args[REED] = 1;
	}
	elseif ($n == 4) {
      $args[CLAY] = 1;
	}
	elseif ($n == 5) {
      $args[WOOD] = 1;
	} 
	else {
	  return null;
	}

    return $this->gainNode($args);  
  }
}
