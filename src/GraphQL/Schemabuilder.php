<?php
namespace App\GraphQL;

use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use App\GraphQL\Types\QueryType;
use App\GraphQL\Types\MutationType;

class SchemaBuilder {
    private static array $instances = [];


    public static function getType(string $classname) {
        if (!isset(self::$instances[$classname])) {
            self::$instances[$classname] = new $classname();
        }
        return self::$instances[$classname];
    }

    public static function build(): Schema {
       
        self::$instances = []; 

        $config = SchemaConfig::create()
            ->setQuery(new QueryType())
            ->setMutation(new MutationType());

        return new Schema($config);
    }
}