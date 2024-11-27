<?php

use PhoneBocx\PhoneBocx;

$bootstrap = "bootstrap-5.0.0-dist";

function core_mainhook(&$html)
{
  $html['head'][] = core_header();
  $html['styles'][] = core_styles();
  $html['scripts'][] = core_scripts();
  $html['head'][] = core_genFavicon($html);
  $html['scripts'][] = '/core/js/core.js';
}

function core_footerhook(&$html, $packages)
{
  $html['body'][] = "<div class='container-fluid'>";
  core_gentopnav($html);
  $html['body'][] = core_gensidebar($packages);
  $html['body'][] = core_gentabcontent($packages);
  $html['body'][] = "  </div>";
  $html['body'][] = core_genmodal();
  $html['body'][] = "</div>";
}

function core_header()
{
  $header = '
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Yeeting faxes">
  <meta name="generator" content="Fax Yeeter">
  <title>SendFax Device</title>';
  return $header;
}

function core_genmodal()
{
  return '<div id="coremodal" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modal title</h5>
        <button type="button" class="btn-close modalclose" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Modal body text goes here.</p>
      </div>
      <div class="modal-footer">
        <button type="button" id="modalno" class="btn btn-secondary modalclose" data-bs-dismiss="modal">Close</button>
        <button type="button" id="modalyes" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>';
}

function core_genFavicon($html)
{
  return "<link rel='icon' href='" . $html['favicon'] . "' />";
}

function core_gentopnav(&$html)
{
  $logo = "/core/sfto-logo.png";
  $html['body'][] = "  <div class='row' id='topnav'>";
  $html['body'][] = '    <div class="col-2">
      <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
        <span class="fs-2"><img src="' . $logo . '" alt="Bender is great" style="width: 90%"></span>
      </a>
    </div>
    <div class="col-10">
      <span class="fs-4">
        <p>
          Release <span class="currentver">&nbsp;</span>
          <span id="updateavail" style="display: none">Update Available</span>
          <span id="noupdateavail" style="display: none">(Up to date)</span>
          <span id="queuecount"></span>
          System Uptime: <span id="uptime">00-00</span>
          <a id="logoutbutton" type="button" class="btn btn-secondary btn-sm d-none" href="/?action=logout">Logout</a>
        </p>
      </span>
    </div>
  </div>';
}

function core_styles()
{
  global $bootstrap;
  $icons = "bootstrap-icons-1.11.3";
  $styles = "<link href='/core/$bootstrap/css/bootstrap.min.css' rel='stylesheet'>\n";
  $styles .= "<link href='/core/$icons/bootstrap-icons.css' rel='stylesheet'>";
  $styles .= "<link href='/core/css/core.css' rel='stylesheet'>";
  return $styles;
}

function core_scripts()
{
  global $bootstrap;
  $scripts = "/core/$bootstrap/js/bootstrap.bundle.js";
  return $scripts;
}

function core_gensidebar($packages)
{
  $head = '  <div class="row" id="mainpage">
    <div class="col-2">
      <hr>
        <ul class="nav nav-pills flex-column mb-auto" id="sidebar" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="home-pill" data-bs-toggle="pill" data-bs-target="#pillcontent-core">
              <i class="bi-house"></i> Home
            </button>
          </li>
';
  foreach ($packages as $h => $f) {
    $sfunc = $h . "_sidebarname";
    if (function_exists($sfunc)) {
      $name = $sfunc();
      $sicon = $h . "_iconname";
      $liclassfunc = $h . "_sidebarclass";
      $listylefunc = $h . "_sidebarstyle";
      if (function_exists($liclassfunc)) {
        $liclass = $liclassfunc();
        $head .= "          <li class='nav-item $liclass' ";
      } else {
        $head .= "          <li class='nav-item' ";
      }
      if (function_exists($listylefunc)) {
        $head .= "style='" . $listylefunc() . "' ";
      }
      $head .= "role='presentation'>\n";
      if (function_exists($sicon)) {
        $icon = "<i class='" . $sicon() . "'></i>";
      } else {
        $icon = "question";
      }
      $head .= "<button class='nav-link' id='$h-pill' data-bs-toggle='pill' data-bs-target='#pillcontent-$h'>
            $icon $name 
            </button>
          </li>\n";
    }
  }
  $head .= '        </ul>
      </hr>
    </div>';
  return $head;
}

function core_gentabcontent($packages)
{
  $div = "    <div class='col-8'>\n";
  $div .= "      <div class='tab-content' id='sidebar-content'>\n";
  $div .= "        <div class='tab-pane fade show active' id='pillcontent-core' role='tabpanel' aria-labelledby='home-pill'>" . core_gen_core_body() . "</div>\n";
  foreach ($packages as $hook => $f) {
    $html = core_gendiv($hook);
    if ($html) {
      $div .= "        <div class='tab-pane fade' id='pillcontent-$hook' role='tabpanel' aria-labelledby='$hook-pill'>$html</div>\n";
    }
  }
  $div .= "      </div>\n";
  $div .= "    </div>\n";
  return $div;
}

function core_gendiv($hookname)
{
  $func = $hookname . "_divcontent";
  if (function_exists($func)) {
    return core_divheader($hookname) . $func();
  }
  return false;
  return "Error: $func does not exist!";
}

function core_divheader($hookname)
{
  $func = $hookname . "_header";
  if (function_exists($func)) {
    $text = $func();
  } else {
    $text = "Error: $func missing";
  }

  $header = "<h3>$text</h3>\n";
  return $header;
}

function core_gen_core_body()
{
  $str = "<h3 id='h3id'>System information</h3>";
  $str .= core_gen_spool_alert();
  $str .= "<ul class='list-group list-group-flush'>";
  $str .= "<li class='list-group-item'>";
  $str .= "Current Ver: <span class='currentver'>&nbsp;</span> (Latest <span class='latestver'>&nbsp;</span>) <br />";
  $str .= "<span class='d-none debugspan'>Active Kernel Version: <span class='kver'>&nbsp;</span> (Built <span class='kbuild'>&nbsp;</span>) <br /></span>";
  $str .= "Last Poll: <span id='lastpoll'>&nbsp;</span> <br />";
  $str .= "</li>";
  $str .= "<li class='list-group-item'>Information<ul id='sysinfo'></ul></li>";
  $str .= "<li class='list-group-item'>Network Interfaces:<ul id='netif'></ul></li></li>";
  $str .= "<li class='list-group-item'>Package Information<ul id='pkglist'></ul></li>";
  $str .= "<li class='list-group-item d-none debugspan' id='jsonresp'>Json Response: <span>&nbsp;</span></li>";
  return $str;
}

function core_gen_spool_alert()
{
  $mounts = @file_get_contents("/proc/mounts");
  if (strpos($mounts, " /spool ") !== false) {
    return core_gen_db_alert();
  }
  return "<div class='alert alert-danger' role='alert'><strong>Critical Issue!</strong> /spool directory not mounted. Contact support urgently!</div>";
}

function core_gen_db_alert()
{
  $prodfile = PhoneBocx::getProdDbFilename();
  if (is_readable($prodfile)) {
    return "";
  }
  return "<div class='alert alert-danger' role='alert'><strong>Critical Issue!</strong> Prod database file is not readable! Contact support urgently!</div>";
}
