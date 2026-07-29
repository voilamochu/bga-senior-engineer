<?php
namespace AGR\Cards\E;

class E108_BlackberryFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E108_BlackberryFarmer';
    $this->name = clienttranslate('Blackberry Farmer');
    $this->deck = 'E';
    $this->author = 'superg';
    $this->number = 108;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Each time you build fences, place 1 <FOOD> on each remaining round space, up to the number of fences just built. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Fencing') && $this->countActualFencesBuilt($event) >= 1;
  }

  protected function countActualFencesBuilt($event)
  {
    $built = count($event['fences'] ?? []);
    $woodPalisades = $event['woodPalisades'] ?? 0;
    return max(0, $built - $woodPalisades);
  }

  public function onPlayerAfterFencing($player, $event)
  {
    return $this->futureMeeplesNode([FOOD => 1], $this->countActualFencesBuilt($event));
  }
}
