<?php
namespace AGR\Cards\D;

class D65_GrainSieve extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D65_GrainSieve';
    $this->name = clienttranslate('Grain Sieve');
    $this->deck = 'D';
    $this->number = 65;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, if you harvest at least 2 <GRAIN>, you get 1 additional <GRAIN> from the general supply.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && (($event['trigger'] ?? null) == HARVEST) && $this->checkGrainReaped($event);
  }

  public function checkGrainReaped($event)
  {
    $n = 0;
    foreach($event['crops'] as $meeple){
      $n += $meeple['type'] == GRAIN? 1 : 0;
    }
    return $n >= 2;
  }

  public function onPlayerAfterReap($player, $event)
  {
    if ($this->checkGrainReaped($event)) {
      return $this->gainNode([GRAIN => 1]);
    }
  }  
}
