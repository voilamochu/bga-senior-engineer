<?php
namespace AGR\Cards\A;
use AGR\Core\Engine;
use AGR\Managers\Meeples;

class A137_RiverineShepherd extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A137_RiverineShepherd';
    $this->name = clienttranslate('Riverine Shepherd');
    $this->deck = 'A';
    $this->number = 137;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Sheep Market__ or __Reed Bank__ accumulation space, you can also take 1 good from the respective other accumulation space, if possible.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'SheepMarket') ||
      $this->isActionCardEvent($event, 'ReedBank');
  }
    
  public function onPlayerPlaceFarmer($player, $event)
  {
    $other = $event['actionCardType'] == 'SheepMarket' ? 'ActionReedBank' : 'ActionSheepMarket';
    $type = $other == 'ActionReedBank' ? REED : SHEEP;
      
    if (is_null($this->getResource($other, $type))) {
      return;
    }
      
    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
      'cardId' => $this->id,
      'method' => 'gainResource',
      'args' => [$other, $type],
      ],
    ];
  }
  
  public function getResource($card, $type)
  {
    $meeples = Meeples::getResourcesOnCard($card, null, $type);
    return $meeples->empty() ? null : $meeples->last();
  }
  
  public function getGainResourceDescription($actionCardId, $type)
  {
    return [
      'log' => clienttranslate('Take ${resources_desc} from ${action_space}'),
      'args' => [
        'i18n' => ['action_space'],      
        'resources_desc' => '1 <' . strtoupper($type) . '>',
        'action_space' => $actionCardId == 'ActionSheepMarket' ? clienttranslate('Sheep Market') : clienttranslate('Reed Bank'),
      ],
    ];      
  }
  
  public function gainResource($actionCardId, $type)
  {
    $meeple = $this->getResource($actionCardId, $type);
    Engine::insertAsChild($this->receiveNode($meeple['id']));
  }
}
