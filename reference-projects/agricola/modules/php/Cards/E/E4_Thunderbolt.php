<?php
namespace AGR\Cards\E;

use AGR\Core\Engine;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Core\Notifications;

class E4_Thunderbolt extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E4_Thunderbolt';
    $this->name = clienttranslate('Thunderbolt');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 4;
    $this->category = 'PASSING_-_IMPROVEMENT/OCC_-_WOOD';
    $this->desc = [
      clienttranslate(
        'Immediately remove all <GRAIN> from one of your fields to the general supply. Gain 2 <WOOD> for each <GRAIN> you just removed.'
      ),
    ];
    $this->passing = true;
    $this->prerequisite = clienttranslate('1 Grain Field');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) < 1) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $grainFields = $player->board()->getGrainFields();

    if (count($grainFields) == 1) {
      $field = $grainFields[0];
      $grainCount = count($field['crops']);
      return [
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'discardSingleField',
          'args' => [$field, $grainCount],
        ],
      ];
    }

    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'discardFieldGrain',
      ],
    ];
  }

  public function getDiscardSingleFieldDescription($field, $grainCount)
  {
    $woodCount = $grainCount * 2;
    return [
      'log' => clienttranslate('Discard ${grain_count}<GRAIN>, gain ${wood_count}<WOOD>'),
      'args' => [
        'grain_count' => $grainCount,
        'wood_count' => $woodCount,
      ],
    ];
  }

  public function discardSingleField($field, $grainCount)
  {
    $this->actDiscardFieldGrain([$field]);
  }

  public function getDiscardFieldGrainDescription()
  {
    return clienttranslate('Discard all grains from one field');
  }


  public function argsDiscardFieldGrain()
  {
    $player = Players::getActive();

    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may discard grain from one of their fields (Thunderbolt)'),
      'descriptionmyturn' => clienttranslate('${you} may discard grain from one of your fields (Thunderbolt)'),
      'zones' => $player->board()->getGrainFields(),
    ];
  }

  public function actDiscardFieldGrain($field)
  {
    $crops = $field[0]['crops'];
    $pId = Players::getActiveId();

    $grains = [];
    foreach ($crops as $crop) {
      if ($crop['type'] == GRAIN) {
        $grains[] = $crop;
      }
    }
    Notifications::E4_Thunderbolt($grains);
    Meeples::deleteResources($grains);

    $childs = [$this->gainNode([WOOD => count($grains) * 2], $pId)];
    if (($field[0]['id'] ?? null) == 'E70_CropRotationField') {
      // XOR wrapper asks "use Crop Rotation Field?" first; once chosen, stSow auto-sows.
      // Inner leaf must NOT be optional or the engine auto-picks it and skips the ask.
      $childs[] = [
        'type' => NODE_XOR,
        'optional' => true,
        'childs' => [[
          'action' => SOW,
          'args' => [
            'max' => 1,
            'type' => VEGETABLE,
            'location' => ['E70_CropRotationField'],
            'auto' => true,
          ]
        ]],
      ];
    }

    $flow = [
      'type' => NODE_SEQ,
      'childs' => $childs
    ];

    Engine::insertAsChild($flow);
  }
}
