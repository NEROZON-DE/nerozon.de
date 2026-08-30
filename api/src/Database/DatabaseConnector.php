<?php
declare(strict_types=1);
namespace Nerozon\Database;
class DatabaseException extends \RuntimeException{} class DatabaseUnavailable extends DatabaseException{} class DatabaseConstraintViolation extends DatabaseException{} class DatabaseQueryFailure extends DatabaseException{} class DatabaseSerializationFailure extends DatabaseException{}
final readonly class WriteResult{public function __construct(public int $affectedRows){}}
interface DatabaseConnector{public function execute(string $statement,array $parameters=[]):WriteResult;public function fetchOne(string $statement,array $parameters=[]):?array;public function fetchAll(string $statement,array $parameters=[]):array;public function transaction(callable $callback):mixed;public function encodeJson(mixed $value):string;public function decodeJson(string $json):mixed;}
