<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Mediarama\Media\Application\MediaSearch;
use Mediarama\Media\Application\MediaSearchCriteria;
use Mediarama\Media\Application\MediaSearchResult;
use Symfony\Component\Uid\Uuid;

final readonly class DbalMediaSearch implements MediaSearch
{
    public function __construct(private Connection $connection)
    {
    }

    public function search(MediaSearchCriteria $c): array
    {
        $where = ["deleted_at IS NULL", "processing_state = 'ready'"];
        $params = [];
        $types = [];

        if ($c->text !== null && trim($c->text) !== '') {
            $where[] = "(search_document @@ websearch_to_tsquery('simple', :text) OR original_filename ILIKE :like)";
            $params['text'] = trim($c->text);
            $params['like'] = '%'.trim($c->text).'%';
        }
        foreach (['creator' => $c->creator, 'camera_make' => $c->cameraMake, 'camera_model' => $c->cameraModel, 'lens' => $c->lens] as $column => $value) {
            if ($value !== null && trim($value) !== '') {
                $where[] = $column.' ILIKE :'.$column;
                $params[$column] = '%'.trim($value).'%';
            }
        }
        if ($c->minimumIso !== null) {
            $where[] = 'iso >= :minimum_iso';
            $params['minimum_iso'] = $c->minimumIso;
        }
        if ($c->maximumIso !== null) {
            $where[] = 'iso <= :maximum_iso';
            $params['maximum_iso'] = $c->maximumIso;
        }
        if ($c->capturedFrom !== null) {
            $where[] = 'captured_at >= :captured_from';
            $params['captured_from'] = $c->capturedFrom->format(DATE_ATOM);
        }
        if ($c->capturedUntil !== null) {
            $where[] = 'captured_at <= :captured_until';
            $params['captured_until'] = $c->capturedUntil->format(DATE_ATOM);
        }
        if ($c->hasLocation === true) {
            $where[] = 'latitude IS NOT NULL AND longitude IS NOT NULL';
        } elseif ($c->hasLocation === false) {
            $where[] = 'latitude IS NULL OR longitude IS NULL';
        }

        $params['limit'] = $c->limit;
        $params['offset'] = $c->offset;
        $types['limit'] = ParameterType::INTEGER;
        $types['offset'] = ParameterType::INTEGER;

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, original_filename, mime_type, title, description, captured_at, creator, camera_make, camera_model, lens, iso, location_name
             FROM media_assets
             WHERE '.implode(' AND ', $where).'
             ORDER BY captured_at DESC NULLS LAST, created_at DESC
             LIMIT :limit OFFSET :offset',
            $params,
            $types,
        );

        return array_map(static fn (array $row): MediaSearchResult => new MediaSearchResult(
            Uuid::fromString((string) $row['id']),
            (string) $row['original_filename'],
            (string) $row['mime_type'],
            $row['title'] !== null ? (string) $row['title'] : null,
            $row['description'] !== null ? (string) $row['description'] : null,
            $row['captured_at'] !== null ? new DateTimeImmutable((string) $row['captured_at']) : null,
            $row['creator'] !== null ? (string) $row['creator'] : null,
            $row['camera_make'] !== null ? (string) $row['camera_make'] : null,
            $row['camera_model'] !== null ? (string) $row['camera_model'] : null,
            $row['lens'] !== null ? (string) $row['lens'] : null,
            $row['iso'] !== null ? (int) $row['iso'] : null,
            $row['location_name'] !== null ? (string) $row['location_name'] : null,
        ), $rows);
    }
}
