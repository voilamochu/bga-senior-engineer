<?php
namespace AGR\Cards\C;

class C161_PotatoDigger extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C161_PotatoDigger';
    $this->name = clienttranslate('Potato Digger');
    $this->deck = 'C';
    $this->number = 161;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, if you have at least 2/4/5 unplanted field tiles, you immediately get 1/2/3 <VEGETABLE>.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    $n = 0;
    $emptyFields = 0;
    
    foreach ($player->board()->getSowableFields(null, true) as $field) {
      if ($field['type'] == 'field') {
        $emptyFields++;
      }
    }

    if ($emptyFields >= 2) {$n = 1;}
    if ($emptyFields >= 4) {$n = 2;}
    if ($emptyFields >= 5) {$n = 3;}
    
    if ($n > 0) {
      return $this->gainNode([VEGETABLE => $n]);
    }
  }
}
