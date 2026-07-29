<?php
namespace AGR\Cards\B;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Core\Engine;

class B81_Handcart extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B81_Handcart';
    $this->name = clienttranslate('Handcart');
    $this->deck = 'B';
    $this->number = 81;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Before each work phase, you can take 1 building resource from at most one <WOOD>/<CLAY>/<REED>/<STONE> accumulation space containing at least 6/5/4/4 building resources of the same type.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
       return $this->isPlayerEvent($event) &&
          $event['type'] == 'startOfWork';
  }

  public function onPlayerStartOfWork($player, $args)
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
          ]
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
    $meeples = [WOOD => 6, CLAY => 5, REED => 4, STONE => 4];

    foreach ([WOOD, CLAY, REED, STONE] as $type) {
      $spaces = ActionCards::getAccumulationSpaces($type);
      foreach ($spaces as $space) {
         $num = 0;
         foreach (Meeples::getResourcesOnCard($space->getId())->toArray() as $meeple) {
           if($meeple['type'] == $type){
             $num = $num +1;
           }
         }
        if ( $num>= $meeples[$type]) {
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
    $flow = $this->receiveNode($meeple['id'], true);

    Engine::insertAsChild($flow);
  }
}