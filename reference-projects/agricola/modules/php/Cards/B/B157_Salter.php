<?php
namespace AGR\Cards\B;

use AGR\Core\Engine;

class B157_Salter extends \AGR\Models\Occupation
{
  private const TURNS = [SHEEP => 3, PIG => 5, CATTLE => 7];

  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B157_Salter';
    $this->name = clienttranslate('Salter');
    $this->deck = 'B';
    $this->number = 157;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At any time, you can pay 1 <SHEEP>/<PIG>/<CATTLE> from your farm. If you do, place 1 <FOOD> on each of the next 3/5/7 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isAnytime($event) && $this->getPlayer()->getAnimals('reserve')->count() == 0;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $onBoard = $player->countAnimalsOnBoard();
    $total = $onBoard[SHEEP] + $onBoard[PIG] + $onBoard[CATTLE];

    if ($total == 0) {
      return null;
    }

    if ($total == 1) {
      $singleAnimalDescriptions = [
        SHEEP => clienttranslate('Convert <SHEEP> to <FOOD>'),
        PIG => clienttranslate('Convert <PIG> to <FOOD>'),
        CATTLE => clienttranslate('Convert <CATTLE> to <FOOD>'),
      ];
      foreach (self::TURNS as $type => $turns) {
        if ($onBoard[$type] == 1) {
          $flow = $this->buildSaltFlow([$type => 1]);
          $flow['customDescription'] = $singleAnimalDescriptions[$type];
          return $flow;
        }
      }
    }

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'customDescription' => clienttranslate('Convert animals to <FOOD>'),
      'childs' => [[
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'salt',
        ],
      ]],
    ];
  }

  public function getSaltDescription()
  {
    return clienttranslate('Convert animals to <FOOD>');
  }

  public function argsSalt()
  {
    $onBoard = $this->getPlayer()->countAnimalsOnBoard();

    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may salt animals'),
      'descriptionmyturn' => clienttranslate('${you} may salt animals'),
      'reserve' => [
        SHEEP => $onBoard[SHEEP],
        PIG => $onBoard[PIG],
        CATTLE => $onBoard[CATTLE],
      ],
    ];
  }

  public function actSalt($sheep, $pig, $cattle)
  {
    $counts = [SHEEP => $sheep, PIG => $pig, CATTLE => $cattle];
    $total = $counts[SHEEP] + $counts[PIG] + $counts[CATTLE];

    if ($total < 1) {
      throw new \BgaVisibleSystemException('Salter: must salt at least one animal');
    }

    $onBoard = $this->getPlayer()->countAnimalsOnBoard();
    foreach ($counts as $type => $amount) {
      if ($amount < 0 || $amount > $onBoard[$type]) {
        throw new \BgaVisibleSystemException('Salter: salting more ' . $type . ' than available');
      }
    }

    Engine::insertAsChild($this->buildSaltFlow(array_filter($counts)));
  }

  private function buildSaltFlow($counts)
  {
    $childs = [$this->payNode($counts)];
    foreach (self::TURNS as $type => $turns) {
      if (($counts[$type] ?? 0) > 0) {
        $childs[] = $this->futureMeeplesNode([FOOD => $counts[$type]], $turns);
      }
    }

    return [
      'type' => NODE_SEQ,
      'childs' => $childs,
    ];
  }
}
