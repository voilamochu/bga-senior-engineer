<?php
namespace AGR\Cards\B;

class B109_PaperMaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B109_PaperMaker';
    $this->name = clienttranslate('Paper Maker');
    $this->deck = 'B';
    $this->number = 109;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately before playing each occupation after this one, you can pay 1 <WOOD> total to get 1 <FOOD> for each occupation you have in front of you.'
      ),
    ];
    $this->players = '1+';
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if ($args['action'] == OCCUPATION && $player->canPayCost([WOOD => 1])) {
      // TODO : handle the cost of occupation
      $args['isDoable'] = true;
    }
  }

  public function isListeningTo($event)
  {
    return $event['type'] == 'action' &&
      $event['action'] == 'Occupation' &&
      $this->pId == $event['pId'] &&
      $event['method'] == 'beforeOccupation';
  }

  public function onPlayerBeforeOccupation($player, $event)
  {
    return $this->payGainNode(
      [WOOD => 1],
      [FOOD => $this->getPlayer()->countOccupations(), 'cardId' => $this->id],
      clienttranslate('Paper Maker effect')
    );
  }
}
