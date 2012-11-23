This is `StronglyTypedJSON` class for PHP.

```
require 'StronglyTypedJSON.php';

class Child extends StronglyTypedJSON {
  /** @var int */      private $age;
  /** @var Parent[] */ private $children;
}
```

and then if you do this:

```
$child = new Child;
$child->age = 10;  /// OK
$child->parents = array(new Adult); // OK!
$child->age = "test!";  /// this throws InvalidArgumentException - not int
$child->parents = new Adult; // InvalidArgumentException - not an array of Parent
$child->parents = new Child; // InvalidArgumentException - not an array of Parent
```

by using magic of `__set`.

**Note that this is NOT possible without some extra magic.** 

```
$ print $child->__toString();
{"age":10,"parents":[{}]}
```