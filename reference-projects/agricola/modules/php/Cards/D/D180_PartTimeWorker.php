<?php
namespace AGR\Cards\D;

class D180_PartTimeWorker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D180_PartTimeWorker';
    $this->name = ('Part-Time Worker');
    $this->deck = 'D';
    $this->number = 180;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      (
        'Each time you use an accumulation space with exactly 2/4/6 goods on it, you can leave 1/2/3 goods on the space. If you do, you get 1 sheep/wild boar/cattle.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
