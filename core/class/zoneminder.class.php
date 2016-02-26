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

  public static function cronHourly() {
    zoneminder::getSynchro();
  }

  public function getSynchro() {
    $addr = config::byKey('addr','zoneminder');
    $uri = $addr . '/api/monitors.json';
    log::add('zoneminder', 'debug', $uri);

    if (config::byKey('user','zoneminder') != '' && config::byKey('password','zoneminder') != '') {
      //cookie
      $post = 'username=' . config::byKey('user','zoneminder') . '&password=' . config::byKey('password','zoneminder') . '&action=login&view=console';
      $loginUrl = $addr . '/index.php';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $loginUrl);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      curl_setopt($ch, CURLOPT_COOKIEJAR, 'zmcookie.txt');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $json_string = curl_exec($ch);
      $store = curl_exec($ch);

      curl_setopt($ch, CURLOPT_POST, 0);
      curl_setopt($ch, CURLOPT_URL, $uri);
      $json_string = curl_exec($ch);
      curl_close($ch);
    } else {
      return false;
    }
    $parsed_json = json_decode($json_string, true);
    foreach($parsed_json['monitors'] as $monitor) {
      //log::add('zoneminder', 'debug', 'Retour ' . print_r($monitor,true));
      log::add('zoneminder', 'debug', 'Retour ' . print_r($monitor['Monitor']['Id'],true));
      $deviceid = $monitor['Monitor']['Id'];
      $name = $monitor['Monitor']['Name'];
      $function = $monitor['Monitor']['Function'];
      $enabled = $monitor['Monitor']['Enabled'];
      $width = $monitor['Monitor']['Width'];
      $height = $monitor['Monitor']['Height'];
      $type = $monitor['Monitor']['Type'];
      $zoneminder = self::byLogicalId($deviceid, 'zoneminder');
      if (!is_object($zoneminder)) {
        $zoneminder = new zoneminder();
        $zoneminder->setEqType_name('zoneminder');
        $zoneminder->setLogicalId($deviceid);
        $zoneminder->setName($name);
        $zoneminder->setIsEnable(true);
        $zoneminder->setConfiguration('deviceid',$deviceid);
      }
      $zoneminder->setConfiguration('name',$name);
      $zoneminder->setConfiguration('function',$function);
      $zoneminder->setConfiguration('enabled',$enabled);
      $zoneminder->setConfiguration('width',$width);
      $zoneminder->setConfiguration('height',$height);
      $zoneminder->setConfiguration('type',$type);
      $zoneminder->save();

      $cmdlogic = zoneminderCmd::byEqLogicIdAndLogicalId($zoneminder->getId(),'activate');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new zoneminderCmd();
        $cmdlogic->setEqLogic_id($zoneminder->getId());
        $cmdlogic->setEqType('zoneminder');
        $cmdlogic->setType('action');
        $cmdlogic->setSubType('other');
        $cmdlogic->setName('Activer');
        $cmdlogic->setLogicalId('activate');
        $cmdlogic->setConfiguration('request','Monitor[Enabled]:true');
        $cmdlogic->save();
      }
      $cmdlogic = zoneminderCmd::byEqLogicIdAndLogicalId($zoneminder->getId(),'unactivate');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new zoneminderCmd();
        $cmdlogic->setEqLogic_id($zoneminder->getId());
        $cmdlogic->setEqType('zoneminder');
        $cmdlogic->setType('action');
        $cmdlogic->setSubType('other');
        $cmdlogic->setName('Désactiver');
        $cmdlogic->setLogicalId('unactivate');
        $cmdlogic->setConfiguration('request','Monitor[Enabled]:false');
        $cmdlogic->save();
      }
      $cmdlogic = zoneminderCmd::byEqLogicIdAndLogicalId($zoneminder->getId(),'active');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new zoneminderCmd();
        $cmdlogic->setEqLogic_id($zoneminder->getId());
        $cmdlogic->setEqType('zoneminder');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Activation');
        $cmdlogic->setLogicalId('active');
        $cmdlogic->setSubType('binary');
        $cmdlogic->save();
      }
      $cmdlogic->setConfiguration('value', $enabled);
      $cmdlogic->save();
      $cmdlogic->event($enabled);

      $cmdlogic = zoneminderCmd::byEqLogicIdAndLogicalId($zoneminder->getId(),'modect');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new zoneminderCmd();
        $cmdlogic->setEqLogic_id($zoneminder->getId());
        $cmdlogic->setEqType('zoneminder');
        $cmdlogic->setType('action');
        $cmdlogic->setSubType('other');
        $cmdlogic->setName('Fonction Détection');
        $cmdlogic->setLogicalId('modect');
        $cmdlogic->setConfiguration('request','Monitor[Function]:Modect');
        $cmdlogic->save();
      }
      $cmdlogic = zoneminderCmd::byEqLogicIdAndLogicalId($zoneminder->getId(),'monitor');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new zoneminderCmd();
        $cmdlogic->setEqLogic_id($zoneminder->getId());
        $cmdlogic->setEqType('zoneminder');
        $cmdlogic->setType('action');
        $cmdlogic->setSubType('other');
        $cmdlogic->setName('Fonction Caméra');
        $cmdlogic->setLogicalId('monitor');
        $cmdlogic->setConfiguration('request','Monitor[Function]:Monitor');
        $cmdlogic->save();
      }
      $cmdlogic = zoneminderCmd::byEqLogicIdAndLogicalId($zoneminder->getId(),'function');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new zoneminderCmd();
        $cmdlogic->setEqLogic_id($zoneminder->getId());
        $cmdlogic->setEqType('zoneminder');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Fonction');
        $cmdlogic->setLogicalId('function');
        $cmdlogic->setSubType('string');
        $cmdlogic->save();
      }
      $cmdlogic->setConfiguration('value', $function);
      $cmdlogic->save();
      $cmdlogic->event($function);
    }
  }

  public function syncCamera($monitorid) {

  }

  public function sendConf($monitorid,$command) {
    $addr = config::byKey('addr','zoneminder');
    $uri = $addr . '/api/monitors/' . $monitorid . '.json';
    log::add('zoneminder', 'debug', $uri);

    if (config::byKey('user','zoneminder') != '' && config::byKey('password','zoneminder') != '') {
      //cookie
      $post = 'username=' . config::byKey('user','zoneminder') . '&password=' . config::byKey('password','zoneminder') . '&action=login&view=console';
      $loginUrl = $addr . '/index.php';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $loginUrl);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      curl_setopt($ch, CURLOPT_COOKIEJAR, 'zmcookie.txt');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $json_string = curl_exec($ch);
      $store = curl_exec($ch);

      curl_setopt($ch, CURLOPT_URL, $uri);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $command);
      $json_string = curl_exec($ch);
      curl_close($ch);
    } else {
      return false;
    }

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

      $eqLogic = $this->getEqLogic();
      $monitorid = $eqLogic->getConfiguration('monitorid');

      zoneminder::sendConf($monitorid,$request);

      return true;
      break;
    }
    return true;
  }
}

?>
