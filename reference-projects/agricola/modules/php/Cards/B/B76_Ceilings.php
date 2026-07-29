<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class B76_Ceilings extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B76_Ceilings';
    $this->name = clienttranslate('Ceilings');
    $this->deck = 'B';
    $this->number = 76;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <WOOD> on the next 5 round spaces. At the start of these rounds, you get the <WOOD>. Remove the <WOOD> promised by this card from future round spaces the next time you renovate.'
      ),
    ];
    $this->cost = [
      CLAY => 1,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([WOOD => 1], 5, true, $this->id);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation') && !$this->isFlagged();
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'deleteFutureWood'
          ]
        ]
      ]
    ];
  }

  public function getDeleteFutureWoodDescription()
  {
    return [
      'log' => clienttranslate('Remove ${resources_desc} promised by ${card}'),
      'args' => [
        'resources_desc' => '<WOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function deleteFutureWood()
  {
    $turn = Globals::getTurn();
    $player = $this->getPlayer();

    for ($i = 1; $i < 6; $i++) {
      $resources = Meeples::getResourcesOnCard('turn_' . ($turn+$i), $player->getId());
      foreach ($resources as $resource) {
        if ($resource['x'] == $this->id) {
          $silentKill = Meeples::deleteResources([$resource]);
          Notifications::silentKill($silentKill);
        }
      }
    }
  }

}
