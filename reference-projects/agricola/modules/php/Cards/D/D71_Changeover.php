<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;
use AGR\Helpers\Utils;
use AGR\Core\Notifications;
use AGR\Core\Globals;

class D71_Changeover extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D71_Changeover';
    $this->name = clienttranslate('Changeover');
    $this->deck = 'D';
    $this->number = 71;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At any time, if a field contains exactly 1 good as a result of a harvest, you can discard that good and immediately take a __Sow__ action limited to that field.'
      ),
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return !empty($this->getSourceFields()) && $this->isAnytime($event);
  }

  public function getSourceFields()
  {
    $fields = $this->getPlayer()->board()->getFieldsAndCrops();
    Utils::filter($fields, function ($field) {
      return count($field['crops']) == 1 && \array_key_exists($this->getPlayer()->getId(),Globals::getHarvestedFields()) && in_array($field['uid'],Globals::getHarvestedFields()[$this->getPlayer()->getId()]);
    });
    return $fields;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'optional' => false,
          'args' => [
            'cardId' => $this->id,
            'method' => 'eatFieldCrop',
          ],
        ],
        ['optional' => true, 'action' => SOW, 'args' => ['max' => 1,'location' => $this->loc()]],
      ],
    ];
  }

  public function loc(){
    $sour = $this -> getSourceFields();
    $loc = [];
    foreach($sour as $field){
      $loc[] = $field['uid'];
    } 
    return $loc;
  }
  public function getEatFieldCropDescription()
  {
    return clienttranslate('Remove 1 crop from a field');
  }

  public function actEatFieldCrop($zones)
  {
    if (count($zones) != 1) {
      throw new \BgaVisibleSystemException('Select only one field');
    }
    $args = $this->argsEatFieldCrop();
    $zone = $zones[0];
    if (!in_array($zone, $args['sources'])) {
      throw new \BgaVisibleSystemException('Invalid field(s)');
    }

    $meeples = array($zone['crops'][0]);
    Meeples::deleteResources($meeples);
    $locations = array($zone['uid']);
    Notifications::payResourcesFromFields($this->getPlayer(), $meeples, $this->name,$locations);
  }

  public function argsEatFieldCrop()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may discard 1 crop from a field to sow (Changeover)'),
      'descriptionmyturn' => clienttranslate('Select field (Changeover)'),
      'sources' => $this->getSourceFields(),
    ];
  }
}
