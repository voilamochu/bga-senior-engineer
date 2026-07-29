<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;
use AGR\Core\Engine;

class D167_PureBreeder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D167_PureBreeder';
    $this->name = clienttranslate('Pure Breeder');
    $this->deck = 'D';
    $this->number = 167;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You immediately get 1 <WOOD>. After each round that does not end with a harvest, you can breed exactly one type of animal. (This is not considered a breeding phase.)'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'AfterEndOfRound';
  }

  public function onPlayerAfterEndOfRound($player)
  {
    $turn = Globals::getTurn();
    $harvestRounds = [4, 7, 9, 11, 13, 14];

    if (in_array($turn, $harvestRounds)) {
      return;
    }    

    $breedTypes = $player->breedTypes();

    if (!$breedTypes[SHEEP] && !$breedTypes[PIG] && !$breedTypes[CATTLE]) {
      return;
    }

    $childs = [];
    foreach ($breedTypes as $type => $canBreed) {
      if (!$canBreed) {
        continue;
      }

      $childs[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'breedAnimal',
          'args' => [$type],
        ],
      ];
    }

    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'type' => NODE_XOR,
          'childs' => $childs,
        ],
      ],
    ];
  }

  public function getBreedAnimalDescription($type)
  {
    $type = '<' . strtoupper($type) . '>';

    return [
      'log' => clienttranslate('Breed ${animal_type}'),
      'args' => [
        'animal_type' => $type,
      ],
    ];
  }

  public function breedAnimal($type)
  {
    $player = $this->getPlayer();
    if (!$player->breed($type, 'card')) {
      return;
    }

    $flow = [
      'action' => REORGANIZE,
      'pId' => $player->getId(),
      'args' => [
        'trigger' => HARVEST,
        'breedTypes' => [$type => true],
      ],
    ];
          
    Engine::insertAsChild($flow);
  }
}
