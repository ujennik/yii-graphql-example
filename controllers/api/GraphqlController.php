<?php

namespace app\controllers\api;

use app\schema\Types;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use yii\base\InvalidArgumentException;
use yii\helpers\Json;


class GraphqlController extends \yii\rest\ActiveController
{
    public $modelClass = '';

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
            'index' => ['POST', 'OPTIONS'],
        ];
    }

    public function actions()
    {
        return [];
    }

    public function actionIndex()
    {
        // сразу заложим возможность принимать параметры
        // как через MULTIPART, так и через POST/GET

        $query = \Yii::$app->request->get('query', \Yii::$app->request->post('query'));
        $variables = \Yii::$app->request->get('variables', \Yii::$app->request->post('variables'));
        $operation = \Yii::$app->request->get('operation', \Yii::$app->request->post('operation', null));

        if (empty($query)) {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            $query = $input['query'];
            $variables = isset($input['variables']) ? $input['variables'] : [];
            $operation = isset($input['operation']) ? $input['operation'] : null;
        }

        // библиотека принимает в variables либо null, либо ассоциативный массив
        // на строку будет ругаться

        if (!empty($variables) && !is_array($variables)) {
            try {
                $variables = Json::decode($variables);
            } catch (InvalidArgumentException $e) {
                $variables = null;
            }
        }

        // создаем схему и подключаем к ней наши корневые типы

        $schema = new Schema([
            'query' => Types::query(),
            'mutation' => Types::mutation(),
        ]);

        // огонь!

        $result = GraphQL::executeQuery(
            $schema,
            $query,
            null,
            null,
            empty($variables) ? null : $variables,
            empty($operation) ? null : $operation
        )->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);

        return $result;
    }
}
