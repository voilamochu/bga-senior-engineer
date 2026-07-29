<?php
namespace AGR\Cards\A;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Core\Notifications;
use AGR\Core\Engine;
use AGR\Core\Stats;

class A144_Sequestrator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A144_Sequestrator';
    $this->name = clienttranslate('Sequestrator');
    $this->deck = 'A';
    $this->number = 144;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 3 <REED> and 4 <CLAY> on this card. The next player to have 3 pastures/5 field tiles gets the 3 <REED>/4 <CLAY> (not retroactively).'
      ),
    ];
    $this->players = '3+';
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak3or4p = true;
  }

  public function onBuy($player)
  {
    $created = Meeples::createResourceInLocation(REED, $this->id, $player->getId(), null, null, 3);
    Notifications::accumulate(Meeples::getMany($created), true);

    $created = Meeples::createResourceInLocation(CLAY, $this->id, $player->getId(), null, null, 4);
    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'Fencing', null) && $this->getNextResource(REED) != null) ||
      ($this->isActionEvent($event, 'Plow', null) && $this->getNextResource(CLAY) != null);
  }

  public function onAfterFencing($player, $event)
  {
    $pastures = count($player->board()->getPastures(true));

    if ($pastures >= 3) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'gainResources',
          'args' => [$player->getId(), REED],
        ],
      ];
    }
  }

  public function onAfterPlow($player, $event)
  {
    $fields = count($player->board()->getFieldTiles());

    if ($fields >= 5) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'gainResources',
          'args' => [$player->getId(), CLAY],
        ],
      ];
    }
  }

  public function gainResources($pId, $type)
  {
    $player = Players::get($pId);
    $childs = [];

    foreach (Meeples::getResourcesOnCard($this->id, null, $type) as $meeple) {
      $childs[] = $this->receiveNode($meeple['id'], $player);
    }

    Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $childs]);
  }

  public function onPlayerAfterFencing($player, $event)
  {
    return $this->onAfterFencing($player, $event);
  }

  public function onOpponentAfterFencing($player, $event)
  {
    return $this->onAfterFencing($player, $event);
  }

  public function onPlayerAfterPlow($player, $event)
  {
    return $this->onAfterPlow($player, $event);
  }

  public function onOpponentAfterPlow($player, $event)
  {
    return $this->onAfterPlow($player, $event);
  }
}
