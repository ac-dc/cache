<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Dependencies\DbQueryDependency;
use Yiisoft\Db\Query;
use Yiisoft\Db\Tests\DatabaseTestCase;

class DbQueryDependencyTest extends DatabaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected $driverName = 'sqlite';


    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $db = $this->getConnection(false);

        $db->createCommand()->createTable('dependency_item', [
            'id' => 'pk',
            'value' => 'string',
        ])->execute();

        $db->createCommand()->insert('dependency_item', ['value' => 'initial'])->execute();
    }

    public function testIsChanged()
    {
        $db = $this->getConnection(false);
        $cache = new ArrayCache();

        $dependency = new DbQueryDependency($db);
        $dependency->db = $db;
        $dependency->query = (new Query())
            ->select(['id'])
            ->from('dependency_item')
            ->orderBy(['id' => SORT_DESC])
            ->limit(1);
        $dependency->reusable = false;

        $dependency->evaluateDependency($cache);
        $this->assertFalse($dependency->isChanged($cache));

        $db->createCommand()->insert('dependency_item', ['value' => 'new'])->execute();

        $this->assertTrue($dependency->isChanged($cache));
    }

    /**
     * @depends testIsChanged
     */
    public function testCustomMethod()
    {
        $db = $this->getConnection(false);
        $cache = new ArrayCache();

        $dependency = new DbQueryDependency($db);
        $dependency->query = (new Query())
            ->from('dependency_item')
            ->andWhere(['value' => 'active']);
        $dependency->reusable = false;
        $dependency->method = 'exists';

        $dependency->evaluateDependency($cache);
        $this->assertFalse($dependency->isChanged($cache));

        $db->createCommand()->insert('dependency_item', ['value' => 'active'])->execute();

        $this->assertTrue($dependency->isChanged($cache));
    }

    /**
     * @depends testCustomMethod
     */
    public function testCustomMethodCallback()
    {
        $db = $this->getConnection(false);
        $cache = new ArrayCache();

        $dependency = new DbQueryDependency($db);
        $dependency->query = (new Query())
            ->from('dependency_item')
            ->andWhere(['value' => 'not exist']);
        $dependency->reusable = false;
        $dependency->method = function (Query $query, $db) {
            return $query->orWhere(['value' => 'initial'])->exists($db);
        };

        $dependency->evaluateDependency($cache);
        $this->assertFalse($dependency->isChanged($cache));

        $db->createCommand()->delete('dependency_item')->execute();

        $this->assertTrue($dependency->isChanged($cache));
    }
}
