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
                $this->nodes[$node['identity']] = $this->node($node);
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

    /**
     * 根据节点列表构建树形结构
     * @param array $nodes 包含 id、pid 和 name 的节点列表
     * @return array 树形结构的节点列表
     */
    protected function buildTree(array $nodes)
    {
        $tree    = [];
        $nodeMap = [];

        // 首先将所有节点存储在一个关联数组中，键为节点的 id
        foreach ($nodes as $node) {
            $nodeMap[$node['identity']]             = $node;
            $nodeMap[$node['identity']]['children'] = [];
        }

        // 遍历节点列表，先取出根节点
        foreach ($nodes as $node) {
            $pid = $node['pid'] ?? null;
            if ($pid === null) {
                // 如果节点没有父节点，将其添加到树的根节点
                $tree[] = $nodeMap[$node['identity']];
                unset($nodeMap[$node['identity']]);
            }
        }

        foreach ($tree as &$t) {
            $t = $this->findChildren($t, $nodeMap);
        }

        return $tree;
    }

    protected function findChildren(&$node, $nodeMap)
    {
        $children = [];
        foreach ($nodeMap as $id => $nm) {
            if ($nm['pid'] == $node['identity']) {
                $children[] = $nm;
                unset($nodeMap[$id]);
            }
        }
        foreach ($children as &$child) {
            $child['children'] = $this->findChildren($child, $nodeMap);
        }
        $node['children'] = $children;
        return $node;
    }

    protected function tree()
    {
        foreach ($this->links as $link) {
            $dstId = $link['target'];
            $srcId = $link['source'];
            if (empty($this->nodes[$dstId])) {
                continue;
            }
            $this->nodes[$dstId]['pid'] = $srcId;
        }
        $res = $this->buildTree(array_values($this->nodes));
        return $res;
    }

    public function result($type = 'graph')
    {
        switch ($type) {
            case 'graph':
                $res = [
                    'categories' => $this->fmtCategories(),
                    'nodes'      => array_values($this->nodes),
                    'links'      => $this->links,
                ];
                break;
            case 'tree':
                $res = $this->tree();
                break;

        }
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
