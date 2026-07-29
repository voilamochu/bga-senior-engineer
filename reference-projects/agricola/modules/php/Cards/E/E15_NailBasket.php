<?php
namespace AGR\Cards\E;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class E15_NailBasket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E15_NailBasket';
    $this->name = clienttranslate('Nail Basket');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 15;
    $this->category = 'FARMYARD_-__FENCING_OR_STABLE_BUILDING';
    $this->desc = [
      clienttranslate(
        'Each time after you use a wood accumulation space, you can place 1 <STONE> from your supply on that space (for the next visitor) to take a __Build Fences__ action.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      REED => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([STONE => 1], $event['actionCardId']),
        [
          'action' => FENCING,
          'args' => ['costs' => Utils::formatCost([WOOD => 1])],
        ],        
      ]
    ];
  }
}
