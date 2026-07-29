<?php
namespace AGR\Cards\A;

use AGR\Helpers\UsedText;

class A115_ChiefForester extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A115_ChiefForester';
    $this->name = clienttranslate('Chief Forester');
    $this->deck = 'A';
    $this->number = 115;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you use a wood accumulation space, you also get a __Sow__ action for exactly 1 field.'),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
    $this->usedText = UsedText::get('SOWS');
  }
  
    public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, WOOD);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'countAsUse' => true,
      'action' => SOW,
      'optional' => true,
      'source' => $this->name,
	  'args' => ['max' => 1],
    ];
  }
}
