ALTER TABLE entries
    ADD COLUMN copsoq_quantitative_demands TINYINT UNSIGNED NULL AFTER recovery_override,
    ADD COLUMN copsoq_work_pace TINYINT UNSIGNED NULL AFTER copsoq_quantitative_demands,
    ADD COLUMN copsoq_cognitive_demands TINYINT UNSIGNED NULL AFTER copsoq_work_pace,
    ADD COLUMN copsoq_low_control TINYINT UNSIGNED NULL AFTER copsoq_cognitive_demands,
    ADD COLUMN nfr_exhausted TINYINT UNSIGNED NULL AFTER copsoq_low_control,
    ADD COLUMN nfr_detach_difficulty TINYINT UNSIGNED NULL AFTER nfr_exhausted,
    ADD COLUMN nfr_need_long_recovery TINYINT UNSIGNED NULL AFTER nfr_detach_difficulty,
    ADD COLUMN nfr_overload TINYINT UNSIGNED NULL AFTER nfr_need_long_recovery,
    ADD COLUMN recovery_detachment TINYINT UNSIGNED NULL AFTER nfr_overload,
    ADD COLUMN recovery_relaxation TINYINT UNSIGNED NULL AFTER recovery_detachment,
    ADD COLUMN recovery_mastery TINYINT UNSIGNED NULL AFTER recovery_relaxation,
    ADD COLUMN recovery_control TINYINT UNSIGNED NULL AFTER recovery_mastery;
