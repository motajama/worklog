ALTER TABLE footprint_factors
    MODIFY base_unit ENUM('hour', 'event', 'km', 'kwh', 'token') NOT NULL;

ALTER TABLE event_footprint_items
    MODIFY base_unit_snapshot ENUM('hour', 'event', 'km', 'kwh', 'token') NOT NULL;

ALTER TABLE routine_footprint_items
    MODIFY base_unit_snapshot ENUM('hour', 'event', 'km', 'kwh', 'token') NOT NULL;
