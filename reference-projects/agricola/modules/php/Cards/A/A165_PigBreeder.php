<?php
namespace AGR\Cards\A;
use AGR\Core\Globals;
use AGR\Core\Engine;

class A165_PigBreeder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A165_PigBreeder';
    $this->name = clienttranslate('Pig Breeder');
    $this->deck = 'A';
    $this->number = 165;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <PIG>. Your <PIG> breed at the end of round 12 (if there is room for the new <PIG>).'
      ),
    ];
    $this->players = '4+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([PIG => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'EndOfRound' &&
      Globals::getTurn() == 12;
  }

  public function onPlayerEndOfRound($player, $event)
  {
    if ($player->breedTypes()[PIG]) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'breedAnimal',
          'args' => [PIG],
        ],
      ];
    }
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
