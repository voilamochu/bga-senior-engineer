<?php
namespace AGR\Cards\E;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;

class E71_CowPatty extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E71_CowPatty';
    $this->name = clienttranslate('Cow Patty');
    $this->deck = 'E';
    $this->author = 'chriss';
    $this->number = 71;
    $this->category = 'CROPS_-_GRAIN_AND_VEGETABLE';
    $this->desc = [
      clienttranslate(
        'Each time you sow in a field that is orthogonally adjacent to a pasture, you can place 1 additional good of the planted type in it.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('1 Cattle');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countAnimalsOnBoard()[CATTLE] == 0) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Sow');
  }

  public function onPlayerAfterSow($player, $event) {
    $this->getFields($event);
    $zones = $this->getExtraDatas('zones');

    if (empty($zones)) {
      return null;
    }

    if (count($zones) == 1) {
      $cropType = $zones[0]['sownType'] ?? $zones[0]['crops'][0]['type'] ?? '';
      return [
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'addSingleGood',
          'args' => [$zones, $cropType],
        ],
      ];
    }

    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'addAdditionalGood',
        'args' => [$zones],
      ],
    ];
  }

  public function getAddSingleGoodDescription($zones, $cropType)
  {
    $icon = '<' . strtoupper($cropType) . '>';
    return [
      'log' => clienttranslate('Add additional ${resource}'),
      'args' => [
        'resource' => $icon,
      ],
    ];
  }

  public function addSingleGood($zones, $cropType)
  {
    $this->actAddAdditionalGood($zones);
  }

  public function getFields($event)
  {
    $zones = [];
    $hasAnotherPasture = count($this->getPlayer()->board()->getPastures(true)) > 1;

    foreach (($event['sows'] ?? []) as $sow) {
      $field = $sow['field'];
      // The field snapshot predates the sow (empty crops), so remember what was planted
      $field['sownType'] = $sow['type'] ?? null;

      // Love for Agriculture: treat the whole 1/2-space pasture as one field.
      // Since all pastures are connected by rules, this field is adjacent to another
      // pasture iff the player has more than one pasture in total.
      if (($field['type'] ?? null) === 'pastureField') {
        if ($hasAnotherPasture) {
          $zones[] = $field;
        }
        continue;
      }

      if ($field['x'] >= 0 && $field['x'] <= 10 && $field['y'] >= 0 && $field['y'] <= 6) {
        if ($this->getPlayer()->board()->isAdjacentToType($field['x'], $field['y'], 'pasture')) {
          $zones[] = $field;
        }
      }
    }

    $this->setExtraDatas('zones', $zones);
  }

  public function getAddAdditionalGoodDescription()
  {
    return clienttranslate('Choose fields to put additional crops in');
  }

  public function argsAddAdditionalGood()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may choose fields to put additional crops in (Cow Patty)'),
      'descriptionmyturn' => clienttranslate('${you} may choose fields to put additional crops in (Cow Patty)'),
      'zones' => $this->getExtraDatas('zones'),
    ];
  }

  public function actAddAdditionalGood($zones)
  {
    $player = $this->getPlayer();
    $fields = $player->board()->getPlantedFields();
    $sows = [];

    $fids = [];
    foreach ($zones as $zone) {
      $fids[] = $zone['id'];
    }

    foreach ($fields as $field) {
      if (in_array($field['id'], $fids)) {
        $type = $field['crops'][0]['type'];

        $ids = Meeples::createResourceInLocation(
          $type,
          'board',
          $field['pId'],
          $field['x'],
          $field['y'],
          1
        );

        $sows[] = [
          'field' => $field,
          'crops' => Meeples::getMany($ids)->toArray(),
        ];
      }
    }

    if (empty($sows)) {
      return;
    }

    Notifications::sow($player, $sows, true);
  }
}
