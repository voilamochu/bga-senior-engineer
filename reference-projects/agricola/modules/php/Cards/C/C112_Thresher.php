<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;

class C112_Thresher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C112_Thresher';
    $this->name = clienttranslate('Thresher');
    $this->deck = 'C';
    $this->number = 112;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately before each time you use the __Grain Utilization__, __Farmland__, or __Cultivation__ action space, you can buy 1 <GRAIN> for 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if (($args['action'] == SOW || $args['action'] == EXCHANGE) && $player->canPayCost([FOOD => 1])) {
      $args['isDoable'] = true;
    }
  }

  public function isListeningTo($event)
  {
    // return $this->isActionEvent($event, 'PlaceFarmer', 'player', true) &&
    //   in_array($event['actionCardType'], ['Farmland', 'GrainUtilization', 'Cultivation']);
    return $this->isActionCardEvent($event, 'Farmland') ||
      $this->isActionCardEvent($event, 'GrainUtilization') ||
      $this->isActionCardEvent($event, 'Cultivation');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    // throw new \feException(print_r($args));
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        [
          'action' => PAY,
          'args' => [
            'nb' => 1,
            'costs' => Utils::formatCost([FOOD => 1]),
            'source' => $this->name,
          ],
        ],
        $this->gainNode([GRAIN => 1])
      ],
    ];
  }
}
