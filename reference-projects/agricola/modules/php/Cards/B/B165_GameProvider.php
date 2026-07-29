<?php
namespace AGR\Cards\B;
use AGR\Managers\Meeples;
use AGR\Helpers\Utils;
use AGR\Core\Notifications;
use AGR\Core\Engine;

class B165_GameProvider extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B165_GameProvider';
    $this->name = clienttranslate('Game Provider');
    $this->deck = 'B';
    $this->number = 165;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately before each harvest, you can discard 1/3/4 <GRAIN> from different fields to get 1/2/3 <PIG>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeHarvest' && !empty($this->getSourceFields());
  }

  public function onPlayerBeforeHarvest($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'optional' => false,
          'args' => [
            'cardId' => $this->id,
            'method' => 'eatFieldGrain',
          ],
        ],
        [
          'optional' => false,
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'gainPig',
          ],
        ],
      ],
    ];
  }

  public function gainPig()
  {
    $flow = $this->gainNode([PIG => $this ->getExtraDatas('pigs')]);
    Engine::insertAsChild($flow);
  }

  public function getGainPigDescription()
  {
    return clienttranslate('gain pigs');
  }

  public function getSourceFields()
  {
    $fields = $this->getPlayer()->board()->getGrainFields();
    Utils::filter($fields, function ($field) {
      return count($field['crops']) >= 1;
    });
    return $fields;
  }

  public function getEatFieldGrainDescription()
  {
    return clienttranslate('Remove 1 <GRAIN> from fields');
  }

  public function actEatFieldGrain($zones)
  {
    if (count($zones) == 2) {
      throw new \BgaVisibleSystemException('Select 1/3/4 fields');
    }
    if(count($zones) == 1){
      $this ->setExtraDatas('pigs',1);
    }
    if(count($zones) == 3){
      $this ->setExtraDatas('pigs',2);
    }
    if(count($zones) == 4){
      $this ->setExtraDatas('pigs',3);
    }
    $args = $this->argsEatFieldGrain();
    $meeples = [];
    $locations = [];
    foreach($zones as $zone){
      if (!in_array($zone, $args['sources'])) {
        throw new \BgaVisibleSystemException('Invalid field(s)');
      }
      if($zone['fieldType'] != GRAIN) { 
        throw new \BgaVisibleSystemException('Select grain fields');
      }
      $meeples[] = $zone['crops'][0];
      $locations[] = $zone['uid'];
    }
    Meeples::deleteResources($meeples);
    Notifications::payResourcesFromFields($this->getPlayer(), $meeples, $this->name,$locations);
  }

  public function argsEatFieldGrain()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may discard 1 <GRAIN> from fields to get <PIG> (Game Provider)'),
      'descriptionmyturn' => clienttranslate('Select fields (Game Provider)'),
      'sources' => $this->getSourceFields(),
    ];
  }
}