<?php

namespace ARK\Cards\Actions;

class ActionAssociation2 extends ActionAssociation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->number = 2;
    $this->descI = [
      clienttranslate('Perform **1 association task** with a maximum value of <STRENGTH:X>.'),
      clienttranslate('Instead of supporting a conservation project, you may hire **1 new association worker** at <STRENGTH:5> (also place the worker on <STRENGTH:5>.'),
    ];
    $this->descII = [
      clienttranslate('Perform **1 or more different association tasks** with a total maximum value of <STRENGTH:X>.'),
      clienttranslate('In addition, you may make 1 **donation**.'),
      clienttranslate('You may place additional workers to reduce the required strength by <STRENGTH:2> each.'),
    ];
    $this->tooltip[] = clienttranslate("<SIDE_I> When using the Conservation project work task, you may hire an association worker instead of supporting a conservation project. Place the new worker next to the worker carrying out that task. During the next break, take both workers back into your supply. You do not need to be able to support a conservation project to hire the new worker instead.");
    $this->tooltip[] = clienttranslate("<SIDE_II> When carrying out an association task, you may place 1 additional worker on the task to reduce the value needed for that task by 2. You can do this multiple times.");
  }
}
