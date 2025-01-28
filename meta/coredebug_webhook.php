<?php

use PhoneBocx\WebUI\DebugTools;
use PhoneBocx\WebUI\DebugTools\ConsolePng;

function coredebug_mainhook(&$html)
{
  $consolejs =  ConsolePng::getConsoleJavascript();
  if ($consolejs) {
    $html['rawscripts']['coredebug'] = $consolejs;
  }
}

function coredebug_sidebarname()
{
  return "Debug Tools";
}

function coredebug_sidebarclasxs()
{
  return 'd-none debugbutton';
}

function coredebug_divcontent()
{
  $c = ConsolePng::getDebugPageHtml();
  $str = "$c<p><ul>\n";
  $entries = DebugTools::getToolList();
  foreach ($entries as $name => $row) {
    $str .= "<li>" . DebugTools::getToolHtml($name) . "</li>\n";
  }
  $str .= "</ul></p>";
  return $str;
}

function coredebug_iconname()
{
  return "bi-pc-display-horizontal";
}

function coredebug_header()
{
  return "System Tools";
}
