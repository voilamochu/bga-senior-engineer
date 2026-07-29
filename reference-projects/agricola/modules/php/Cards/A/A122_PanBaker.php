<?php
namespace AGR\Cards\A;

class A122_PanBaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A122_PanBaker';
    $this->name = clienttranslate('Pan Baker');
    $this->deck = 'A';
    $this->number = 122;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you use the __Grain Utilization__ action space, you also get 2 <CLAY> and 1 <WOOD>.'),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable'] || $args['action'] != SOW) {
      return;
    }
    if (!is_object($args['ctx']) || $args['ctx']->getCardId() != 'ActionGrainUtilization') {
      return;
    }

    // Empty supply, but our incoming 1 wood can be sown on a wood field card
    // (Wood Field, Cherry Orchard) (bug 232439)
    $reserve = $player->getAllReserveResources();
    $reserve[WOOD] += 1;
    $reserve[CLAY] += 2;
    if ($player->board()->canSow($reserve, false, true)) {
      $args['isDoable'] = true;
    }
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainUtilization');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([CLAY => 2, WOOD => 1]);
  }  
}
