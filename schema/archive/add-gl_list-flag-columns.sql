ALTER TABLE /*_*/gather_list
  ADD gl_perm_override TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER gl_perm,
  ADD gl_needs_review TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER gl_perm_override,
  ADD gl_flag_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER gl_needs_review;

-- migrate PERM_HIDDEN to PERM_PUBLIC + PERM_OVERRIDE_HIDDEN
UPDATE /*_*/gather_list
  SET gl_perm = 1, gl_perm_override = 1
  WHERE gl_perm = 2;

DROP INDEX /*i*/gl_perm_updated ON /*_*/gather_list;
CREATE INDEX /*i*/gl_visibility_updated ON /*_*/gather_list (gl_perm, gl_perm_override, gl_flag_count, gl_needs_review, gl_updated, gl_id);
