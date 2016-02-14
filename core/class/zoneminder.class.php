<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class zoneminder extends eqLogic {

  public function preUpdate() {
    if ($this->getConfiguration('addr') == '') {
      throw new Exception(__('L\'adresse ne peut être vide',__FILE__));
    }
  }

  public function preSave() {
    $this->setLogicalId($this->getConfiguration('addr'));
  }


}

class zoneminderCmd extends cmd {

  public function execute($_options = null) {


            switch ($this->getType()) {
				          case 'info' :
          					return $this->getConfiguration('value');
          					break;
                case 'action' :
					          $request = $this->getConfiguration('request');
                    switch ($this->getSubType()) {
                        case 'slider':
                            $request = str_replace('#slider#', $value, $request);
                            break;
                        case 'color':
                            $request = str_replace('#color#', $_options['color'], $request);
                            break;
                        case 'message':
              							if ($_options != null)  {
              								$replace = array('#title#', '#message#');
              								$replaceBy = array($_options['title'], $_options['message']);
              								if ( $_options['title'] == '') {
              									throw new Exception(__('Le sujet ne peuvent être vide', __FILE__));
              								}
              								$request = str_replace($replace, $replaceBy, $request);

              							}
              							else
              							 $request = 1;
                            break;
						            default : $request == null ?  1 : $request;
					          }

					$eqLogic = $this->getEqLogic();
					$LogicalID = $this->getLogicalId();

					$url = $this->getConfiguration('url');
          $value = file($url);

					return $value;
			}
			return true;
    }
}

?>
