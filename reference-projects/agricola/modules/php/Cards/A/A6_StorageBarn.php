<?php
namespace AGR\Cards\A;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Models\Player;
use AGR\Helpers\Utils;

class A6_StorageBarn extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A6_StorageBarn';
    $this->name = clienttranslate('Storage Barn');
    $this->deck = 'A';
    $this->number = 6;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If you have the Well, Joinery, Pottery, and/or Basketmaker\'s Workshop, you immediately get 1 <STONE>, 1 <WOOD>, 1 <CLAY>, and/or 1 <REED>, respectively.'
      ),
    ];
    $this->passing = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
	if ($player->hasPlayedCard('Major_Well')) {
		$args[STONE] = 1;
	}
	if ($player->hasPlayedCard('Major_Joinery')) {
		$args[WOOD] = 1;			
	}
	if ($player->hasPlayedCard('Major_Pottery')) {
		$args[CLAY] = 1;			
	}
	if ($player->hasPlayedCard('Major_Basket')) {
		$args[REED] = 1;			
	}

    if (isset($args)) {
      return $this->gainNode($args);
    }
  }
}
