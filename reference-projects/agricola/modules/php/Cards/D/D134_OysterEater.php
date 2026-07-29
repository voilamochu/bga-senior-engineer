<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D134_OysterEater extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D134_OysterEater';
    $this->name = clienttranslate('Oyster Eater');
    $this->deck = 'D';
    $this->number = 134;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time the __Fishing__ accumulation space is used, you get 1 bonus <SCORE> and must skip placing your next person that round. (You can place the person on a later turn.)'
      ),
    ];
    $this->extraVp = true;
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer', null) && $event['actionCardType'] == 'Fishing';
  }

  public function onAfterPlaceFarmer($player, $event)
  {
    $player = $this->getPlayer()->getId();

    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([SCORE => 1], $player),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'method' => 'skipNextPlacement',
            'cardId' => $this->id,
            'args' => [],
          ],
        ]
      ]
    ];
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return $this->onAfterPlaceFarmer($player, $event);
  }

  public function onOpponentAfterPlaceFarmer($player, $event)
  {
    return $this->onAfterPlaceFarmer($player, $event);
  }

  public function skipNextPlacement()
  {
    $player = $this->getPlayer()->getId();
    $skipped = Globals::getSkipNext() ?? [];
    $skipped[] = $player;
    Globals::setSkipNext($skipped);
  }
}
