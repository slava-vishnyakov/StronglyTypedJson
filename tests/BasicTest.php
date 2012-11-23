<?php

require '../lib/StronglyTypedJson.php';

class Child extends StronglyTypedJson {
  /** @var int */     private $age;
  /** @var Adult[] */ private $parents;
}

class Adult extends StronglyTypedJson {
  
}

class BasicTest extends PHPUnit_Framework_TestCase 
{
  public function test() {
    $child = new Child;
    $child->age = 10;  /// OK
    $child->parents = array(new Adult); // OK!
    $this->assertEquals(10, $child->age);
    $this->assertEquals(array(new Adult), $child->parents);
  }

  /** 
   * @expectedException InvalidArgumentException 
   */
  public function test1() {
    $child = new Child;
    $child->age = "test!";  /// this throws InvalidArgumentException!
  }
   
  /** 
   * @expectedException InvalidArgumentException 
   */
  public function test2() {
    $child = new Child;
    $child->parents = new Adult; // InvalidArgumentException - not an array of Parent
  }

  /** 
   * @expectedException InvalidArgumentException 
   */
  public function test3() {
    $child = new Child;
    $child->parents = new Child; // InvalidArgumentException - not an array of Parent
  }

  public function test4() {
    $child = new Child;
    $this->assertEquals('{"age":null,"parents":[]}', $child->toJSON());
    $child->age = 10;  /// OK
    $this->assertEquals('{"age":10,"parents":[]}', $child->toJSON());
    $child->parents = array(new Adult); // OK!
    $this->assertEquals(10, $child->age);
    $this->assertEquals('{"age":10,"parents":[{}]}', $child->toJSON());
  }
}