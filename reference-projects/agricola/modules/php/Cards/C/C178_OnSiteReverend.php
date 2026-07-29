<?php
namespace AGR\Cards\C;

class C178_OnSiteReverend extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C178_OnSiteReverend';
    $this->name = ('On-Site Reverend');
    $this->deck = 'C';
    $this->number = 178;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [('At the start of each harvest, you get 1 building resource of your choice.')];
    $this->players = '5+';
    $this->implemented = false;
  }
}
