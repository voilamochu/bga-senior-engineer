<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;
use const HARVEST;
use AGR\Helpers\CardRulings;

class E133_ChampionBreeder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E133_ChampionBreeder';
    $this->name = clienttranslate('Champion Breeder');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 133;
    $this->category = 'BONUS_POINTS';
    $this->desc = [
      clienttranslate(
        'Each time you place 2 or 3+ newborn animals on your farm during the breeding phase of the harvest, you get 1 or 2 bonus <SCORE>, respectively.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '3+';

    $this->rulings = CardRulings::fromKeys([
      'NEWBORN_ANIMAL_NEEDS_SPACE',
    ]);
  }

  public function isListeningTo($event)
  {
    return Globals::isBreedPhase() && $this->isActionEvent($event, 'Reorganize') && $event['trigger'] == HARVEST;
  }

  public function onPlayerAfterReorganize($player, $event)
  {
    $createdAnimals = Globals::getNumBredAnimals();
    $silentKills = Globals::getNumSilentKills();

    $score = $createdAnimals - $silentKills - 1;

    if ($score > 0) {
      return $this->gainNode([SCORE => $score]);
    }
  }
}
