<?php

namespace ARK\Cards\Actions;

class ActionSponsors extends \ARK\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->actionType = 'Sponsors';
    $this->name = clienttranslate('Sponsors');
    $this->descI = [
      clienttranslate('Play **1** sponsors card with a maximum level of <STRENGTH:X> from your hand.'),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain <MONEY:X>.'),
    ];
    $this->descII = [
      clienttranslate(
        'Play **1 or more** sponsors cards with a total maximum level of <STRENGTH:X>** + 1** from your **hand** or from within **reputation range** (with additional costs).'
      ),
      'SPONSORS',
      clienttranslate('Break <STRBREAK:X>, gain **2x**<MONEY:X>.'),
    ];
    $this->tooltip = [
      clienttranslate('At level I, you can play sponsor cards only from your hand <HAND-CARDS>. At level II, you can play cards either from your hand or in your reputation range by paying **the folder number as additional cost** <HAND-CARDS> <TAKE-IN-RANGE-FOLDER-COST>.')
    ];
  }

  public function getFlow($strength = null)
  {
    $lvl = $this->getLevel();
    return [
      'action' => SPONSORS,
      'args' => [
        'strength' => $strength + ($lvl - 1),
        'lvl' => $lvl,
        'canBreakForMoney' => $lvl * $strength,
        'number' => $this->number,
      ],
    ];
  }

  public function canBePlayed($player, $strength = null)
  {
    // always doable as we can break
    return true;
  }
}
