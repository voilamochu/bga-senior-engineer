<?php
namespace AGR\Cards\A;

class A103_Portmonger extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A103_Portmonger';
    $this->name = clienttranslate('Portmonger');
    $this->deck = 'A';
    $this->number = 103;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take 1/2/3+ <FOOD> from a food accumulation space, you also get 1 <VEGETABLE>/<GRAIN>/<REED>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, FOOD);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    $args = ['pId' => $this->pId];		  
    $n = count($event['meeples']);

	if ($n == 1) {
      $args[VEGETABLE] = 1;
	}
	elseif ($n == 2) {
      $args[GRAIN] = 1;
	}
	elseif ($n >= 3) {
      $args[REED] = 1;
	}

    return $this->gainNode($args);
  }
}
