<?php
namespace AGR\Cards\A;

class A64_BarleyMill extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A64_BarleyMill';
    $this->name = clienttranslate('Barley Mill');
    $this->deck = 'A';
    $this->number = 64;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('In the field phase of each harvest, you get 1 <FOOD> for each grain field that you harvest.'),
    ];
    $this->vp = 1;
    $this->fee = [WOOD => 1];
    $this->costs = [[CLAY =>4], [STONE => 2]];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && (($event['trigger'] ?? null) == HARVEST) && $this->countGrains($event) > 0;
  }

  public function countGrains($event)
  {
    $n = 0;
    $fields = [];
    foreach($event['crops'] as $meeple){
      $uid = ($meeple['x'] < 0 || $meeple['y'] < 0) ? $meeple['location'] : $meeple['x'] . '_' . $meeple['y'];
      if( $meeple['type'] == \GRAIN && !in_array($uid,$fields)){
        $fields[] = $uid;
      }
    }
    return count($fields);
  }

  public function onPlayerAfterReap($player, $event)
  {
    $n = $this->countGrains($event);
    return $this->gainNode([FOOD => $n]);
  }
}