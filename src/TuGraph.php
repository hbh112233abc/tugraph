<?php
namespace bingher\tugraph;

use Exception;
use GuzzleHttp\Client;
use WikibaseSolutions\CypherDSL\Query;
use WikibaseSolutions\CypherDSL\Patterns\Node;
use function WikibaseSolutions\CypherDSL\node;
use function WikibaseSolutions\CypherDSL\query;
use function WikibaseSolutions\CypherDSL\relationship;
use function WikibaseSolutions\CypherDSL\relationshipFrom;
use function WikibaseSolutions\CypherDSL\relationshipTo;


class TuGraph
{
    /**
     * 请求客户端
     * @var Client
     */
    protected $client;
    /**
     * 配置信息
     * @var array
     */
    protected $config = [
        'uri'      => 'http://192.168.102.137:7070',
        'user'     => 'admin',
        'password' => 'Hymake@666',
        'graph'    => '',
    ];

    /**
     * 图项目名称
     * @var string
     */
    protected $graph = '';
    /**
     * 登录令牌
     * @var string
     */
    protected $token = '';

    /**
     * 查询构建器
     * @var Query
     */
    protected $q;

    /**
     * 查询结果数据
     * @var array
     */
    protected $data = [];

    function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->client = new Client(
            [
                'base_uri' => $this->config['uri'],
                'verify'   => false,
                'timeout'  => 30,
            ]
        );
        $this->graph  = $this->config['graph'];
        $this->login();
        $this->q = new Query();
    }

    function __destruct()
    {
        $this->logout();
    }

    /**
     * 登录
     * @return array
     */
    protected function login(): array
    {
        $api         = '/login';
        $params      = [
            'user'     => $this->config['user'],
            'password' => $this->config['password'],
        ];
        $res         = $this->post($api, $params);
        $this->token = $res['jwt'];
        return $res;
    }

    /**
     * 登出
     * @return void
     */
    protected function logout()
    {
        if (empty($this->token)) {
            return;
        }
        $api = '/logout';
        $this->post($api);
        $this->token = '';
    }

    /**
     * 刷新token
     * @return void
     */
    protected function refresh()
    {
        $api         = '/refresh';
        $res         = $this->post($api);
        $this->token = $res['jwt'];
    }

    /**
     * 选择图
     * @param string $graphName
     * @return self
     */
    public function graph(string $graphName)
    {
        $this->graph = $graphName;
        return $this;
    }

    /**
     * 生成节点
     * @param array $args
     * @return Node
     */
    static public function node(string|null $label = null)
    {
        return node($label);
    }

    /**
     * 生成查询
     * @return Query
     */
    static public function query()
    {
        return query();
    }

    static public function link(array $types = [])
    {
        return relationship()->withTypes($types);
    }
    static public function linkTo(array $types = [])
    {
        return relationshipTo()->withTypes($types);
    }
    static public function linkFrom(array $types = [])
    {
        return relationshipFrom()->withTypes($types);
    }

    /**
     * 获取Query解析后的Cypher脚本
     * @return string
     */
    public function sql(): string
    {
        return $this->q->build();
    }

    /**
     * 调用cypher语句
     * @param mixed $sql
     * @return array|array{elapsed:float, header: array{array{name:string,type:int}}, results: array{array}}
     */
    public function call(string $sql = ''): array
    {
        if (empty($sql)) {
            $sql = $this->q->build();
        }
        // print_r($sql);
        $api        = '/cypher';
        $params     = [
            'script' => $sql,
            'graph'  => $this->graph,
        ];
        $res        = $this->post($api, $params);
        $this->data = $res;
        return $res;
    }

    /**
     * 解析成EChart渲染需要的数据
     * @param array $data
     * @return array{categories: array, links: array, nodes: array}
     */
    public function eChart(string $type = 'graph')
    {
        $data = $this->result();
        $ec   = new EChart($data);
        return $ec->result($type);
    }

    /**
     * 组装结果数据
     * @param array $data
     * @return array[]|array{edges: array, nodes: array, table: array}
     */
    public function result(array $data = []): array
    {
        if (empty($data)) {
            $data = $this->data;
        }
        $result = [
            'nodes' => [],
            'edges' => [],
            'table' => [],
        ];
        $nodes  = [];
        $edges  = [];
        $header = $data['header'];
        foreach ($data['result'] as $items) {
            $row = [];
            foreach ($items as $i => $item) {
                $type = $header[$i]['type'];
                switch ($type) {
                    case 0: //标量
                        $row[$header[$i]['name']] = $item;
                        break;
                    case 1: //点
                        $n = json_decode($item, true);
                        $nodes[$n['identity']] = $n;
                        break;
                    case 2: //边
                        $r = json_decode($item, true);
                        $edges[] = $r;
                        break;
                    // case 4: //路径
                    default:
                        $p = json_decode($item, true);
                        foreach ($p as $d) {
                            if (isset($d['src']) && isset($d['dst'])) {
                                $edges[] = $d;
                            } else {
                                $nodes[$d['identity']] = $d;
                            }
                        }
                        break;

                }
            }
            if (!empty($row)) {
                $result['table'][] = $row;
            }
        }
        $result['nodes'] = array_values($nodes);
        $result['edges'] = array_values($edges);
        return $result;
    }

    /**
     * 获取所有所有数据
     * @return array[]|array{edges: array, nodes: array}
     */
    public function all()
    {
        $sql    = 'match p=(n)-[]-(m) return p';
        $res    = $this->call($sql);
        $result = $this->result($res);
        return $result;
    }

    /**
     * POST请求服务器
     * @param string $api
     * @param array $params
     * @throws \Exception
     * @return array
     */
    protected function post(string $api, array $params = []): array
    {
        $data = [];
        if (!empty($params)) {
            $data['json'] = $params;
        }
        $data['headers'] = [
            'content-type' => 'application/json',
        ];
        if (!empty($this->token)) {
            $data['headers']['Authorization'] = sprintf('Bearer %s', $this->token);
        }
        $response = $this->client->post($api, $data);
        if ($response->getStatusCode() !== 200) {
            throw new Exception(sprintf('%s 请求失败 code:%d', $api, $response->getStatusCode()));
        }
        $body   = $response->getBody()->getContents();
        $result = json_decode($body, true);
        // var_dump($result);
        return $result;
        // if ($result['success'] != 0) {
        //     //TODO: token过期的话,刷新token后重新请求
        //     throw new Exception(sprintf('%s 接口返回失败 code:%d msg: %s', $api, $result['errorCode'], $result['errorMessage']));
        // }
        // return $result['data'];
    }

    function __call($method, $args)
    {
        // 检查当前类是否有对应方法
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $args);
        }

        // 检查Query对象里是否有对应的方法
        if (method_exists($this->q, $method)) {
            call_user_func_array([$this->q, $method], $args);
            return $this;
        }

        // 若都不存在则抛出异常
        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', get_class($this), $method));
    }
}
