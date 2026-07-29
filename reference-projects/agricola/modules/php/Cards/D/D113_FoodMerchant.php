<?php
namespace AGR\Cards\D;

class D113_FoodMerchant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D113_FoodMerchant';
    $this->name = clienttranslate('Food Merchant');
    $this->deck = 'D';
    $this->number = 113;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'For each <GRAIN> you harvest from a field, you can buy 1 <VEGETABLE> for 3 <FOOD>. If you harvest the last <GRAIN> from a field, the <VEGETABLE> costs you only 2 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && $this->countGrains($event) > 0;
  }

  public function countGrains($event)
  {
    $n = 0;
    foreach ($event['crops'] as $meeple) {
      $n += $meeple['type'] == GRAIN ? 1 : 0;
    }
    return $n;
  }

  public function countLastGrains($event)
  {
    $n = 0;
    foreach ($event['crops'] as $crop) {
      $n += ($crop['type'] == GRAIN && isset($crop['isLastCrop']) && $crop['isLastCrop']) ? 1 : 0;
    }
    return $n;
  }

  public function onPlayerAfterReap($player, $event)
  {
    $n = $this->countGrains($event);
    $m = $this-> countLastGrains($event);
    $childs = [];
    $foodcount = 0;
      for ($i = 1; $i <= $n; $i++) {
        if($m > 0){
          $m = $m -1;
          $foodcount = $foodcount+2;
        }
        else{$foodcount = $foodcount+3;}
        $childs[] = $this->payGainNode([FOOD => $foodcount], [VEGETABLE => $i], null, false);
      }
      return  [
        'type' => NODE_XOR,
         'optional' => true,
         'doableRequiresChild' => true,
        'childs' => $childs,
      ];
  }
}