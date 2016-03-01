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

  public static function cronDaily() {
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
      //log::add('zoneminder', 'debug', 'Retour ' . print_r($monitor['Monitor']['Id'],true));
      $deviceid = $monitor['Monitor']['Id'];
      $name = $monitor['Monitor']['Name'];
      $function = $monitor['Monitor']['Function'];
      $enabled = $monitor['Monitor']['Enabled'];
      $width = $monitor['Monitor']['Width'];
      $height = $monitor['Monitor']['Height'];
      $type = $monitor['Monitor']['Type'];
      $controlable = $monitor['Monitor']['Controllable'];
      $controlid = $monitor['Monitor']['ControlId'];
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
      $zoneminder->setConfiguration('controlable',$controlable);
      $zoneminder->setConfiguration('controlid',$controlid);
      $zoneminder->save();

      /*$cmd = zoneminderCmd::byEqLogicIdAndLogicalId($zoneminder->getId(),'activate');
  		if (!is_object($cmd)) {
  			$cmd = new zoneminderCmd();
  			$cmd->setLogicalId('activate');
  			$cmd->setIsVisible(1);
  			$cmd->setName(__('Activer', __FILE__));
  		}
  		$cmd->setType('action');
  		$cmd->setSubType('other');
      $cmd->setConfiguration('request','Enabled');
      $cmd->setConfiguration('value','true');
  		$cmd->setEqLogic_id($zoneminder->getId());
  		$cmd->save();*/
      $cmdlogic = zoneminderCmd::byEqLogicIdAndLogicalId($zoneminder->getId(),'activate');
      if (!is_object($cmdlogic)) {
  			$cmdlogic = new zoneminderCmd();
  			$cmdlogic->setLogicalId('activate');
  			$cmdlogic->setIsVisible(1);
  			$cmdlogic->setName(__('Activer', __FILE__));
  		}
  		$cmdlogic->setType('action');
  		$cmdlogic->setSubType('other');
      $cmdlogic->setConfiguration('request','Monitor[Enabled]:true');
  		$cmdlogic->setEqLogic_id($zoneminder->getId());
  		$cmdlogic->save();
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


      if (class_exists('camera')) {
        zoneminder::syncCamera($deviceid);
      }
    }
  }

  public function syncCamera($deviceid) {
    $zoneminder = self::byLogicalId($deviceid, 'zoneminder');
    $url = config::byKey('addr','zoneminder');
    $url_parse = parse_url($url);
    $plugin = plugin::byId('camera');
		$camera_jeedom = eqLogic::byLogicalId('zm'.$deviceid, 'camera');
		if (!is_object($camera_jeedom)) {
			$camera_jeedom = new camera();
			$camera_jeedom->setDisplay('height', $zoneminder->getConfiguration('height'));
			$camera_jeedom->setDisplay('width', $zoneminder->getConfiguration('width'));
		}
		$camera_jeedom->setName('ZM ' . $zoneminder->getName());
		$camera_jeedom->setIsEnable($zoneminder->getConfiguration('enabled'));
		$camera_jeedom->setConfiguration('ip', $url_parse['host']);
		$camera_jeedom->setConfiguration('urlStream', '/zm/cgi-bin/nph-zms?mode=single&monitor=' . $deviceid . '&user=#username#&pass=#password#');
    $camera_jeedom->setConfiguration('username', config::byKey('user','zoneminder'));
    $camera_jeedom->setConfiguration('password', config::byKey('password','zoneminder'));
		$camera_jeedom->setEqType_name('camera');
		$camera_jeedom->setConfiguration('protocole', $url_parse['scheme']);
    $camera_jeedom->setConfiguration('device', ' ');
    $camera_jeedom->setConfiguration('applyDevice', ' ');
    $port = isset($url_parse['port']) ? ':' . $url_parse['port'] : '';
    $port = str_replace(':','',$port);
    if ($port == '') {
      if ($url_parse['scheme'] == 'https') {
  			$port = 443;
  		} else {
  			$port = 80;
  		}
    }
    $camera_jeedom->setConfiguration('port', $port);
		$camera_jeedom->setLogicalId('zm'.$deviceid);
		$camera_jeedom->save();
  }

  public function sendConf($deviceid,$command) {
    $addr = config::byKey('addr','zoneminder');
    $uri = $addr . '/api/monitors/' . $deviceid . '.json';
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

    zoneminder::getSynchro();

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
      $deviceid = $eqLogic->getConfiguration('deviceid');

      zoneminder::sendConf($deviceid,$request);

      return true;
      break;
    }
    return true;
  }
}

?>
