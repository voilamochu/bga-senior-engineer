<?php
namespace AGR\Cards\C;
use AGR\Managers\Meeples;

class C154_TwinResearcher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C154_TwinResearcher';
    $this->name = clienttranslate('Twin Researcher');
    $this->deck = 'C';
    $this->number = 154;
    $this->category = POINTS_PROVIDER;
    $this->desc = [clienttranslate
      (
        'Each time you use one of the two accumulation spaces for the same type of good containing exactly the same number of goods, you can also buy 1 bonus <SCORE> for 1 <FOOD>.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak3or4p = true;

  }
  public function isListeningTo($event)
  {
    return ($this->isActionCardEvent($event, 'Forest')||$this->isActionCardEvent($event, 'Grove')||$this->isActionCardEvent($event, 'Copse')
      ||$this->isActionCardEvent($event, 'CopseAdd')||$this->isActionCardEvent($event, 'ClayPit')||$this->isActionCardEvent($event, 'Hollow')
      ||$this->isActionCardEvent($event, 'Hollow4')||$this->isActionCardEvent($event, 'Fishing')||$this->isActionCardEvent($event, 'TravelingPlayers')
      ||$this->isActionCardEvent($event, 'EasternQuarry') ||$this->isActionCardEvent($event, 'WesternQuarry'));
  }
  public function onPlayerPlaceFarmer($player, $event)
  {
     if($event['actionCardType'] == 'Forest'){
        if((Meeples::getResourcesOnCard ('ActionForest', null, WOOD)->count()==Meeples::getResourcesOnCard ('ActionGrove', null, WOOD)->count())
          ||(Meeples::getResourcesOnCard ('ActionForest', null, WOOD)->count()==Meeples::getResourcesOnCard ('ActionCopse', null, WOOD)->count())
          ||(Meeples::getResourcesOnCard ('ActionForest', null, WOOD)->count()==Meeples::getResourcesOnCard ('ActionCopseAdd', null, WOOD)->count()))
          return $this->payGainNode([FOOD => 1], [SCORE => 1]);
     }
      
     elseif($event['actionCardType'] == 'Grove'){
       if((Meeples::getResourcesOnCard ('ActionGrove', null, WOOD)->count()==Meeples::getResourcesOnCard ('ActionForest', null, WOOD)->count())
         ||(Meeples::getResourcesOnCard ('ActionGrove', null, WOOD)->count()==Meeples::getResourcesOnCard ('ActionCopse', null, WOOD)->count()))
         return $this->payGainNode([FOOD => 1], [SCORE => 1]);
     }
     elseif($event['actionCardType'] == 'Copse'){
       if((Meeples::getResourcesOnCard ('ActionCopse', null, WOOD)->count()==Meeples::getResourcesOnCard ('ActionGrove', null, WOOD)->count())
         ||(Meeples::getResourcesOnCard ('ActionCopse', null, WOOD)->count()==Meeples::getResourcesOnCard ('ActionForest', null, WOOD)->count()))
         return $this->payGainNode([FOOD => 1], [SCORE => 1]);
     }
     elseif($event['actionCardType'] == 'CopseAdd'){
       if(Meeples::getResourcesOnCard ('ActionCopseAdd', null, WOOD)->count()==Meeples::getResourcesOnCard ('ActionForest', null, WOOD)->count())
         return $this->payGainNode([FOOD => 1], [SCORE => 1]);
     }
    
     elseif($event['actionCardType'] == 'ClayPit'){
       if((Meeples::getResourcesOnCard ('ActionClayPit', null, CLAY)->count()==Meeples::getResourcesOnCard ('ActionHollow', null, CLAY)->count())
         ||(Meeples::getResourcesOnCard ('ActionClayPit', null, CLAY)->count()==Meeples::getResourcesOnCard ('ActionHollow4', null, CLAY)->count()))
         return $this->payGainNode([FOOD => 1], [SCORE => 1]);
     }
         
     elseif($event['actionCardType'] == 'Hollow'){
       if(Meeples::getResourcesOnCard ('ActionClayPit', null, CLAY)->count()==Meeples::getResourcesOnCard ('ActionHollow4', null, CLAY)->count())
          return $this->payGainNode([FOOD => 1], [SCORE => 1]);       
     }
     
     elseif(($event['actionCardType'] == 'Fishing')||($event['actionCardType'] == 'TravelingPlayers')){
       if(Meeples::getResourcesOnCard ('ActionFishing', null, FOOD)->count()==Meeples::getResourcesOnCard ('ActionTravelingPlayers', null, FOOD)->count())
         return $this->payGainNode([FOOD => 1], [SCORE => 1]);
     }
     elseif(($event['actionCardType'] == 'EasternQuarry')||($event['actionCardType'] == 'WesternQuarry')){
       if(Meeples::getResourcesOnCard ('ActionEasternQuarry', null, STONE)->count()==Meeples::getResourcesOnCard ('ActionWesternQuarry', null, STONE)->count())
         return $this->payGainNode([FOOD => 1], [SCORE => 1]);
     }
  }
}