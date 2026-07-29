<?php
namespace AGR\Cards\A;

use AGR\Helpers\UsedText;
use AGR\Managers\Meeples;
use AGR\Helpers\CardRulings;
use AGR\Helpers\Utils;
use AGR\Core\Notifications;
use AGR\Core\Globals;

class A71_ClearingSpade extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A71_ClearingSpade';
    $this->name = clienttranslate('Clearing Spade');
    $this->deck = 'A';
    $this->number = 71;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At any time, you can move 1 crop from a planted field containing at least 2 crops to an empty field.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->rulings = CardRulings::fromKeys([
      'WOOD_STONE_NOT_CROPS',
    ]);
    $this->usedText = UsedText::get('CROPS_MOVED');
  }

  public function isListeningTo($event)
  {
    return ($this->isAnytime($event) || ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFieldPhase')) &&
      !empty($this->getSourceFields()) &&
      !empty($this->getTargetFields());
  }

  public function onPlayerStartHarvestFieldPhase($player, $event)
  {
    return $this->onPlayerAtAnytime($player, $event);
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'countAsUse' => true,
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'moveCrop',
      ],
    ];
  }

  public function getMoveCropDescription()
  {
    return clienttranslate('Move crop');
  }

  public function getSourceFields()
  {
    $fields = $this->getPlayer()
      ->board()
      ->getFieldsAndCrops();
    Utils::filter($fields, function ($field) {
      return count($field['crops']) >= 2 && in_array($field['crops'][0]['type'], CROPS);
    });
    return $fields;
  }

  public function getTargetFields()
  {
    $player = $this->getPlayer();
    $reserve = [];
    foreach (RESOURCES as $res) {
      $reserve[$res] = 0;
    }

    foreach($this->getSourceFields() as $field){
      $reserve[$field['crops'][0]['type']]++;
    }

    return $player->board()->getSowableFields($reserve);
  }

  public function argsMoveCrop()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may move one crop (Clearing Spade)'),
      'descriptionmyturn' => clienttranslate('Select the giving and receiving field (Clearing Spade)'),
      'sources' => $this->getSourceFields(),
      'targets' => $this->getTargetFields(),
    ];
  }

  public function actMoveCrop($sources, $targets)
  {
    if (count($sources) != count($targets) || count($sources) != 1) {
      throw new \BgaVisibleSystemException('Select only one giving and receiving field');
    }

    $args = $this->argsMoveCrop();
    $source = $sources[0];
    $target = $targets[0];
    if (!in_array($source, $args['sources']) || !in_array($target, $args['targets'])) {
      throw new \BgaVisibleSystemException('Invalid giving or receving field(s)');
    }

    $crop = $source['crops'][0];
    if (isset($target['constraints']) && $crop['type'] != $target['constraints']) {
      throw new \BgaVisibleSystemException('The receiving field can\'t accept this type of crop');
    }

    $location = $target['type'] == 'fieldCard' ? $target['id'] : 'board';
    Meeples::moveToCoords($crop['id'], $location, $target);
    $crop['location'] = $location;
    $crop['x'] = $target['x'];
    $crop['y'] = $target['y'];
    Notifications::A71_ClearingSpade($this->getPlayer(), [$crop]);
    

    $harvestedFields = Globals::getHarvestedFields();
    $pId = $this->getPlayer()->getId();

    if (isset($harvestedFields[$pId])) {
      $fields = $harvestedFields[$pId];
      Utils::filter($fields, function ($field) use ($source) {
          return $field != $source['uid'];
        });
      $harvestedFields[$this->getPlayer()->getId()] = $fields;
      Globals::setHarvestedFields($harvestedFields);
      }
  }
}
