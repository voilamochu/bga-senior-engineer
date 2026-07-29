<?php
namespace AGR\Cards\D;

use AGR\Helpers\UsedText;
use AGR\Core\Globals;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class D93_SheepInspector extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D93_SheepInspector';
    $this->name = clienttranslate('Sheep Inspector');
    $this->deck = 'D';
    $this->number = 93;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Once per work phase, after you complete a person action, you can pay 1 <SHEEP> and 2 <FOOD> to return another person you placed home, unless it is on the __Meeting Place__ action space.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
    $this->usedText = UsedText::get('FARMERS_RETURNED_HOME');
  }

  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'PlaceFarmer') && !$this->isFlagged()) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'startOfWork');
  }

  public function onPlayerStartOfWork($player, $event)
  {
    return $this->unflagCardNode();
  }

  // Resolution-time guard: if already flagged (e.g. Brotherly Love created a duplicate node),
  // the flagCard leaf reports not-doable, making the optional SEQ auto-pass.
  public function isFlagCardDoable($player = null, $ignoreResources = false)
  {
    return !$this->isFlagged();
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    // Ignore Job Contract fake meeple placement (and any similar fake placements)
    if (($event['farmerPlaced'] ?? true) === false) {
      return;
    }

    $excludedFarmer = $event['fId'] ?? null;

    if ($this->getSpaces($excludedFarmer) == []) {
      return;
    }

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->flagCardNode(),
        $this->payNode([SHEEP => 1, FOOD => 2]),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'returnFarmer',
            'args' => [$excludedFarmer],
          ],
        ],
      ],
    ];
  }

  public function getSpaces($excludedFarmer)
  {
    $player = $this->getPlayer();
    $spaces = [];

    // If Job Contract fake exists, exclude its location
    $fakeLoc = null;
    $fakeId = Globals::getJobContractFake();
    if (!empty($fakeId)) {
      $meeples = Meeples::getMany($fakeId, false);
      if ($meeples->count() > 0) {
        $fakeLoc = $meeples->first()['location'] ?? null;
      }
    }

    $actionSpaces = ActionCards::getVisible();
    $actionSpaceIds = [];
    foreach ($actionSpaces as $actionSpace) {
      $actionSpaceIds[] = $actionSpace->getId();
    }

    foreach (Farmers::getAllOfPlayer($player->getId()) as $farmer) {
      // Children can't be returned by actReturnFarmer
      if ($farmer['state'] == CHILD) {
        continue;
      }
      if (!in_array($farmer['location'], $actionSpaceIds) || ($excludedFarmer !== null && $farmer['id'] == $excludedFarmer)) {
        continue;
      }

      // Don’t allow selecting the Job Contract fake location
      if ($fakeLoc !== null && $farmer['location'] == $fakeLoc) {
        continue;
      }

      $spaces[] = $farmer['location'];
    }

    // Exclude Meeting Place robustly, then normalise keys for JS
    $spaces = array_values(array_unique($spaces));
    $spaces = array_values(array_filter($spaces, function ($s) {
      return strpos($s, 'ActionMeetingPlace') !== 0;
    }));

    return $spaces;
  }

  public function getReturnFarmerDescription()
  {
    return clienttranslate('Return a person to your home');
  }

  public function argsReturnFarmer($excludedFarmer)
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may return a farmer to their home (Sheep Inspector)'),
      'descriptionmyturn' => clienttranslate('${you} may return a farmer to your home (Sheep Inspector)'),
      'spaces' => $this->getSpaces($excludedFarmer),
    ];
  }

  // SpecialEffect::actSpecialEffect passes actArgs first, then ctx args ($excludedFarmer)
  public function actReturnFarmer($space, $excludedFarmer)
  {
    $player = $this->getPlayer();

    // Job Contract handling:
    // - If the player somehow clicks the fake’s space (Lessons), treat it as returning the Day Laborer farmer home + cleanup fake. (shouldn't be possibly but just in case)
    // - If the player returns the Day Laborer farmer home, cleanup fake.
    $fakeId = Globals::getJobContractFake();
    if (!empty($fakeId)) {
      $fakeLoc = null;
      $meeples = Meeples::getMany($fakeId, false);
      if ($meeples->count() > 0) {
        $fakeLoc = $meeples->first()['location'] ?? null;
      }

      if ($fakeLoc !== null && $space == $fakeLoc) {
        $dayLaborer = Farmers::getNonChildrenOnCard('ActionDayLaborer', $player->getId())->first();
        if ($dayLaborer === null) {
          Farmers::cleanupJobContractFake();
          throw new \BgaVisibleSystemException('No valid person found to return home.');
        }

        Farmers::cleanupJobContractFake();
        $player->returnHomeOne($dayLaborer['id']);
        Notifications::returnHomeOne($player, Farmers::getMany($dayLaborer['id']));
        return;
      }
    }

    $farmer = Farmers::getNonChildrenOnCard($space, $player->getId())->first();
    if ($farmer === null) {
      throw new \BgaVisibleSystemException('No valid person found on that action space.');
    }

    if ((int) $farmer['id'] === (int) $excludedFarmer) {
      throw new \BgaVisibleSystemException('You must choose another person.');
    }

    // If returning the real Day Laborer farmer, cleanup the Job Contract fake on Lessons
    if (!empty($fakeId) && $space == 'ActionDayLaborer') {
      Farmers::cleanupJobContractFake();
    }

    $player->returnHomeOne($farmer['id']);
    Notifications::returnHomeOne($player, Farmers::getMany($farmer['id']));
  }
}
