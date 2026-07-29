<?php
namespace AGR\Cards\E;

class E161_ElderBaker extends \AGR\Models\PlayerActionCard

{
  protected $type = OCCUPATION;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E161_ElderBaker';
    $this->name = clienttranslate('Elder Baker');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 161;
    $this->category = 'CROPS';
    $this->desc = [
      clienttranslate(
        'This card is an action space for you only. When you use it, you get 3 <GRAIN>. You can build the __Stone Oven__ major improvement even when taking a __Minor Improvement__ action.'
      ),
    ];
    $this->players = '4+';
    $this->privateSpace = true;
    $this->flow = $this->gainNode([GRAIN => 3]);
  }  
}
