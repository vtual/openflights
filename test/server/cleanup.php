<?php
include_once(dirname(__FILE__) . '/config.php');

// Not an actual test, just cleaning up
class DeleteFlightsTest extends WebTestCase {
  function test() {
    global $settings;

    $db = db_connect();
    $sql = "DELETE FROM flights WHERE uid IN (SELECT uid FROM users WHERE name='" . $settings["name"] . "')";
    $result = $db->query($sql);
    echo mysqli_affected_rows($db) . " flights deleted\n";
  }
}

// Not an actual test, just cleaning up
class DeleteAirportTest extends WebTestCase {
  function test() {
    global $settings;

    $db = db_connect();
    $sql = "DELETE FROM airports WHERE uid IN (SELECT uid FROM users WHERE name='" . $settings["name"] . "')";
    $result = $db->query($sql);
    echo mysqli_affected_rows($db) . " airports deleted\n";
  }
}

// Not an actual test, just cleaning up
class DeleteAirlinesTest extends WebTestCase {
  function test() {
    global $settings;

    $db = db_connect();
    $sql = "DELETE FROM airlines WHERE uid IN (SELECT uid FROM users WHERE name='" . $settings["name"] . "')";
    $result = $db->query($sql);
    echo mysqli_affected_rows($db) . " airline(s) deleted\n";
  }
}

// Not an actual test, just cleaning up
class DeleteUserTest extends WebTestCase {
  function test() {
    global $settings;

    $db = db_connect();
    $sql = "DELETE FROM users WHERE name='" . $settings["name"] . "'";
    $result = $db->query($sql);
    echo mysqli_affected_rows($db) . " user deleted\n";
  }
}

?>
