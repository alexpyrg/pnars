<?php

declare(strict_types=1);

namespace App\Modules\Vehicles;

use PDO;

final class VehicleRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listByAccident(string $accidentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*, vt.label_el AS vehicle_type_label
             FROM vehicles v
             LEFT JOIN lookup_values vt ON vt.id = v.vehicle_type_lookup_id
             WHERE v.accident_id = :accident_id AND v.deleted_at IS NULL
             ORDER BY v.created_at ASC'
        );
        $stmt->execute([':accident_id' => $accidentId]);

        return $stmt->fetchAll() ?: [];
    }

    public function findById(string $vehicleId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*, a.created_by AS accident_owner_id
             FROM vehicles v
             JOIN accidents a ON a.id = v.accident_id
             WHERE v.id = :id AND v.deleted_at IS NULL AND a.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $vehicleId]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(string $accidentId, array $data, string $userId): string
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vehicles (
                accident_id,
                plate_number,
                vehicle_type_lookup_id,
                vehicle_make_id,
                vehicle_model_id,
                vehicle_color_lookup_id,
                drive_wheels_lookup_id,
                steering_position_lookup_id,
                length_mm,
                width_mm,
                road_alignment_lookup_id,
                towing_lookup_id,
                engine_power_kw,
                manufacturing_year,
                curb_weight_kg,
                axles_count,
                general_comments,
                passengers_count,
                defects_caused_lookup_id,
                defects_comments,
                technical_inspection_passed_lookup_id,
                maneuver_before_accident_lookup_id,
                dangerous_load_lookup_id,
                dangerous_load_dispersion_lookup_id,
                collisions_count,
                damage_comments,
                cdc3_lookup_id,
                cdc4_lookup_id,
                on_fire_lookup_id,
                firefighting_material_used_lookup_id,
                collision_offroad_object_lookup_id,
                collision_type_lookup_id,
                abs_lookup_id,
                esp_lookup_id,
                tcs_lookup_id,
                acs_lookup_id,
                ldw_lookup_id,
                css_lookup_id,
                safety_systems_comments,
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
                :accident_id,
                :plate_number,
                :vehicle_type_lookup_id,
                :vehicle_make_id,
                :vehicle_model_id,
                :vehicle_color_lookup_id,
                :drive_wheels_lookup_id,
                :steering_position_lookup_id,
                :length_mm,
                :width_mm,
                :road_alignment_lookup_id,
                :towing_lookup_id,
                :engine_power_kw,
                :manufacturing_year,
                :curb_weight_kg,
                :axles_count,
                :general_comments,
                :passengers_count,
                :defects_caused_lookup_id,
                :defects_comments,
                :technical_inspection_passed_lookup_id,
                :maneuver_before_accident_lookup_id,
                :dangerous_load_lookup_id,
                :dangerous_load_dispersion_lookup_id,
                :collisions_count,
                :damage_comments,
                :cdc3_lookup_id,
                :cdc4_lookup_id,
                :on_fire_lookup_id,
                :firefighting_material_used_lookup_id,
                :collision_offroad_object_lookup_id,
                :collision_type_lookup_id,
                :abs_lookup_id,
                :esp_lookup_id,
                :tcs_lookup_id,
                :acs_lookup_id,
                :ldw_lookup_id,
                :css_lookup_id,
                :safety_systems_comments,
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
            ':accident_id' => $accidentId,
            ':created_by' => $userId,
            ':updated_by' => $userId,
        ]);

        return (string) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    public function update(string $vehicleId, array $data, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE vehicles
             SET
                plate_number = :plate_number,
                vehicle_type_lookup_id = :vehicle_type_lookup_id,
                vehicle_make_id = :vehicle_make_id,
                vehicle_model_id = :vehicle_model_id,
                vehicle_color_lookup_id = :vehicle_color_lookup_id,
                drive_wheels_lookup_id = :drive_wheels_lookup_id,
                steering_position_lookup_id = :steering_position_lookup_id,
                length_mm = :length_mm,
                width_mm = :width_mm,
                road_alignment_lookup_id = :road_alignment_lookup_id,
                towing_lookup_id = :towing_lookup_id,
                engine_power_kw = :engine_power_kw,
                manufacturing_year = :manufacturing_year,
                curb_weight_kg = :curb_weight_kg,
                axles_count = :axles_count,
                general_comments = :general_comments,
                passengers_count = :passengers_count,
                defects_caused_lookup_id = :defects_caused_lookup_id,
                defects_comments = :defects_comments,
                technical_inspection_passed_lookup_id = :technical_inspection_passed_lookup_id,
                maneuver_before_accident_lookup_id = :maneuver_before_accident_lookup_id,
                dangerous_load_lookup_id = :dangerous_load_lookup_id,
                dangerous_load_dispersion_lookup_id = :dangerous_load_dispersion_lookup_id,
                collisions_count = :collisions_count,
                damage_comments = :damage_comments,
                cdc3_lookup_id = :cdc3_lookup_id,
                cdc4_lookup_id = :cdc4_lookup_id,
                on_fire_lookup_id = :on_fire_lookup_id,
                firefighting_material_used_lookup_id = :firefighting_material_used_lookup_id,
                collision_offroad_object_lookup_id = :collision_offroad_object_lookup_id,
                collision_type_lookup_id = :collision_type_lookup_id,
                abs_lookup_id = :abs_lookup_id,
                esp_lookup_id = :esp_lookup_id,
                tcs_lookup_id = :tcs_lookup_id,
                acs_lookup_id = :acs_lookup_id,
                ldw_lookup_id = :ldw_lookup_id,
                css_lookup_id = :css_lookup_id,
                safety_systems_comments = :safety_systems_comments,
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
            ':id' => $vehicleId,
            ':updated_by' => $userId,
        ]);
    }

    public function softDelete(string $vehicleId, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE vehicles
             SET deleted_at = NOW(), updated_by = :updated_by, updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            ':id' => $vehicleId,
            ':updated_by' => $userId,
        ]);
    }
}
