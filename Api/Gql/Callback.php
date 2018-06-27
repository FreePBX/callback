<?php

namespace FreePBX\modules\Callback\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Callback extends Base {
	protected $module = 'callback';

	public function mutationCallback() {
		if($this->checkAllWriteScope()) {
			return function() {
				return [
					'addCallback' => Relay::mutationWithClientMutationId([
						'name' => 'addCallback',
						'description' => 'Add a new entry to Callback',
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'callback' => [
								'type' => $this->typeContainer->get('callback')->getObject(),
								'resolve' => function ($payload) {
									return count($payload) > 1 ? $payload : null;
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$sql = "INSERT INTO callback (`callback_id`,`description`,`callbacknum`,`destination`,`sleep`) VALUES (:id,:description,:callbacknum,:destination,:sleep)";
							$sth = $this->freepbx->Database->prepare($sql);
							$sth->execute($this->getMutationExecuteArray($input));
							$item = $this->getSingleData($input['id']);
							return !empty($item) ? $item : [];
						}
					]),
					'updateCallback' => Relay::mutationWithClientMutationId([
						'name' => 'updateCallback',
						'description' => 'Update an entry in Callback',
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'callback' => [
								'type' => $this->typeContainer->get('callback')->getObject(),
								'resolve' => function ($payload) {
									return count($payload) > 1 ? $payload : null;
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$item = $this->getSingleData($input['id']);
							if(empty($tiem)) {
								return null;
							}
							$sql = "UPDATE callback SET `callback_id` = :id,`description` = :description,`callbacknum` = :callbacknum,`destination` = :destination,`sleep` = :sleep WHERE `callback_id` = :id";
							$sth = $this->freepbx->Database->prepare($sql);
							$sth->execute($this->getMutationExecuteArray($input));
							$item = $this->getSingleData($input['id']);
							return !empty($item) ? $item : [];
						}
					]),
					'removeCallback' => Relay::mutationWithClientMutationId([
						'name' => 'removeCallback',
						'description' => 'Remove an entry from Callback',
						'inputFields' => [
							'id' => [
								'type' => Type::nonNull(Type::id())
							]
						],
						'outputFields' => [
							'deletedId' => [
								'type' => Type::nonNull(Type::id()),
								'resolve' => function ($payload) {
									return $payload['id'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$sql = "DELETE FROM callback WHERE `callback_id` = :id";
							$sth = $this->freepbx->Database->prepare($sql);
							$sth->execute([
								":id" => $input['id']
							]);
							return ['id' => $input['id']];
						}
					])
				];
			};
		}
	}

	public function queryCallback() {
		if($this->checkAllReadScope()) {
			return function() {
				return [
					'allCallbacks' => [
						'type' => $this->typeContainer->get('callback')->getConnectionType(),
						'description' => '',
						'args' => Relay::forwardConnectionArgs(),
						'resolve' => function($root, $args) {
							$after = !empty($args['after']) ? Relay::fromGlobalId($args['after'])['id'] : null;
							$first = !empty($args['first']) ? $args['first'] : null;
							return Relay::connectionFromArraySlice(
								$this->getData($after,$first),
								$args,
								[
									'sliceStart' => !empty($after) ? $after : 0,
									'arrayLength' => $this->getTotal()
								]
							);
						},
					],
					'callback' => [
						'type' => $this->typeContainer->get('callback')->getObject(),
						'description' => '',
						'args' => [
							'id' => [
								'type' => Type::id(),
								'description' => 'The ID',
							]
						],
						'resolve' => function($root, $args) {
							return $this->getSingleData(Relay::fromGlobalId($args['id'])['id']);
						}
					]
				];
			};
		}
	}

	private function getTotal() {
		$sql = "SELECT count(*) as count FROM callback";;
		$sth = $this->freepbx->Database->prepare($sql);
		$sth->execute();
		return $sth->fetchColumn();
	}

	private function getData($after, $first) {
		$sql = 'SELECT * FROM callback';
		$sql .= " " . (!empty($first) && is_numeric($first) ? "LIMIT ".$first : "LIMIT 18446744073709551610");
		$sql .= " " . (!empty($after) && is_numeric($after) ? "OFFSET ".$after : "OFFSET 0");

		$sth = $this->freepbx->Database->prepare($sql);
		$sth->execute();
		return $sth->fetchAll(\PDO::FETCH_ASSOC);
	}

	private function getSingleData($id) {
		$sth = $this->freepbx->Database->prepare('SELECT * FROM callback WHERE `callback_id` = :id');
		$sth->execute([
			":id" => $id
		]);
		return $sth->fetch(\PDO::FETCH_ASSOC);
	}

	public function initializeTypes() {
		$user = $this->typeContainer->create('callback');
		$user->setDescription('');

		$user->addInterfaceCallback(function() {
			return [$this->getNodeDefinition()['nodeInterface']];
		});

		$user->setGetNodeCallback(function($id) {
			return $this->getSingleData($id);
		});

		$user->addFieldCallback(function() {
			return [
				'id' => Relay::globalIdField('', function($row) {
					return isset($row['callback_id']) ? $row['callback_id'] : null;
				}),
				'callback_id' => [
					'type' => Type::nonNull(Type::string()),
					'description' => '',
					'resolver' => function($row) {
						return isset($row['callback_id']) ? $row['callback_id'] : null;
					}
				],
				'description' => [
					'type' => Type::string(),
					'description' => 'Enter a description for this callback.',
				],
				'callbacknum' => [
					'type' => Type::string(),
					'description' => 'Optional: Enter the number to dial for the callback. Leave this blank to just dial the incoming CallerID Number',
				],
				'destination' => [
					'type' => Type::string(),
					'description' => 'Where to send the caller once they are called back',
				],
				'sleep' => [
					'type' => Type::int(),
					'description' => 'Optional: Enter the number of seconds the system should wait before calling back.',
				],
			];
		});

		$user->setConnectionResolveNode(function ($edge) {
			return $edge['node'];
		});

		$user->setConnectionFields(function() {
			return [
				'totalCount' => [
					'type' => Type::int(),
					'resolve' => function($value) {
						return $this->getTotal();
					}
				],
				'callbacks' => [
					'type' => Type::listOf($this->typeContainer->get('callback')->getObject()),
					'resolve' => function($root, $args) {
						$data = array_map(function($row){
							return $row['node'];
						},$root['edges']);
						return $data;
					}
				]
			];
		});
	}

	private function getMutationFields() {
		return [
			'id' => [
				'type' => Type::nonNull(Type::id()),
				'description' => ''
			],
			'description' => [
				'type' => Type::string(),
				'description' => 'Enter a description for this callback.'
			],
			'callbacknum' => [
				'type' => Type::string(),
				'description' => ''
			],
			'destination' => [
				'type' => Type::string(),
				'description' => 'Where to send the caller once they are called back'
			],
			'sleep' => [
				'type' => Type::int(),
				'description' => 'Optional: Enter the number of seconds the system should wait before calling back.'
			]
		];
	}

	private function getMutationExecuteArray($input) {
		return [
			":callback_id" => isset($input['id']) ? $input['id'] : '',
			":description" => isset($input['description']) ? $input['description'] : null,
			":callbacknum" => isset($input['callbacknum']) ? $input['callbacknum'] : null,
			":destination" => isset($input['destination']) ? $input['destination'] : null,
			":sleep" => isset($input['sleep']) ? $input['sleep'] : null
		];
	}
}
