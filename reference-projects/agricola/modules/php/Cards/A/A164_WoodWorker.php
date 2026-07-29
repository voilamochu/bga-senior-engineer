<?php
namespace AGR\Cards\A;

class A164_WoodWorker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A164_WoodWorker';
    $this->name = clienttranslate('Wood Worker');
    $this->deck = 'A';
    $this->number = 164;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take <WOOD> from an accumulation space, you can exchange 1 <WOOD> for 1 <SHEEP>. Place the <WOOD> on the accumulation space.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('If you take less than 1 <WOOD> from a <WOOD> accumulation space, you may still use this card.'),
      clienttranslate('You may immediately convert the <SHEEP> to <FOOD> in order to use the __Shifting Cultivator__ (A091).'),
    ];
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([WOOD => 1], $event['actionCardId']),
        $this->gainNode([SHEEP => 1]),
      ]
    ];
  }
}
