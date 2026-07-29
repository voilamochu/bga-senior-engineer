<?php
namespace AGR\Cards\E;

use AGR\Helpers\UsedText;

class E152_BargainHunter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E152_BargainHunter';
    $this->name = clienttranslate('Bargain Hunter');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 152;
    $this->category = 'ACTION_-_IMPROVEMENTS_OR_OCCUPATIONS';
    $this->desc = [
      clienttranslate(
        'At the start of each round, you can place 1 <FOOD> from your supply on the __Traveling Players__ accumulation space to play a minor improvement by paying its cost.'
      ),
    ];
    $this->players = '4+';
    $this->usedText = UsedText::get('IMPROVEMENTS_BUILT');
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([FOOD => 1], 'ActionTravelingPlayers'),
        [
          'action' => IMPROVEMENT,
          'optional' => false,
          'args' => [
            'types' => [MINOR],
            'trueAction' => false,
          ],
        ],
      ],
    ];
  }
}
