<?php
namespace AGR\Cards\B;
use AGR\Helpers\CardRulings;

class B100_Clutterer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B100_Clutterer';
    $this->name = clienttranslate('Clutterer');
    $this->deck = 'B';
    $this->number = 100;
    $this->category = POINTS_PROVIDER;
    $this->desc = [clienttranslate
      (
        'During scoring, you get 1 bonus <SCORE> for each card played after this one that has "accumulation space(s)" in its text.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'ONLY_PLAYED_BY_OWNER',
    ]);
  }

 public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'Improvement') ||
      $this->isActionEvent($event, 'Occupation'));
  }
  public function onPlayerAfterImprovement($player, $event)
  {
    $ImprovmentArray=[ 'A15_CarpentersAxe','A17_ReclamationPlow','A23_StoneCompany','A35_SwimmingClass','A42_ForestLakeHut',
      'A46_ClawKnife','A49_NestSite','A50_MilkJug', 'A51_DriftNetBoat', 'A52_ThrowingAxe','A56_Basket',  'A66_FeedingDish',
      'A72_CalciumFertilizers','A77_Hod','A78_Canoe','A80_StoneTongs','A81_InterimStorage','A82_WorkCertificate',
      'B15_CarpentersBench','B17_ForestPlow','B24_Lasso','B28_ForestryStudies','B34_SpecialFood','B40_BreweryPond',  
      'B47_HerringPot','B48_ForestStone','B4_WoodPile','B51_DiggingSpade',  'B55_MaintenancePremium','B56_Brook',
      'B60_BrewingWater','B64_MillWheel', 'B67_HandTruck','B79_Corf','B81_Handcart','C15_Trellis','C21_HeartofStone',
      'C25_SteamMachine','C36_ClayDeposit','C39_StudioBoat','C42_RavenousHunger',  'C51_FishingNet','C58_Woodcraft',
      'C76_WoodCart','D16_WoodenWheyBucket','D19_PulverizerPlow','D39_TruffleSlicer','D54_TroutPool','D68_SmallBasket',
      'D73_SupplyBoat','E15_NailBasket','E19_OxGoad','E55_StoneWeir','E59_CombandCutter','E5_NightLoot','E66_BarnShed',
      'E75_StoneAxe'];
        
    if (in_array(($event['cardId']),$ImprovmentArray))
    {  
        return $this->gainNode([SCORE => 1]);
    }
  }
  public function onPlayerAfterOccupation($player, $event)
  {
   $OccupationArray=['A100_Curator','A103_Portmonger','A104_WoodHarvester','A107_Catcher','A108_MushroomCollector',
      'A115_ChiefForester','A116_WoodCutter','A121_ClayPuncher','A128_RiparianBuilder','A137_RiverineShepherd',
      'A138_Harpooner','A139_HollowWarden','A140_ShovelBearer','A142_Cordmaker','A146_StorehouseSteward','A147_AnimalDealer',
      'A149_HouseArtist','A150_Stagehand','A154_Paymaster','A155_Conjurer','A156_Buyer', 'A158_CulinaryArtist',
      'A159_JoineroftheSea','A160_Lutenist','A161_PatchCaretaker','A162_ForestTallyman','A164_WoodWorker','A172_BoatPainter',
      'A175_HollowGardener','A179_MountainShepherd','A91_ShiftingCultivator','A95_Angler','A96_TaskArtisan','B108_OvenFiringBoy',
      'B116_Shoreforester','B121_Geologist','B122_Mineralogist','B131_Equipper','B138_ForestGuardian','B143_ClayWarden',
      'B144_Collier','B146_Illusionist','B147_Huntsman','B155_ArtTeacher','B158_DistrictManager','B160_PubOwner','B161_Weakling',
      'B162_ForestClearer','B174_RiverbankGardener','B179_WildBoarHunter','B180_GameTeaser','B92_LittleStickKnitter',
      'C102_TreeGuard','C114_SoilScientist','C125_Nightworker','C130_OutskirtsDirector','C141_SheepProvider','C145_ForestReviewer',
      'C147_Cowherd','C148_MudWallower','C152_Puppeteer','C154_TwinResearcher','C156_HoofCaregiver','C158_ForestCampaigner',
      'C159_FishermansFriend','C163_MaterialDeliveryman','C164_GermanHeathKeeper', 'C169_FastMason','C173_TopOuter',
      'C174_StoneCustodian','C177_MountainHiker','C180_Trapper','C93_InnerDistrictsDirector','D105_Sculptor','D110_FishFarmer',
      'D116_TreeInspector','D125_ForestTrader','D134_OysterEater','D138_PetLover','D140_Loudmouth','D142_PotatoPlanter',
      'D143_TreeCutter','D144_WaterWorker','D146_Porter','D149_CasualWorker','D151_SpinDoctor','D164_PetGrower','D165_PigStalker',
      'D169_Plowsmith','D174_LoessGardener','D176_Woodshacker','D180_PartTimeWorker','E116_FirCutter','E131_MarketMaster',
      'E137_FlaxFarmer','E140_Carter','E143_Hewer','E152_BargainHunter','E158_StoneCustodian','E160_KelpGatherer','E166_Roastmaster'];
   if (in_array(($event['cardId']),$OccupationArray))
   {
      return $this->gainNode([SCORE => 1]);
   }
  }
}
