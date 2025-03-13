# PHP driver for TuGraph

## Install

```shell
composer require bingher/tugraph
```

## Use

- use origin cypher query

```php
use bingher\tugraph\TuGraph;

$config = [
    'uri' => '<API Base Uri>',
    'user' => '<User>',
    'password' => '<Password>',
    'graph' => '<Graph name,default:empty>',
];

$tu = new TuGraph($config);

$sql = 'Match p = (n)-[]-(m) Return p';
$result = $tu->graph('<GraphName>')->call($sql);
```

- use php-cypher-dsl query

```php
use bingher\tugraph\TuGraph;
use function WikibaseSolutions\CypherDSL\node;
use function WikibaseSolutions\CypherDSL\query;

$config = [
    'uri' => '<API Base Uri>',
    'user' => '<User>',
    'password' => '<Password>',
    'graph' => '<Graph name,default:empty>',
];

$tu = new TuGraph($config);

$tom = node("Person")->withProperties(["name" => "Tom Hanks"]);
$coActors = node();

$statement = query()
    ->match($tom->relationshipTo(node(), "ACTED_IN")->relationshipFrom($coActors, "ACTED_IN"))
    ->returning($coActors->property("name"))
    ->build();

$result = $tu->graph('<GraphName>')->call($statement);

```

- example

```php
use bingher\tugraph\TuGraph;

$config = [
    'uri' => '<API Base Uri>',
    'user' => '<User>',
    'password' => '<Password>',
    'graph' => '<Graph name,default:empty>',
];

$tu = new TuGraph($config);

$zg = TuGraph::node('主公');
$r  = TuGraph::link(['隶属']);
$wj = TuGraph::node();

$res = $tu->graph('三国')
    ->match($zg->relationship($r, $wj))
    ->where([$zg->property('name')->equals('曹操'), $wj->property('name')->startsWith('夏侯')])
    ->returning([$zg, $r, $wj])
    ->call();

$sql = $tu->sql();
var_dump($sql);
$this->assertStringMatchesFormat("MATCH (%s:主公)-[%s:隶属]-(%s) WHERE ((%s.name = '曹操') AND (%s.name STARTS WITH '夏侯')) RETURN %s, %s, %s", $sql);
var_dump($res);
```

> query result

```php
array(4) {
  ["elapsed"]=>float(0.0040798187255859375)
  ["header"]=>
  array(3) {
    [0]=>
    array(2) {
      ["name"]=>string(35) "varc9815f2faa847fca20a93a8d940604e5"
      ["type"]=>int(1)
    }
    [1]=>
    array(2) {
      ["name"]=>string(35) "var741b1da0dd39e501fc9adf7febe0706a"
      ["type"]=>int(2)
    }
    [2]=>
    array(2) {
      ["name"]=>string(35) "vard064169534b1fb4d74dbec62d435ef5a"
      ["type"]=>int(1)
    }
  }
  ["result"]=>
  array(3) {
    [0]=>
    array(3) {
      [0]=>string(170) "{"identity":91,"label":"主公","properties":{"camp":"魏","family":"谯县曹氏","father_position":"太尉","hometown":"豫州","name":"曹操","position":"魏武帝"}}"
      [1]=>string(94) "{"dst":91,"forward":false,"identity":0,"label":"隶属","label_id":2,"src":28,"temporal_id":0}"
      [2]=>string(126) "{"identity":28,"label":"武将","properties":{"camp":"魏","family":"谯县夏侯氏","hometown":"豫州","name":"夏侯惇"}}"
    }
    [1]=>
    array(3) {
      [0]=>string(170) "{"identity":91,"label":"主公","properties":{"camp":"魏","family":"谯县曹氏","father_position":"太尉","hometown":"豫州","name":"曹操","position":"魏武帝"}}"
      [1]=>string(94) "{"dst":91,"forward":false,"identity":0,"label":"隶属","label_id":2,"src":34,"temporal_id":0}"
      [2]=>string(126) "{"identity":34,"label":"武将","properties":{"camp":"魏","family":"谯县夏侯氏","hometown":"豫州","name":"夏侯尚"}}"
    }
    [2]=>
    array(3) {
      [0]=>string(170) "{"identity":91,"label":"主公","properties":{"camp":"魏","family":"谯县曹氏","father_position":"太尉","hometown":"豫州","name":"曹操","position":"魏武帝"}}"
      [1]=>string(94) "{"dst":91,"forward":false,"identity":0,"label":"隶属","label_id":2,"src":35,"temporal_id":0}"
      [2]=>string(126) "{"identity":35,"label":"武将","properties":{"camp":"魏","family":"谯县夏侯氏","hometown":"豫州","name":"夏侯渊"}}"
    }
  }
  ["size"]=>int(3)
}
```

- use result function

```php
$sql = '<cypher query>';
$callResult = $tu->call($sql);
$result = $tu->result($callResult);
var_dump($result);
```

> result

```shell
array (
    'nodes' => array(...node array),
    'edges' => array(...edge array),
    'table' => array(...array),
)
```

## Ref

[TuGraph Restfull API](https://tugraph-db.readthedocs.io/zh-cn/latest/7.client-tools/7.restful-api.html)
[TuGraph Cypher API](https://tugraph-db.readthedocs.io/zh-cn/latest/8.query/1.cypher.html)
[php-cypher-dsl wiki](https://github.com/neo4j-php/php-cypher-dsl/wiki)
