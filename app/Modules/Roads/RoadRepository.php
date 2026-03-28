<?php

declare(strict_types=1);

namespace App\Modules\Roads;

use PDO;

final class RoadRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listByAccident(string $accidentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, ar.road_order
             FROM accident_roads ar
             JOIN roads r ON r.id = ar.road_id
             WHERE ar.accident_id = :accident_id AND r.deleted_at IS NULL
             ORDER BY ar.road_order ASC'
        );
        $stmt->execute([':accident_id' => $accidentId]);

        return $stmt->fetchAll() ?: [];
    }

    public function findById(string $roadId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, ar.accident_id, ar.road_order, a.created_by AS accident_owner_id
             FROM roads r
             JOIN accident_roads ar ON ar.road_id = r.id
             JOIN accidents a ON a.id = ar.accident_id
             WHERE r.id = :id AND r.deleted_at IS NULL AND a.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $roadId]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(string $accidentId, int $roadOrder, array $data, string $userId): string
    {
        $startedTransaction = !$this->pdo->inTransaction();

        if ($startedTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO roads (
                    traffic_flow_lookup_id,
                    lanes_count,
                    surface_type_lookup_id,
                    speed_limit_kmh,
                    speed_limit_type_lookup_id,
                    intersection_lookup_id,
                    local_area_lookup_id,
                    road_alignment_lookup_id,
                    construction_zone_lookup_id,
                    traffic_control_signs_lookup_id,
                    traffic_signal_operation_lookup_id,
                    road_surface_condition_lookup_id,
                    pedestrian_infrastructure_lookup_id,
                    bicycle_infrastructure_lookup_id,
                    lighting_condition_lookup_id,
                    weather_condition_lookup_id,
                    strong_winds_lookup_id,
                    fog_lookup_id,
                    conditions_comments,
                    road_defects_lookup_id,
                    temporary_factors_lookup_id,
                    signaling_related_lookup_id,
                    speed_restriction_infrastructure_lookup_id,
                    speed_restriction_contributed_lookup_id,
                    possible_causes_comments,
                    additional_comments,
                    information_source_lookup_id,
                    confidence_level_lookup_id,
                    confidence_description,
                    investigation_method_lookup_id,
                    investigation_confidence_lookup_id,
                    investigation_confidence_description,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
                ) VALUES (
                    :traffic_flow_lookup_id,
                    :lanes_count,
                    :surface_type_lookup_id,
                    :speed_limit_kmh,
                    :speed_limit_type_lookup_id,
                    :intersection_lookup_id,
                    :local_area_lookup_id,
                    :road_alignment_lookup_id,
                    :construction_zone_lookup_id,
                    :traffic_control_signs_lookup_id,
                    :traffic_signal_operation_lookup_id,
                    :road_surface_condition_lookup_id,
                    :pedestrian_infrastructure_lookup_id,
                    :bicycle_infrastructure_lookup_id,
                    :lighting_condition_lookup_id,
                    :weather_condition_lookup_id,
                    :strong_winds_lookup_id,
                    :fog_lookup_id,
                    :conditions_comments,
                    :road_defects_lookup_id,
                    :temporary_factors_lookup_id,
                    :signaling_related_lookup_id,
                    :speed_restriction_infrastructure_lookup_id,
                    :speed_restriction_contributed_lookup_id,
                    :possible_causes_comments,
                    :additional_comments,
                    :information_source_lookup_id,
                    :confidence_level_lookup_id,
                    :confidence_description,
                    :investigation_method_lookup_id,
                    :investigation_confidence_lookup_id,
                    :investigation_confidence_description,
                    :created_by,
                    :updated_by,
                    NOW(),
                    NOW()
                )
                RETURNING id'
            );

            $stmt->execute($data + [
                ':created_by' => $userId,
                ':updated_by' => $userId,
            ]);

            $roadId = (string) $stmt->fetchColumn();

            $linkStmt = $this->pdo->prepare(
                'INSERT INTO accident_roads (accident_id, road_id, road_order, created_by)
                 VALUES (:accident_id, :road_id, :road_order, :created_by)'
            );
            $linkStmt->execute([
                ':accident_id' => $accidentId,
                ':road_id' => $roadId,
                ':road_order' => $roadOrder,
                ':created_by' => $userId,
            ]);

            if ($startedTransaction) {
                $this->pdo->commit();
            }

            return $roadId;
        } catch (\Throwable $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(string $roadId, array $data, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE roads
             SET
                traffic_flow_lookup_id = :traffic_flow_lookup_id,
                lanes_count = :lanes_count,
                surface_type_lookup_id = :surface_type_lookup_id,
                speed_limit_kmh = :speed_limit_kmh,
                speed_limit_type_lookup_id = :speed_limit_type_lookup_id,
                intersection_lookup_id = :intersection_lookup_id,
                local_area_lookup_id = :local_area_lookup_id,
                road_alignment_lookup_id = :road_alignment_lookup_id,
                construction_zone_lookup_id = :construction_zone_lookup_id,
                traffic_control_signs_lookup_id = :traffic_control_signs_lookup_id,
                traffic_signal_operation_lookup_id = :traffic_signal_operation_lookup_id,
                road_surface_condition_lookup_id = :road_surface_condition_lookup_id,
                pedestrian_infrastructure_lookup_id = :pedestrian_infrastructure_lookup_id,
                bicycle_infrastructure_lookup_id = :bicycle_infrastructure_lookup_id,
                lighting_condition_lookup_id = :lighting_condition_lookup_id,
                weather_condition_lookup_id = :weather_condition_lookup_id,
                strong_winds_lookup_id = :strong_winds_lookup_id,
                fog_lookup_id = :fog_lookup_id,
                conditions_comments = :conditions_comments,
                road_defects_lookup_id = :road_defects_lookup_id,
                temporary_factors_lookup_id = :temporary_factors_lookup_id,
                signaling_related_lookup_id = :signaling_related_lookup_id,
                speed_restriction_infrastructure_lookup_id = :speed_restriction_infrastructure_lookup_id,
                speed_restriction_contributed_lookup_id = :speed_restriction_contributed_lookup_id,
                possible_causes_comments = :possible_causes_comments,
                additional_comments = :additional_comments,
                information_source_lookup_id = :information_source_lookup_id,
                confidence_level_lookup_id = :confidence_level_lookup_id,
                confidence_description = :confidence_description,
                investigation_method_lookup_id = :investigation_method_lookup_id,
                investigation_confidence_lookup_id = :investigation_confidence_lookup_id,
                investigation_confidence_description = :investigation_confidence_description,
                updated_by = :updated_by,
                updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL'
        );

        $stmt->execute($data + [
            ':id' => $roadId,
            ':updated_by' => $userId,
        ]);
    }

    public function softDelete(string $roadId, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE roads
             SET deleted_at = NOW(), updated_by = :updated_by, updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            ':id' => $roadId,
            ':updated_by' => $userId,
        ]);
    }
}
