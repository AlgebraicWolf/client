<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tarantool\Client\Schema;

use Tarantool\Client\Client;
use Tarantool\Client\Exception\Exception;
use Tarantool\Client\IProto;
use Tarantool\Client\Request\DeleteRequest;
use Tarantool\Client\Request\InsertRequest;
use Tarantool\Client\Request\ReplaceRequest;
use Tarantool\Client\Request\SelectRequest;
use Tarantool\Client\Request\UpdateRequest;
use Tarantool\Client\Request\UpsertRequest;

final class Space
{
    public const VSPACE = 281;
    public const VINDEX = 289;

    private $client;
    private $id;
    private $indexes = [];

    public function __construct(Client $client, int $id)
    {
        $this->client = $client;
        $this->id = $id;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function select(array $key = [], $index = 0, int $limit = \PHP_INT_MAX &0xffffffff, int $offset = 0, int $iteratorType = IteratorTypes::EQ) : array
    {
        if (\is_string($index)) {
            $index = $this->getIndexIdByName($index);
        }

        $request = new SelectRequest($this->id, $index, $key, $offset, $limit, $iteratorType);

        return $this->client->sendRequest($request)->getBodyField(IProto::DATA);
    }

    public function insert(array $values) : array
    {
        $request = new InsertRequest($this->id, $values);

        return $this->client->sendRequest($request)->getBodyField(IProto::DATA);
    }

    public function replace(array $values) : array
    {
        $request = new ReplaceRequest($this->id, $values);

        return $this->client->sendRequest($request)->getBodyField(IProto::DATA);
    }

    public function update(array $key, array $operations, $index = 0) : array
    {
        if (\is_string($index)) {
            $index = $this->getIndexIdByName($index);
        }

        $request = new UpdateRequest($this->id, $index, $key, $operations);

        return $this->client->sendRequest($request)->getBodyField(IProto::DATA);
    }

    public function upsert(array $values, array $operations) : array
    {
        $request = new UpsertRequest($this->id, $values, $operations);

        return $this->client->sendRequest($request)->getBodyField(IProto::DATA);
    }

    public function delete(array $key, $index = 0) : array
    {
        if (\is_string($index)) {
            $index = $this->getIndexIdByName($index);
        }

        $request = new DeleteRequest($this->id, $index, $key);

        return $this->client->sendRequest($request)->getBodyField(IProto::DATA);
    }

    public function flushIndexes() : void
    {
        $this->indexes = [];
    }

    private function getIndexIdByName(string $indexName) : int
    {
        if (isset($this->indexes[$indexName])) {
            return $this->indexes[$indexName];
        }

        $schema = $this->client->getSpaceById(self::VINDEX);
        $data = $schema->select([$this->id, $indexName], Index::INDEX_NAME);

        if (empty($data)) {
            throw new Exception("No index '$indexName' is defined in space #{$this->id}");
        }

        return $this->indexes[$indexName] = $data[0][1];
    }
}
