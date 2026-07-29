<?php
namespace AGR\Cards\B;

class B141_FieldCaretaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B141_FieldCaretaker';
    $this->name = clienttranslate('Field Caretaker');
    $this->deck = 'B';
    $this->number = 141;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can immediately exchange 0/1/3 <CLAY> for 1/2/3 <GRAIN>. This card is a field.'
      ),
    ];
    $this->players = '3+';
    $this->holder = true;    
    $this->field = true;        
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => [
        $this->gainNode([GRAIN => 1]),
        $this->payGainNode([CLAY => 1], [GRAIN => 2], null, false),
        $this->payGainNode([CLAY => 3], [GRAIN => 3], null, false),
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
