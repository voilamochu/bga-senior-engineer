<?php
namespace ARK\Cards\Sponsors;

class S223_ScienceInstitute extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S223_ScienceInstitute';
    $this->number = 223;
    $this->name = clienttranslate('Science Institute');
    $this->lvl = 3;
    $this->categories = [SCIENCE, SCIENCE];
    $this->effects = [];
  }
}
