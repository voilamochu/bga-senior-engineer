<?php
namespace AGR\Cards\C;
use AGR\Core\Engine;
use AGR\Core\Globals;

class C84_PerennialRye extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C84_PerennialRye';
    $this->name = clienttranslate('Perennial Rye');
    $this->deck = 'C';
    $this->number = 84;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each round that does not end with a harvest, you can pay 1 <GRAIN> to breed exactly 1 type of animal. (This is not considered a breeding phase.)'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }

  public function isListeningTo($event)
  {
    return ($this->isAnytime($event) && !$this->isFlagged()) || // Use the card
      ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn'); // Unuse the card
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }  

  public function onPlayerAtAnytime($player, $event)
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
      'countAsUse' => true,
      'childs' => [
        $this->flagCardNode(),
        $this->payNode([GRAIN => 1]),
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
