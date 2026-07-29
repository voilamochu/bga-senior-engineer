<?php
namespace AGR\Cards\A;

class A37_Bucksaw extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A37_Bucksaw';
    $this->name = clienttranslate('Bucksaw');
    $this->deck = 'A';
    $this->number = 37;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you renovate, you can also pay 1 <WOOD> to get 1 bonus <SCORE> and 1 <GRAIN>.'),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation');
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    return $this->payGainNode([WOOD => 1], [GRAIN => 1, SCORE => 1]);
  }
  
}
