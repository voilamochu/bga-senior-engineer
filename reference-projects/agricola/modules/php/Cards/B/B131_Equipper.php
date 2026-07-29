<?php
namespace AGR\Cards\B;

use AGR\Helpers\UsedText;
use AGR\Helpers\Utils;

class B131_Equipper extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B131_Equipper';
    $this->name = clienttranslate('Equipper');
    $this->deck = 'B';
    $this->number = 131;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use a wood accumulation space, you can play a minor improvement.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('This is not a __Minor Improvement__ action and does not combo with cards that require a __Minor Improvement__ action, such as __Blueprint__ (C027).'),
    ];
    $this->usedText = UsedText::get('IMPROVEMENTS_BUILT');
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD, true);
  }

  public function onPlayerImmediatelyAfterCollect($player, $event)
  {
    return Utils::wrapOptional([
      'countAsUse' => true,
      'action' => IMPROVEMENT,
      'args' => [
        'types' => [MINOR],
        'trueAction' => false,
      ],
    ]);
  } 
}
