-- Licence: GNU GPL v2+

ALTER TABLE /*_*/gather_list
  -- The list permissions type (NULL=not migrated, GATHER_PRIVATE=0, GATHER_PUBLIC=1)
  MODIFY COLUMN gl_perm TINYINT UNSIGNED NOT NULL DEFAULT 0;
