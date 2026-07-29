<?php
namespace AGR\Cards\A;

class A142_Cordmaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A142_Cordmaker';
    $this->name = clienttranslate('Cordmaker');
    $this->deck = 'A';
    $this->number = 142;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) takes at least 2 <REED> from the __Reed Bank__ accumulation space, you can choose to take 1 <GRAIN> or buy 1 <VEGETABLE> for 2 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, REED, false, null) &&
      count($event['meeples']) >= 2;
  }
  
  public function onAfterCollect($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'childs' => [
        $this->gainNode([GRAIN => 1]),
        $this->payGainNode([FOOD => 2], [VEGETABLE => 1], null, false),
      ]
    ];    
  }
  
  public function onPlayerAfterCollect($player, $event)
  {
    return $this->onAfterCollect($player, $event);
  }
  
  public function onOpponentAfterCollect($player, $event)
  {
    $flow = $this->onAfterCollect($player, $event);
      
    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [$flow],
        ]
      ]
    ];
  }
}
