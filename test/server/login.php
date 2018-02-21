<?php
include_once(dirname(__FILE__) . '/config.php');

//
// Test cases for php/login.php
//

// Standard log in
class SuccessfulLoginTest extends WebTestCase {
  function test() {
    global $webroot, $settings;
    $result = login($this);
    $this->assertEqual($result->status, "1");
    $this->assertEqual($result->name, $settings['name']);
  }
}

// Legacy login test (where name hash was built using uppercase chars)
class LegacyLoginTest extends WebTestCase {
  function test() {
    global $webroot, $settings;

    $name = "LegacyUser";
    $password = "foobar";
    $hash = md5($password . $name);

    $db = db_connect();
    $sql = "INSERT INTO users(name,password) VALUES('$name','$hash')";
    $result = $db->query($sql);
    $this->assertTrue(mysqli_affected_rows($db) == 1, "Legacy user added");

    $result = login($this, $name, $password);
    $this->assertEqual($result->status, "1");

    $sql = "DELETE FROM users WHERE name='$name'";
    $result = $db->query($sql);
    $this->assertTrue(mysqli_affected_rows($db) == 1, "Legacy user deleted");
  }
}

// Wrong password
class WrongPasswordLoginTest extends WebTestCase {
  function test() {
    global $webroot, $settings;
    $result = login($this, $settings["name"], "incorrect");
    $this->assertEqual($result->status, "0");
  }
}

// Login attempt with expired session
class ExpiredSessionTest extends WebTestCase {
  function test() {
    global $webroot, $settings;
    $result = login($this, $settings["name"], $settings["password"], "DEADBEEF");
    $this->assertEqual($result->status, "0");
    $this->assertText("Session expired");
  }
}
?>
