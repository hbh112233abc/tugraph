<?php
namespace bingher\test;

use bingher\tugraph\EChart;
use bingher\tugraph\TuGraph;

class TuGraphTest extends Base
{
    static $tu;

    static public function setUpBeforeClass(): void
    {
        $env        = __DIR__ . '/../.env';
        $content    = file_get_contents($env);
        $config     = json_decode($content, true);
        static::$tu = new TuGraph($config);
    }
    public function testInit()
    {
        $tu = new TuGraph();
        $this->assertTrue($tu instanceof TuGraph);
    }

    public function testGraph()
    {
        $res = static::$tu->graph('default');
        $this->assertTrue($res instanceof TuGraph);
        $this->assertEquals($this->prop($res, 'graph'), 'default');
    }

    public function testCall()
    {
        $res = static::$tu->graph('三国')->call('match (n)-[r:参战]->(m) return n.name as person,m.start_time,r');
        var_dump($res);
        $res = static::$tu->result($res);
        var_dump($res);
        $this->assertIsArray($res);
    }

    public function testAll($g = '变更')
    {
        $res = static::$tu->graph($g)->all();
        $this->dump($res);
        $this->assertIsArray($res);
        return $res;
    }

    public function testAllEchart()
    {
        $g    = '材料';
        $g    = '变更';
        $data = $this->testAll($g);
        $ec   = new EChart($data);
        $res  = $ec->result();
        $this->dump($res);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('categories', $res);
        $this->assertArrayHasKey('nodes', $res);
        $this->assertArrayHasKey('links', $res);
        $json = json_encode($res, JSON_UNESCAPED_UNICODE);
        file_put_contents($g . '.json', $json);
    }

    public function testQuery()
    {
        $zg  = TuGraph::node('主公');
        $r   = TuGraph::link(['隶属']);
        $wj  = TuGraph::node();
        $res = static::$tu->graph('三国')
            ->match($zg->relationship($r, $wj))
            ->where([$zg->property('name')->equals('曹操'), $wj->property('name')->startsWith('夏侯')])
            ->returning([$zg, $r, $wj]);
        $sql = static::$tu->sql();
        var_dump($sql);
        $this->assertStringMatchesFormat("MATCH (%s:主公)-[%s:隶属]-(%s) WHERE ((%s.name = '曹操') AND (%s.name STARTS WITH '夏侯')) RETURN %s, %s, %s", $sql);
        $res = static::$tu->call();
        var_dump($res);
    }
}
