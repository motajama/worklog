ALTER TABLE entries ADD COLUMN copsoq_quantitative_demands INTEGER CHECK (copsoq_quantitative_demands BETWEEN 0 AND 4);
ALTER TABLE entries ADD COLUMN copsoq_work_pace INTEGER CHECK (copsoq_work_pace BETWEEN 0 AND 4);
ALTER TABLE entries ADD COLUMN copsoq_cognitive_demands INTEGER CHECK (copsoq_cognitive_demands BETWEEN 0 AND 4);
ALTER TABLE entries ADD COLUMN copsoq_low_control INTEGER CHECK (copsoq_low_control BETWEEN 0 AND 4);

ALTER TABLE entries ADD COLUMN nfr_exhausted INTEGER CHECK (nfr_exhausted BETWEEN 0 AND 4);
ALTER TABLE entries ADD COLUMN nfr_detach_difficulty INTEGER CHECK (nfr_detach_difficulty BETWEEN 0 AND 4);
ALTER TABLE entries ADD COLUMN nfr_need_long_recovery INTEGER CHECK (nfr_need_long_recovery BETWEEN 0 AND 4);
ALTER TABLE entries ADD COLUMN nfr_overload INTEGER CHECK (nfr_overload BETWEEN 0 AND 4);

ALTER TABLE entries ADD COLUMN recovery_detachment INTEGER CHECK (recovery_detachment BETWEEN 0 AND 4);
ALTER TABLE entries ADD COLUMN recovery_relaxation INTEGER CHECK (recovery_relaxation BETWEEN 0 AND 4);
ALTER TABLE entries ADD COLUMN recovery_mastery INTEGER CHECK (recovery_mastery BETWEEN 0 AND 4);
ALTER TABLE entries ADD COLUMN recovery_control INTEGER CHECK (recovery_control BETWEEN 0 AND 4);
