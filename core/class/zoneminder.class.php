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

  public static function cron() {
    foreach (eqLogic::byType('zoneminder', true) as $zoneminder) {
      zoneminder::getEvents($zoneminder->getConfiguration('monitorid'));
    }
  }

  public function getSynchro() {
    $addr = config::byKey('addr','zoneminder');
    $uri = $addr . '/api/monitors.json';
    log::add('zoneminder', 'debug', $uri);

    if (config::byKey('user','zoneminder') != '' && config::byKey('password','zoneminder') != '') {
      //cookie
      $post = 'username=' . config::byKey('user','zoneminder') . '&password=' . config::byKey('password','zoneminder') . '&action=login&view=console';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $addr . '/api/index.php');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/zmcookies.txt');
      curl_setopt($ch, CURLOPT_POST      ,1);
      curl_setopt($ch, CURLOPT_POSTFIELDS    ,$post);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
      curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS
      $json_string = curl_exec($ch);
      $info = curl_getinfo($ch);
      curl_close($ch);
      log::add('zoneminder', 'debug', 'Retour ' . print_r($json_string,true) . $json_string . $info);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $uri);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/zmcookies.txt');
      curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/zmcookies.txt');
      $json_string = curl_exec($ch);
      $info = curl_getinfo($ch);
      curl_close($ch);
    } else {
      $json_string = file_get_contents($uri);
    }
    log::add('zoneminder', 'debug', 'Retour ' . print_r($json_string,true) . $json_string);


    //$uri = $addr . '/api/monitors/1.json';
    //log::add('zoneminder', 'debug', $uri);
    //$json_string = file_get_contents($uri);
    //http://zoneminder-server-ip/cgi-bin/nph-zms?mode=jpeg&monitor=monitor_id&scale=100&maxfps=10&buffer=1000&user=username&password=password

  }

  public function getEvents($monitorid) {
    $user = config::byKey('user','zoneminder');
    $password = config::byKey('password','zoneminder');
    $addr = config::byKey('addr','zoneminder');

    $uri = $addr . '/api/events/events/index/MonitorId:' . $monitorid . '.json';
    //log::add('zoneminder', 'debug', $uri);
    $json_string = file_get_contents($uri);

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
