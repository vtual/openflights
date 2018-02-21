<?php
include_once(dirname(__FILE__) . '/config.php');

//
// Test cases for php/flights.php

// Check demo user map
class BlockAnonExportCase extends WebTestCase {
  function test() {
    global $webroot;

    $params = array("export" => "true");
    $this->get($webroot . "php/flights.php", $params);
    $this->assertText("You must be logged in to export.");
  }
}

class ExportAirlineToCSVCase extends WebTestCase {
  function test() {
    global $webroot, $route;

    // First figure out the correct results
    $db = db_connect();
    $sql = "SELECT alid FROM airlines WHERE iata='" . $route["core_al_iata"] . "'";
    $result = $db->query($sql) or die($sql . ":" . mysqli_error($db));
    if($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
      $alid = $row["alid"];
    }

    $sql = "SELECT COUNT(*) AS count FROM routes WHERE alid=" . $alid;
    $result = $db->query($sql) or die($sql . ":" . mysqli_error($db));
    if($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
      $route_count = intval($row["count"]);
    }

    // Then test
    assert_login($this);
    $params = array("export" => "export", "id" => "L" . $alid);
    $csv = $this->get($webroot . "php/flights.php", $params);
    $rows = explode("\n", $csv);
    $this->assertEqual(count($rows), $route_count + 1);
  }
}

?>
