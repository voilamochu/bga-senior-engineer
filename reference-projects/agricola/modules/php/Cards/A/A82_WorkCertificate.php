<?php
namespace AGR\Cards\A;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Core\Engine;

class A82_WorkCertificate extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A82_WorkCertificate';
    $this->name = clienttranslate('Work Certificate');
    $this->deck = 'A';
    $this->number = 82;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use an action space, you can take 1 building resource from a building resource accumulation space with at least 4 building resources on it.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = '3 Occupations';
    $this->occupationPrerequisites = ['min' => 3];
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer');
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    $spaces = $this->getSpaces();
    if ($spaces == []) {
      return;
    }

    $childs = [];
    foreach ($spaces as $space) {
      $types = [];
      foreach (Meeples::getResourcesOnCard($space->getId()) as $meeple) {
        if (!in_array($meeple['type'], $types)) {
          $types[] = $meeple['type'];
        }
      }

      foreach ($types as $type) {
        $childs[] = [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'takeFromSpace',
            'args' => [$space->getId(), $type],
          ],
        ];
      }
    }

    return [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => $childs,
    ];
  }

  public function getSpaces()
  {
    $valid = [];

    foreach ([WOOD, CLAY, REED, STONE] as $type) {
      $spaces = ActionCards::getAccumulationSpaces($type);
      foreach ($spaces as $space) {
        if (Meeples::getResourcesOnCard($space->getId())->count() >= 4) {
          $valid[] = $space;
        }
      }
    }

    return $valid;
  }

  public function getTakeFromSpaceDescription($cId, $type)
  {
    $space = ActionCards::get($cId);

    return [
      'log' => clienttranslate('Take ${resources_desc} from ${action_space}'),
      'args' => [
        'i18n' => ['action_space'],
        'resources_desc' => '1 <' . strtoupper($type) . '>',
        'action_space' => $space->getName(),
      ],
    ];
  }

  public function takeFromSpace($cId, $type)
  {
    $meeple = Meeples::getResourcesOnCard($cId, null, $type)->first();
    Engine::insertAsChild($this->receiveNode($meeple['id'], true));
  }
}
