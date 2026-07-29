<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B113_PatchCaregiver extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B113_PatchCaregiver';
    $this->name = clienttranslate('Patch Caregiver');
    $this->deck = 'B';
    $this->number = 113;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can choose to buy 1 <GRAIN> for 1 <FOOD>, or 1 <VEGETABLE> for 3 <FOOD>. This card is a field.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;    
    $this->field = true;    
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'doableRequiresChild' => true,
      'childs' => [
        $this->payGainNode([FOOD => 1],[GRAIN => 1], null, false),
        $this->payGainNode([FOOD => 3],[VEGETABLE => 1], null, false),
      ]
    ];
  }
  
  public function getFieldDetails()
  {
    return [
      'constraints' => null,
    ];
  }  
}
