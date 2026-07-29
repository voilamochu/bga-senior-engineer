<?php
namespace AGR\Cards\A;

class A83_ShepherdsCrook extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A83_ShepherdsCrook';
    $this->name = clienttranslate("Shepherd's Crook");
    $this->deck = 'A';
    $this->number = 83;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you fence a new pasture covering at least 4 farmyard spaces, you immediately get 2 sheep on this pasture.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];

    $this->rulings = [
      clienttranslate('Building 2 such pastures at once gives you 4 <SHEEP>.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Fencing', 'player', true) &&
      !empty($this->getBigEnoughPastures($event['newPastures']['new']));
  }

  protected function getBigEnoughPastures($pastures)
  {
    return \array_values(
      array_filter($pastures, function ($pasture) {
        return count($pasture['nodes']) >= 4;
      })
    );
  }

  public function onPlayerImmediatelyAfterFencing($player, $event)
  {
    $pastures = $this->getBigEnoughPastures($event['newPastures']['new']);
    return $this->gainNode([SHEEP => 2 * count($pastures)]);
  }
}
