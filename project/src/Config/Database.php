<?php

namespace App\Config;

use PDO;
use PDOException;
use InvalidArgumentException;

/**
 * Class Database
 *
 * Gestion centralisée de la connexion PDO et des opérations CRUD génériques.
 *
 * Exemple d'utilisation :
 *
 * ```php
 * // SELECT
 * $users = Database::select('users', ['id', 'email']);
 *
 * // INSERT
 * $userId = Database::insert('users', [
 *     'email' => 'test@mail.com',
 *     'username' => 'Thomas'
 * ]);
 *
 * // UPDATE
 * Database::update('users',
 *     ['username' => 'NouveauNom'],
 *     ['id' => 5]
 * );
 *
 * // DELETE
 * Database::delete('users', ['id' => 5]);
 * ```
 */
class Database
{
	private static ?PDO $instance = null;

	/**
	 * Liste blanche des tables autorisées.
	 *
	 * @var string[]
	 */
	private static array $allowedTables = [
		'users', 
		'joueur', 
		'partie',
		'salon',
		'tourdejeu', 
		'codes_attente',
		'rematch_invitations',
		'archive_partie',
		'archives_tourdejeu'
	];

	private static bool $environmentLoaded = false;

	/**
	 * Retourne l'instance PDO (Singleton).
	 *
	 * @return PDO
	 */
	public static function getInstance(): PDO
	{
		if (self::$instance === null) {
			self::loadEnvironment();

			try {
				$databaseUrl = getenv('DATABASE_URL');
				if ($databaseUrl === false || $databaseUrl === '') {
					throw new InvalidArgumentException('DATABASE_URL est manquante dans project/.env.');
				}

				$parts = parse_url($databaseUrl);
				if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
					throw new InvalidArgumentException('DATABASE_URL est invalide.');
				}

				$dsn = 'pgsql:host=' . $parts['host'];
				if (isset($parts['port'])) {
					$dsn .= ';port=' . $parts['port'];
				}
				$dsn .= ';dbname=' . ltrim($parts['path'], '/');
				parse_str($parts['query'] ?? '', $options);
				foreach (['sslmode', 'channel_binding'] as $option) {
					if (isset($options[$option])) {
						$dsn .= ';' . $option . '=' . $options[$option];
					}
				}

				self::$instance = new PDO(
					$dsn,
					rawurldecode($parts['user'] ?? ''),
					rawurldecode($parts['pass'] ?? ''),
					[
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
						PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
						PDO::ATTR_EMULATE_PREPARES => false
					]
				);
			} catch (PDOException | InvalidArgumentException $e) {
				die("Erreur de connexion à la base de données : " . $e->getMessage());
			}
		}

		return self::$instance;
	}

	private static function loadEnvironment(): void
	{
		if (self::$environmentLoaded) {
			return;
		}

		$envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
		if (is_file($envFile)) {
			foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
				$line = trim($line);
				if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
					continue;
				}

				[$name, $value] = explode('=', $line, 2);
				$name = trim($name);
				$value = trim($value);
				$value = trim($value, "\\\"'");
				putenv($name . '=' . $value);
			}
		}

		self::$environmentLoaded = true;
	}

	/**
	 * Effectue un SELECT.
	 *
	 * @param string   $table   Nom de la table
	 * @param string[] $columns Colonnes à sélectionner
	 * @param array    $where   Conditions sous forme ['colonne' => valeur]
	 *
	 * @return array Résultat sous forme de tableau associatif
	 *
	 * @throws InvalidArgumentException
	 */
	public static function select(
		string $table,
		array $columns = ['*'],
		array $where = [],
		string $operator = 'AND',
		?string $orderBy = null,
		string $direction = 'ASC',
	): array
	{
		self::validateTable($table);

		$db = self::getInstance();
		$columnsSql = implode(', ', $columns);

		$sql = "SELECT {$columnsSql} FROM {$table}";
		$params = [];

		if (!empty($where)) {

			$conditions = [];
			$operator = strtoupper($operator) === 'OR' ? 'OR' : 'AND';

			foreach ($where as $column => $value) {
				$conditions[] = "{$column} = :{$column}";
				$params[$column] = $value;
			}

			$sql .= " WHERE " . implode(" {$operator} ", $conditions);
		}

		if ($orderBy !== null) {

			$direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

			$sql .= " ORDER BY {$orderBy} {$direction}";
		}

		$stmt = $db->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll();
	}

	/**
	 * Effectue un INSERT.
	 *
	 * @param string $table Nom de la table
	 * @param array  $data  Données à insérer ['colonne' => valeur]
	 *
	 * @return int ID de la ligne insérée
	 *
	 * @throws InvalidArgumentException
	 */
	public static function insert(string $table, array $data): int
	{
		self::validateTable($table);

		if (empty($data)) {
			throw new InvalidArgumentException("Données vides.");
		}

		$db = self::getInstance();

		$columns = implode(', ', array_keys($data));
		$placeholders = ':' . implode(', :', array_keys($data));

		$sql = "INSERT INTO {$table} ({$columns})
				VALUES ({$placeholders})";

		$stmt = $db->prepare($sql);
		$ok = $stmt->execute($data);

		return (int) $db->lastInsertId();
	}

	public static function insertFromSelect(
		string $targetTable,
		array $targetColumns,
		string $sourceTable,
		array $sourceColumns,
		array $where = []
	): int
	{
		self::validateTable($targetTable);
		self::validateTable($sourceTable);

		if (empty($targetColumns) || empty($sourceColumns)) {
			throw new InvalidArgumentException("Colonnes invalides.");
		}

		if (count($targetColumns) !== count($sourceColumns)) {
			throw new InvalidArgumentException("Les colonnes source et cible doivent correspondre.");
		}

		$db = self::getInstance();

		$target = implode(', ', $targetColumns);
		$source = implode(', ', $sourceColumns);

		$sql = "INSERT INTO {$targetTable} ({$target})
				SELECT {$source}
				FROM {$sourceTable}";

		$params = [];

		if (!empty($where)) {
			$conditions = [];

			foreach ($where as $column => $value) {
				$conditions[] = "{$column} = :{$column}";
				$params[$column] = $value;
			}

			$sql .= " WHERE " . implode(' AND ', $conditions);
		}

		$stmt = $db->prepare($sql);
		$stmt->execute($params);

		return $stmt->rowCount();
	}

	/**
	 * Effectue un UPDATE.
	 *
	 * @param string $table Nom de la table
	 * @param array  $data  Données à modifier
	 * @param array  $where Conditions
	 *
	 * @return bool True si succès
	 *
	 * @throws InvalidArgumentException
	 */
	public static function update(string $table, array $data, array $where): bool
	{
		self::validateTable($table);

		if (empty($data)) {
			throw new InvalidArgumentException("Données vides.");
		}

		if (empty($where)) {
			throw new InvalidArgumentException("Update sans condition interdit.");
		}

		$db = self::getInstance();

		$setParts = [];
		$params = [];

		foreach ($data as $column => $value) {

			if (is_array($value) && isset($value["__raw"])) {

				// expression SQL brute
				$setParts[] = "{$column} = {$value['__raw']}";

			} else {

				$setParts[] = "{$column} = :set_{$column}";
				$params["set_{$column}"] = $value;

			}
		}

		$whereParts = [];

		foreach ($where as $column => $value) {

			$whereParts[] = "{$column} = :where_{$column}";
			$params["where_{$column}"] = $value;

		}

		$sql = "UPDATE {$table}
				SET " . implode(', ', $setParts) . "
				WHERE " . implode(' AND ', $whereParts);

		$stmt = $db->prepare($sql);

		return $stmt->execute($params);
	}

	/**
	 * Effectue un DELETE.
	 *
	 * @param string $table Nom de la table
	 * @param array  $where Conditions
	 *
	 * @return bool True si suppression réussie
	 *
	 * @throws InvalidArgumentException
	 */
	public static function delete(string $table, array $where): bool
	{
		self::validateTable($table);

		if (empty($where)) {
			throw new InvalidArgumentException("Delete sans condition interdit.");
		}

		$db = self::getInstance();

		$whereParts = [];
		foreach ($where as $column => $value) {
			$whereParts[] = "{$column} = :{$column}";
		}

		$sql = "DELETE FROM {$table}
				WHERE " . implode(' AND ', $whereParts);

		$stmt = $db->prepare($sql);
		$stmt->execute($where);

		return true;
	}

	/**
	 * Vérifie que la table est autorisée.
	 *
	 * @param string $table
	 *
	 * @throws InvalidArgumentException
	 */
	private static function validateTable(string $table): void
	{
		$table = strtolower($table);
		if (!in_array( $table, self::$allowedTables)) {
			throw new InvalidArgumentException("Table non autorisée.");
		}
	}

	/**
	 * Retourne le dernier ID inséré
	 */
	public static function lastInsertId(): int
	{
		return (int) self::$instance->lastInsertId();
	}

	public static function raw(string $expression): array
	{
		return ["__raw" => $expression];
	}
}
