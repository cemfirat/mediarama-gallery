ALTER TABLE cpg_pictures
    ADD COLUMN mime VARCHAR(255) NOT NULL DEFAULT 'image/*' AFTER total_filesize,
    ADD COLUMN ftype VARCHAR(32) NOT NULL DEFAULT 'image' AFTER mime;

UPDATE cpg_pictures
SET mime = 'image/jpeg',
    ftype = 'image'
WHERE pid = 100;
