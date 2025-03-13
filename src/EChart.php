<?php
namespace bingher\tugraph;

class EChart
{
    /**
     * 分类
     * @var array
     */
    protected $categories = [];
    /**
     * 节点
     * @var array
     */
    protected $nodes = [];
    /**
     * 边
     * @var array
     */
    protected $links = [];

    function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->parse($data);
        }
    }

    /**
     * 解析数据
     * @param array $data
     * @return static
     */
    public function parse(array $data)
    {
        if (!empty($data['nodes'])) {
            foreach ($data['nodes'] as $node) {
                $this->nodes[] = $this->node($node);
            }
        }
        if (!empty($data['edges'])) {
            foreach ($data['edges'] as $edge) {
                $this->links[] = $this->edge($edge);
            }
        }
        return $this;
    }

    /**
     * 格式化分类
     * @return array{name: mixed[]}
     */
    protected function fmtCategories()
    {
        $res = [];
        foreach ($this->categories as $c) {
            $res[] = ['name' => $c];
        }
        return $res;
    }

    public function result()
    {
        $res = [
            'categories' => $this->fmtCategories(),
            'nodes'      => $this->nodes,
            'links'      => $this->links,
        ];
        return $res;
    }

    /**
     * 获取节点名称
     * @param array $node
     */
    protected function getNodeName(array $node)
    {
        if (empty($node['properties'])) {
            return $node['label'];
        }
        $keys = ['name', 'code', 'title', '名称', '编号', '题名'];
        foreach ($keys as $key) {
            if (isset($node['properties'][$key])) {
                return $node['properties'][$key];
            }
        }
        return $node['label'];
    }

    /**
     * 解析节点
     * @param array $node
     * @return array
     */
    protected function node(array $node): array
    {
        $node['id'] = $node['identity'];
        if (!in_array($node['label'], $this->categories)) {
            $this->categories[] = $node['label'];
        }
        $node['category'] = array_search($node['label'], $this->categories);
        $node['name']     = $this->getNodeName($node);
        return $node;
    }

    /**
     * 解析边
     * @param array $edge
     * @return array
     */
    protected function edge(array $edge): array
    {
        $edge['label']  = [
            'formatter' => $edge['label'],
            'show'      => true,
        ];
        $edge['source'] = $edge['src'];
        $edge['target'] = $edge['dst'];
        return $edge;
    }
}
