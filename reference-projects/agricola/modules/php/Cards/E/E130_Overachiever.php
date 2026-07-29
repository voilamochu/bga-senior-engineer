<?php
namespace AGR\Cards\E;

use AGR\Helpers\UsedText;
use AGR\Helpers\Utils;
use AGR\Managers\ActionCards;
use AGR\Managers\Players;
use AGR\Core\Globals;
use AGR\Helpers\CardRulings;

class E130_Overachiever extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E130_Overachiever';
    $this->name = clienttranslate('Overachiever');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 130;
    $this->category = 'ACTION_-_IMPROVEMENTS_OR_OCCUPATIONS';
    $this->desc = [
      clienttranslate(
        'Each time you use a __Wish for Children__ action space, you can play 1 additional improvement by paying its cost minus 1 resource of your choice.'
      ),
    ];
    $this->players = '3+';

    $this->rulings = CardRulings::fromKeys([
      'TRIGGERS_BEFORE_ACTION_SPACE',
    ]);
    $this->usedText = UsedText::get('IMPROVEMENTS_BUILT');
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'WishChildren');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return Utils::wrapOptional([
      'countAsUse' => true,
      'action' => IMPROVEMENT,
      'cardId' => 'E130_Overachiever',
      'args' => [
        'types' => [MINOR, MAJOR],
        'trueAction' => false,
      ]
    ]);
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (($args['actionCardId'] ?? null) != 'E130_Overachiever') {
      return;
    }

    Utils::addBonusChoices($args['costs'], [
      [WOOD => -1],
      [CLAY => -1],
      [STONE => -1],
      [REED => -1],
      [FOOD => -1],
      [GRAIN => -1],
      [VEGETABLE => -1],
      [SHEEP => -1],
      [PIG => -1],
      [CATTLE => -1]
    ], $this->id);
  }

  // player may build a improvement for room space
  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if ($args['action'] == WISHCHILDREN) {
      if ($player->hasPlayedCard('E155_Visionary')) {
        $othersHaveGrown = true;

        foreach (Players::getAll() as $player2) {
          if ($player->getId() == $player2->getId()) {
            continue;
          }

          if ($player2->countFarmers() == 2) {
            $othersHaveGrown = false;
            break;
          }
        }

        if (Globals::getTurn() < 11 && !$othersHaveGrown) {
          return false;
        }
      }      

      if (isset($args['ctx'])) {
        $ctxArgs = is_array($args['ctx']) ? $args['ctx'] : $args['ctx']->getArgs();
        if ($ctxArgs["fromActionSpace"] ?? false) {
          $args['isDoable'] = true;
        }
      }
    }
  }
}
