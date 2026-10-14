-- Explicit reversible association: one album per old copy; do not merge inferred identities.
-- Existing titles, years, author links and covers are never changed.
START TRANSACTION;
INSERT INTO ALBUMS_TBL(User_Id,Title,Artists_JSON,Original_Year,Legacy_Vinyl_Id,Metadata_Source)
SELECT v.User_Id,v.Title,COALESCE((SELECT JSON_ARRAYAGG(a.Author_Name) FROM AUTOR_VINYLS_TBL av JOIN AUTHORS_TBL a ON a.Id=av.Autor_Id WHERE av.Vinilo_Id=v.Id),JSON_ARRAY()),NULLIF(v.Release_date,0),v.Id,'legacy'
FROM VINYLS_TBL v WHERE v.Album_Id IS NULL AND NOT EXISTS (SELECT 1 FROM ALBUMS_TBL a WHERE a.User_Id=v.User_Id AND a.Legacy_Vinyl_Id=v.Id);
UPDATE VINYLS_TBL v JOIN ALBUMS_TBL a ON a.Legacy_Vinyl_Id=v.Id AND a.User_Id=v.User_Id SET v.Album_Id=a.Id WHERE v.Album_Id IS NULL;
COMMIT;
