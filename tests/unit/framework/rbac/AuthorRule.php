<?php
namespace yiiunit\framework\rbac;

use yii\rbac\Rule;

/**
 * Checks if authorID matches userID passed via params
 */
class AuthorRule extends Rule
{
    public $name = 'isAuthor';
    public $reallyReally = false;

    /**
     * @inheritdoc
     */
    public function execute($item, $params)
    {
        return $params['authorID'] == $params['user'];
    }
}