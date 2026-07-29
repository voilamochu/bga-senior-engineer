<?php
namespace AGR\Cards\A;

use AGR\Core\Globals;
use AGR\Managers\ActionCards;
use AGR\Helpers\Utils;

class A96_TaskArtisan extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A96_TaskArtisan';
    $this->name = clienttranslate('Task Artisan');
    $this->deck = 'A';
    $this->number = 96;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card and each time a stone accumulation space appears on a round space in the preparation phase, you get 1 <WOOD> and a __Minor Improvement__ action.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_PARALLEL,
      'childs' => [
        $this->gainNode([WOOD => 1]),
        Utils::wrapOptional([
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MINOR],
          ],
        ]),
      ],
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'AfterRevealAction' &&
      (Globals::getLastRevealed() == 'ActionWesternQuarry' ||
        Globals::getLastRevealed() == 'ActionEasternQuarry');
  }

  public function onPlayerAfterRevealAction($player, $event)
  {
    return [
      'type' => NODE_PARALLEL,
      'childs' => [
        $this->gainNode([WOOD => 1]),
        Utils::wrapOptional([
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MINOR],
          ],
        ]),
      ],
    ];
  }
}
